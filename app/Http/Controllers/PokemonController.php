<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use Inertia\Inertia;

class PokemonController extends Controller
{
    public function index(Request $request)
    {
        // 1. Recibimos parámetros (incluyendo los nuevos filtros)
        $generation = $request->input('generation', 1);
        $page = $request->input('page', 1);
        $search = strtolower($request->input('search', ''));
        $type = strtolower($request->input('type', ''));
        $perPage = 20;

        // 2. SEGURIDAD (RBAC): Esto manda sobre todo lo demás
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user || !$user->hasPermissionTo("ver generacion {$generation}")) {
            abort(403, 'No tienes los permisos necesarios para ver los Pokémons de esta generación.');
        }

        // 3. Obtenemos TODA la lista de especies de esta Generación
        $response = Http::get("https://pokeapi.co/api/v2/generation/{$generation}");
        if ($response->failed()) abort(500, 'Error con la PokeAPI');

        $species = collect($response->json()['pokemon_species'])->map(function ($s) {
            $urlParts = explode('/', rtrim($s['url'], '/'));
            $s['id'] = end($urlParts);
            return $s;
        });

        // 4. FILTRO 1: BÚSQUEDA POR NOMBRE
        if (!empty($search)) {
            // Filtramos la colección para que solo queden los que contengan el texto buscado
            $species = $species->filter(fn($s) => str_contains(strtolower($s['name']), $search));
        }

        // 5. FILTRO 2: POR TIPO (La intersección experta)
        if (!empty($type)) {
            $typeResponse = Http::get("https://pokeapi.co/api/v2/type/{$type}");
            if ($typeResponse->ok()) {
                // Sacamos un array plano solo con los nombres de los Pokémon que tienen ese tipo
                $pokemonsOfThisType = collect($typeResponse->json()['pokemon'])
                    ->map(fn($p) => $p['pokemon']['name'])
                    ->toArray();

                // Cruzamos los datos: De nuestra lista actual, conservamos SOLO los que 
                // existen en la lista de tipos que acabamos de descargar.
                $species = $species->filter(fn($s) => in_array($s['name'], $pokemonsOfThisType));
            }
        }

        // 6. PAGINACIÓN DE LA COLECCIÓN YA FILTRADA
        $species = $species->sortBy('id')->values(); // Reindexamos
        $total = $species->count();
        $offset = ($page - 1) * $perPage;
        $pagedSpecies = $species->slice($offset, $perPage);

        // 7. CONCURRENCIA PARA OBTENER IMÁGENES
        $detailedPokemonResponses = Http::pool(function (Pool $pool) use ($pagedSpecies) {
            return $pagedSpecies->map(function ($s) use ($pool) {
                return $pool->as($s['name'])->get("https://pokeapi.co/api/v2/pokemon/{$s['id']}");
            });
        });

        $pokemonData = [];
        foreach ($detailedPokemonResponses as $name => $res) {
            if ($res->ok()) {
                $data = $res->json();
                $pokemonData[] = [
                    'id' => $data['id'],
                    'name' => ucfirst($data['name']),
                    'image' => $data['sprites']['other']['official-artwork']['front_default'] ?? null,
                    'types' => collect($data['types'])->map(fn($t) => $t['type']['name']),
                ];
            }
        }

        $pokemonData = collect($pokemonData)->sortBy('id')->values()->all();

        // 8. MANDAMOS LOS DATOS Y LOS FILTROS ACTUALES A VUE
        return Inertia::render('pokedex/Index', [
            'pokemon' => $pokemonData,
            'currentGeneration' => (int) $generation,
            'filters' => [
                'search' => $search,
                'type' => $type
            ],
            'pagination' => [
                'current_page' => (int) $page,
                'last_page' => ceil($total / $perPage) ?: 1, // Si no hay resultados, la pag 1 es la última
                'total' => $total
            ]
        ]);
    }
}

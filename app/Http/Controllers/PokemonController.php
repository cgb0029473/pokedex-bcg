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
        // 1. Recibir parámetros (Por defecto mostramos Generación 1, Página 1)
        $generation = $request->input('generation', 1);
        $page = $request->input('page', 1);
        $perPage = 20; // Cuántos Pokémon mostrar por página

        // 2. VERIFICACIÓN DE PERMISOS (RBAC)
        // Aquí conectamos con Spatie. Si el usuario intenta cambiar la URL a ?generation=2 
        /** @var \App\Models\User $user */
        $user = $request->user();

        if (!$user->hasPermissionTo("ver generacion {$generation}")) {
            abort(403, 'No tienes la medalla necesaria para ver los Pokémon de esta generación.');
        }

        // 3. OBTENER LA LISTA DE LA GENERACIÓN DESDE POKEAPI
        // La API nos devuelve todas las "especies" de esa generación
        $response = Http::get("https://pokeapi.co/api/v2/generation/{$generation}");

        if ($response->failed()) {
            abort(500, 'Error al conectar con la PokeAPI');
        }

        $species = collect($response->json()['pokemon_species']);

        // Truco: PokeAPI a veces los devuelve desordenados. Extraemos el ID de la URL para ordenarlos.
        $species = $species->map(function ($s) {
            $urlParts = explode('/', rtrim($s['url'], '/'));
            $s['id'] = end($urlParts);
            return $s;
        })->sortBy('id')->values();

        // 4. PAGINACIÓN MANUAL EN LARAVEL
        // Cortamos la lista gigante para tomar solo los 20 que tocan en esta página
        $total = $species->count();
        $offset = ($page - 1) * $perPage;
        $pagedSpecies = $species->slice($offset, $perPage);

        // 5. OBTENER DETALLES (IMÁGENES Y TIPOS) DE FORMA CONCURRENTE
        // Como necesitamos la foto y el tipo de cada uno de esos 20 Pokémon, 
        // hacer 20 peticiones seguidas sería muy lento. ¡Http::pool las lanza todas al mismo tiempo!
        $detailedPokemonResponses = Http::pool(function (Pool $pool) use ($pagedSpecies) {
            return $pagedSpecies->map(function ($s) use ($pool) {
                return $pool->as($s['name'])->get("https://pokeapi.co/api/v2/pokemon/{$s['id']}");
            });
        });

        // 6. FORMATEAR LOS DATOS PARA VUE
        $pokemonData = [];
        foreach ($detailedPokemonResponses as $name => $res) {
            if ($res->ok()) {
                $data = $res->json();
                $pokemonData[] = [
                    'id' => $data['id'],
                    'name' => ucfirst($data['name']),
                    // Sacamos el arte oficial de alta calidad
                    'image' => $data['sprites']['other']['official-artwork']['front_default'],
                    // Mapeamos los tipos (ej. ['grass', 'poison'])
                    'types' => collect($data['types'])->map(fn($t) => $t['type']['name']),
                ];
            }
        }

        // Ordenamos el array final para que se muestren en el orden correcto en pantalla
        $pokemonData = collect($pokemonData)->sortBy('id')->values()->all();

        // 7. ENVIAR TODO AL FRONTEND (INERTIA)
        return Inertia::render('pokedex/Index', [
            'pokemon' => $pokemonData,
            'currentGeneration' => (int) $generation,
            'pagination' => [
                'current_page' => (int) $page,
                'last_page' => ceil($total / $perPage),
                'total' => $total
            ]
        ]);
    }
}

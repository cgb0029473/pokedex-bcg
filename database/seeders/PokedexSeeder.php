<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class PokedexSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Creamos los permisos (Uno por cada generación que quieras soportar)
        $permisos = ['ver generacion 1', 'ver generacion 2', 'ver generacion 3'];
        foreach ($permisos as $permiso) {
            Permission::create(['name' => $permiso]);
        }

        // 2. Creamos los Roles
        $rolMaestroPokemon = Role::create(['name' => 'Maestro Pokémon']); // Ve todo
        $rolEntrenadorNovato = Role::create(['name' => 'Entrenador Novato']); // Ve solo Gen 1

        // 3. Asignamos los permisos a los roles
        $rolMaestroPokemon->givePermissionTo(Permission::all());
        $rolEntrenadorNovato->givePermissionTo('ver generacion 1');

        // 4. Creamos usuarios de prueba para tu GitHub
        $novato = User::create([
            'name' => 'Ash Ketchum',
            'email' => 'ash@gmail.com',
            'password' => bcrypt('Admin10*')
        ]);
        $novato->assignRole('Entrenador Novato');

        $maestro = User::create([
            'name' => 'Profesor Oak',
            'email' => 'oak@gmail.com',
            'password' => bcrypt('Admin10*')
        ]);
        $maestro->assignRole('Maestro Pokémon');
    }
}

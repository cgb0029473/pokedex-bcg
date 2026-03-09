<script setup>
import { router } from '@inertiajs/vue3';

// Recibimos los datos del controlador
const props = defineProps({
    pokemon: Array,
    currentGeneration: Number,
    pagination: Object,
});

// Diccionario de colores
const typeColors = {
    normal: 'bg-gray-400',
    fire: 'bg-red-500',
    water: 'bg-blue-500',
    grass: 'bg-green-500',
    electric: 'bg-yellow-400',
    ice: 'bg-blue-200 text-gray-800',
    fighting: 'bg-red-700',
    poison: 'bg-purple-500',
    ground: 'bg-yellow-600',
    flying: 'bg-indigo-300',
    psychic: 'bg-pink-500',
    bug: 'bg-lime-500',
    rock: 'bg-yellow-700',
    ghost: 'bg-purple-700',
    dragon: 'bg-indigo-700',
    dark: 'bg-gray-800',
    steel: 'bg-gray-400',
    fairy: 'bg-pink-300 text-gray-800',
};

const getTypeColor = (type) => {
    return typeColors[type] || 'bg-gray-500 text-white';
};

// Función para cambiar de página
const changePage = (newPage) => {
    router.get(
        '/pokedex',
        {
            generation: props.currentGeneration,
            page: newPage,
        },
        { preserveScroll: true },
    );
};

// NUEVA FUNCIÓN: Cambiar de Generación
const changeGeneration = (event) => {
    const selectedGeneration = event.target.value;

    // Hacemos la petición a Laravel con la nueva generación y volvemos a la página 1
    router.get('/pokedex', {
        generation: selectedGeneration,
        page: 1,
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-100 p-8">
        <div class="mx-auto max-w-7xl">
            <div
                class="mb-8 flex flex-col items-center justify-between gap-4 rounded-xl bg-white p-6 shadow-sm md:flex-row"
            >
                <h1 class="text-3xl font-extrabold text-gray-800">
                    Pokédex <span class="text-red-500">BCG</span>
                </h1>

                <div class="flex items-center gap-3">
                    <label
                        for="generation-select"
                        class="font-bold text-gray-600"
                        >Generación:</label
                    >

                    <select
                        id="generation-select"
                        :value="currentGeneration"
                        @change="changeGeneration"
                        class="block cursor-pointer rounded-lg border border-gray-300 bg-gray-50 p-2.5 text-sm font-bold text-gray-900 focus:border-red-500 focus:ring-red-500"
                    >
                        <option value="1">Kanto (Gen 1)</option>
                        <option value="2">Johto (Gen 2)</option>
                        <option value="3">Hoenn (Gen 3)</option>
                    </select>
                </div>
            </div>

            <div
                class="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5"
            >
                <div
                    v-for="p in pokemon"
                    :key="p.id"
                    class="flex flex-col items-center overflow-hidden rounded-2xl border border-gray-100 bg-white p-4 shadow-md transition-shadow duration-300 hover:shadow-xl"
                >
                    <span
                        class="mb-2 self-start text-xs font-bold text-gray-400"
                    >
                        #{{ String(p.id).padStart(4, '0') }}
                    </span>

                    <div
                        class="mb-4 flex h-32 w-32 items-center justify-center rounded-full bg-gray-50 p-2"
                    >
                        <img
                            :src="p.image"
                            :alt="p.name"
                            class="h-full w-full object-contain drop-shadow-md"
                        />
                    </div>

                    <h2 class="mb-3 text-xl font-bold text-gray-800 capitalize">
                        {{ p.name }}
                    </h2>

                    <div class="mt-auto flex gap-2">
                        <span
                            v-for="type in p.types"
                            :key="type"
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-bold text-white uppercase shadow-sm',
                                getTypeColor(type),
                            ]"
                        >
                            {{ type }}
                        </span>
                    </div>
                </div>
            </div>

            <div
                class="mt-10 flex items-center justify-center space-x-6 rounded-xl bg-white p-4 shadow-sm"
            >
                <button
                    @click="changePage(pagination.current_page - 1)"
                    :disabled="pagination.current_page === 1"
                    class="rounded-lg bg-red-500 px-6 py-2 font-bold text-white transition-colors hover:bg-red-600 disabled:opacity-50"
                >
                    &laquo; Anterior
                </button>

                <span class="font-medium text-gray-600">
                    Página
                    <span class="font-bold text-gray-900">{{
                        pagination.current_page
                    }}</span>
                    de {{ pagination.last_page }}
                </span>

                <button
                    @click="changePage(pagination.current_page + 1)"
                    :disabled="pagination.current_page === pagination.last_page"
                    class="rounded-lg bg-red-500 px-6 py-2 font-bold text-white transition-colors hover:bg-red-600 disabled:opacity-50"
                >
                    Siguiente &raquo;
                </button>
            </div>
        </div>
    </div>
</template>

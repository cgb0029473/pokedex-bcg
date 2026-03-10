<script setup>
import { reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    pokemon: Array,
    currentGeneration: Number,
    filters: Object,
    pagination: Object,
});

// Lista de tipos para el select del filtro
const availableTypes = [
    'normal',
    'fire',
    'water',
    'grass',
    'electric',
    'ice',
    'fighting',
    'poison',
    'ground',
    'flying',
    'psychic',
    'bug',
    'rock',
    'ghost',
    'dragon',
    'dark',
    'steel',
    'fairy',
];

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

const getTypeColor = (type) => typeColors[type] || 'bg-gray-500 text-white';

// Estado reactivo para nuestros filtros (toma los valores iniciales del backend)
const filterState = reactive({
    search: props.filters.search || '',
    type: props.filters.type || '',
    generation: props.currentGeneration,
});

// FUNCIÓN MAESTRA: Envía las peticiones a Laravel cuando cambiamos algo
const applyFilters = (resetPage = true) => {
    router.get(
        '/pokedex',
        {
            generation: filterState.generation,
            search: filterState.search,
            type: filterState.type,
            page: resetPage ? 1 : props.pagination.current_page,
        },
        {
            preserveState: true, // Mantiene lo que escribiste en el input
            replace: true, // No llena el historial del navegador con cada tecla
        },
    );
};

const changePage = (newPage) => {
    router.get(
        '/pokedex',
        {
            generation: filterState.generation,
            search: filterState.search,
            type: filterState.type,
            page: newPage,
        },
        { preserveScroll: true },
    );
};

// Limpiar filtros
const clearFilters = () => {
    filterState.search = '';
    filterState.type = '';
    applyFilters();
};
</script>

<template>
    <div class="min-h-screen bg-gray-50 p-4 text-gray-800 md:p-8">
        <div class="mx-auto flex max-w-[1400px] flex-col gap-8 md:flex-row">
            <aside class="w-full flex-shrink-0 md:w-1/4 lg:w-1/5">
                <div
                    class="sticky top-8 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                >
                    <h1
                        class="mb-8 flex items-center gap-2 text-2xl font-extrabold text-gray-900"
                    >
                        Pokédex
                        <span
                            class="rounded bg-red-50 px-2 py-1 text-sm text-red-500"
                            >BCG</span
                        >
                    </h1>

                    <div class="mb-6">
                        <label
                            class="mb-2 block text-sm font-bold text-gray-700"
                            >Generación</label
                        >
                        <select
                            v-model="filterState.generation"
                            @change="applyFilters(true)"
                            class="w-full rounded-lg border-gray-300 font-medium shadow-sm focus:border-red-500 focus:ring-red-500"
                        >
                            <option value="1">Kanto (Gen 1)</option>
                            <option value="2">Johto (Gen 2)</option>
                            <option value="3">Hoenn (Gen 3)</option>
                        </select>
                    </div>

                    <div class="mb-6">
                        <label
                            class="mb-2 block text-sm font-bold text-gray-700"
                            >Buscar Pokémon</label
                        >
                        <div class="relative">
                            <input
                                type="text"
                                v-model="filterState.search"
                                @keyup.enter="applyFilters(true)"
                                placeholder="Ej. Pikachu, Charizard..."
                                class="w-full rounded-lg border-gray-300 pl-10 shadow-sm focus:border-red-500 focus:ring-red-500"
                            />
                            <svg
                                class="absolute top-2.5 left-3 h-5 w-5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                ></path>
                            </svg>
                        </div>
                    </div>

                    <div class="mb-6">
                        <label
                            class="mb-2 block text-sm font-bold text-gray-700"
                            >Filtrar por Tipo</label
                        >
                        <select
                            v-model="filterState.type"
                            @change="applyFilters(true)"
                            class="w-full rounded-lg border-gray-300 capitalize shadow-sm focus:border-red-500 focus:ring-red-500"
                        >
                            <option value="">Todos los tipos</option>
                            <option
                                v-for="type in availableTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-3">
                        <button
                            @click="applyFilters(true)"
                            class="w-full rounded-lg bg-red-500 px-4 py-2 font-bold text-white transition-colors hover:bg-red-600"
                        >
                            Buscar
                        </button>
                        <button
                            v-if="filterState.search || filterState.type"
                            @click="clearFilters"
                            class="w-full rounded-lg bg-gray-100 px-4 py-2 text-sm font-bold text-gray-700 transition-colors hover:bg-gray-200"
                        >
                            Limpiar Filtros
                        </button>
                    </div>
                </div>
            </aside>

            <main class="flex w-full flex-col md:w-3/4 lg:w-4/5">
                <div
                    v-if="pokemon.length === 0"
                    class="flex flex-grow flex-col items-center justify-center rounded-2xl border border-gray-100 bg-white p-12 text-center shadow-sm"
                >
                    <div class="mb-4 text-6xl">🔍</div>
                    <h2 class="text-2xl font-bold text-gray-700">
                        No se encontraron Pokémon
                    </h2>
                    <p class="mt-2 text-gray-500">
                        Intenta con otros filtros o revisa tu ortografía.
                    </p>
                </div>

                <div
                    v-else
                    class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <div
                        v-for="p in pokemon"
                        :key="p.id"
                        class="group flex flex-col items-center overflow-hidden rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition-all duration-300 hover:shadow-lg"
                    >
                        <span
                            class="mb-2 self-start text-xs font-black text-gray-300 transition-colors group-hover:text-red-400"
                        >
                            #{{ String(p.id).padStart(4, '0') }}
                        </span>

                        <div
                            class="mb-4 flex h-32 w-32 items-center justify-center rounded-full bg-gray-50 p-3 transition-transform duration-300 group-hover:scale-110"
                        >
                            <img
                                v-if="p.image"
                                :src="p.image"
                                :alt="p.name"
                                class="h-full w-full object-contain drop-shadow-md"
                            />
                            <div v-else class="text-xs text-gray-300">
                                Sin Imagen
                            </div>
                        </div>

                        <h2
                            class="mb-4 text-xl font-bold text-gray-800 capitalize"
                        >
                            {{ p.name }}
                        </h2>

                        <div class="mt-auto flex gap-2">
                            <span
                                v-for="type in p.types"
                                :key="type"
                                :class="[
                                    'rounded-full px-3 py-1 text-xs font-bold uppercase shadow-sm',
                                    getTypeColor(type),
                                ]"
                            >
                                {{ type }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    v-if="pagination.last_page > 1"
                    class="mt-8 flex items-center justify-between rounded-xl border border-gray-100 bg-white p-4 shadow-sm"
                >
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="pagination.current_page === 1"
                        class="rounded bg-gray-100 px-4 py-2 font-bold text-gray-700 hover:bg-gray-200 disabled:opacity-50"
                    >
                        Anterior
                    </button>
                    <span class="text-sm font-medium text-gray-500">
                        {{ pagination.total }} Resultados &mdash; Página
                        {{ pagination.current_page }} de
                        {{ pagination.last_page }}
                    </span>
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="
                            pagination.current_page === pagination.last_page
                        "
                        class="rounded bg-gray-100 px-4 py-2 font-bold text-gray-700 hover:bg-gray-200 disabled:opacity-50"
                    >
                        Siguiente
                    </button>
                </div>
            </main>
        </div>
    </div>
</template>

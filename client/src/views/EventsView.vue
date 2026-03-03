<template>
    <div class="min-h-screen flex flex-col bg-background-dark text-slate-100">
        <Navbar />

        <main class="flex-grow">
            <!-- Breadcrumb -->
            <div class="bg-background-dark border-b border-border-dark py-8">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <nav class="flex text-sm font-medium text-slate-400 mb-2">
                        <router-link
                            to="/"
                            class="hover:text-primary transition-colors"
                            >Home</router-link
                        >
                        <span class="mx-2 text-slate-600">/</span>
                        <span class="text-white">Eventos</span>
                    </nav>
                    <h1
                        class="text-3xl font-bold tracking-tight text-white sm:text-4xl"
                    >
                        Todos os Eventos
                    </h1>
                </div>
            </div>

            <!-- Content -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
                <!-- Mobile Filter Toggle -->
                <div class="lg:hidden mb-4">
                    <button
                        @click="showMobileFilters = !showMobileFilters"
                        class="flex items-center gap-2 px-4 py-2.5 rounded-lg border border-border-dark bg-surface-dark text-sm font-medium text-white hover:border-primary hover:text-primary transition-all"
                    >
                        <span class="material-symbols-outlined text-[18px]">tune</span>
                        Filtros
                        <span
                            v-if="activeFiltersCount > 0"
                            class="inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1 rounded-full bg-primary text-background-dark text-xs font-bold"
                        >
                            {{ activeFiltersCount }}
                        </span>
                        <span
                            class="material-symbols-outlined text-[18px] transition-transform duration-200"
                            :class="showMobileFilters ? 'rotate-180' : ''"
                        >
                            expand_more
                        </span>
                    </button>
                </div>

                <div class="flex flex-col lg:flex-row gap-8">
                    <!-- Sidebar Filters -->
                    <aside :class="['w-full lg:w-1/4 flex-shrink-0 space-y-6', showMobileFilters ? 'block' : 'hidden lg:block']">
                        <!-- Estado -->
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4"
                            >
                                Estado
                            </h3>
                            <select
                                v-model="filters.state"
                                @change="applyFilters"
                                class="w-full rounded-lg border-border-dark bg-surface-dark py-2.5 px-3 text-sm text-white focus:border-primary focus:ring-primary"
                            >
                                <option value="">Todos os estados</option>
                                <option
                                    v-for="state in states"
                                    :key="state"
                                    :value="state"
                                >
                                    {{ state }}
                                </option>
                            </select>
                        </div>

                        <div class="h-px w-full bg-border-dark"></div>

                        <!-- Data -->
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4"
                            >
                                Data
                            </h3>
                            <input
                                v-model="filters.date_from"
                                @change="applyFilters"
                                type="date"
                                placeholder="De"
                                class="w-full rounded-lg border-border-dark bg-surface-dark py-2.5 px-3 text-sm text-white focus:border-primary focus:ring-primary mb-2"
                            />
                            <input
                                v-model="filters.date_to"
                                @change="applyFilters"
                                type="date"
                                placeholder="Até"
                                class="w-full rounded-lg border-border-dark bg-surface-dark py-2.5 px-3 text-sm text-white focus:border-primary focus:ring-primary"
                            />
                        </div>

                        <div class="h-px w-full bg-border-dark"></div>

                        <!-- Distância -->
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4"
                            >
                                Distância
                            </h3>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="dist in distances"
                                    :key="dist.value"
                                    @click="toggleDistance(dist.value)"
                                    :class="[
                                        'px-3 py-2 rounded-lg border transition-all text-sm font-medium',
                                        filters.distance === dist.value
                                            ? 'border-primary bg-primary/10 text-primary'
                                            : 'border-border-dark bg-surface-dark text-white hover:border-primary hover:text-primary',
                                    ]"
                                >
                                    {{ dist.label }}
                                </button>
                            </div>
                        </div>

                        <div class="h-px w-full bg-border-dark"></div>

                        <!-- Preço -->
                        <div>
                            <h3
                                class="text-sm font-bold uppercase tracking-wider text-slate-400 mb-4"
                            >
                                Preço
                            </h3>
                            <div
                                class="mb-4 flex items-center justify-between text-sm text-white"
                            >
                                <span>R$ {{ filters.price_range }}</span>
                                <span>R$ 500+</span>
                            </div>
                            <input
                                v-model="filters.price_range"
                                @change="applyFilters"
                                type="range"
                                min="0"
                                max="500"
                                class="w-full accent-primary"
                            />
                        </div>

                        <button
                            @click="showMobileFilters = false"
                            class="lg:hidden w-full rounded-lg bg-primary py-3 text-sm font-bold text-background-dark transition-all hover:bg-primary-dark"
                        >
                            Ver Resultados
                        </button>

                        <button
                            @click="clearFilters"
                            class="w-full rounded-lg border border-slate-600 bg-transparent py-3 text-sm font-bold text-white transition-all hover:border-primary hover:text-primary"
                        >
                            Limpar Filtros
                        </button>
                    </aside>

                    <!-- Main Content -->
                    <div class="flex-1">
                        <!-- Header with count and sort -->
                        <div
                            class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
                        >
                            <p class="text-sm text-slate-400">
                                <span
                                    v-if="!eventsStore.loading"
                                    class="font-bold text-white"
                                >
                                    {{ eventsStore.pagination.total }}
                                </span>
                                <span v-if="!eventsStore.loading">
                                    eventos encontrados</span
                                >
                            </p>
                            <div class="flex items-center gap-3">
                                <label for="sort" class="text-sm text-slate-400"
                                    >Ordenar por:</label
                                >
                                <select
                                    id="sort"
                                    v-model="filters.sort_by"
                                    @change="applyFilters"
                                    class="rounded-lg border-border-dark bg-surface-dark py-2 pl-3 pr-8 text-sm text-white focus:border-primary focus:ring-primary"
                                >
                                    <option value="date">Data: Próximos</option>
                                    <option value="price_asc">
                                        Preço: Menor para Maior
                                    </option>
                                    <option value="price_desc">
                                        Preço: Maior para Menor
                                    </option>
                                </select>
                            </div>
                        </div>

                        <!-- Loading skeleton -->
                        <div
                            v-if="eventsStore.loading"
                            class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 mb-12"
                        >
                            <div
                                v-for="i in 9"
                                :key="i"
                                class="group relative flex flex-col overflow-hidden rounded-2xl bg-surface-dark shadow-lg ring-1 ring-white/5 animate-pulse"
                            >
                                <div
                                    class="relative aspect-[16/9] w-full overflow-hidden bg-slate-800/50"
                                ></div>
                                <div class="flex flex-1 flex-col p-5">
                                    <div
                                        class="flex items-center justify-between mb-3"
                                    >
                                        <div
                                            class="h-3 w-16 rounded bg-slate-700"
                                        ></div>
                                        <div
                                            class="h-3 w-20 rounded bg-slate-700"
                                        ></div>
                                    </div>
                                    <div
                                        class="h-6 w-3/4 rounded bg-slate-700 mb-2"
                                    ></div>
                                    <div
                                        class="h-4 w-1/2 rounded bg-slate-700/50"
                                    ></div>
                                    <div
                                        class="my-4 h-px w-full bg-border-dark"
                                    ></div>
                                    <div
                                        class="mt-auto flex items-center justify-between"
                                    >
                                        <div class="space-y-1">
                                            <div
                                                class="h-2 w-10 rounded bg-slate-700"
                                            ></div>
                                            <div
                                                class="h-5 w-16 rounded bg-slate-700"
                                            ></div>
                                        </div>
                                        <div
                                            class="h-9 w-24 rounded bg-slate-700"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Events grid -->
                        <div
                            v-else-if="eventsStore.events.length > 0"
                            class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 mb-12"
                        >
                            <EventCard
                                v-for="event in eventsStore.events"
                                :key="event.id"
                                :event="event"
                            />
                        </div>

                        <!-- Empty state -->
                        <div v-else class="text-center py-16">
                            <span
                                class="material-symbols-outlined text-6xl text-slate-600 mb-4"
                            >
                                event_busy
                            </span>
                            <p class="text-slate-400 mb-2">
                                Nenhum evento encontrado
                            </p>
                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="text-primary hover:underline"
                            >
                                Limpar filtros
                            </button>
                        </div>

                        <!-- Pagination -->
                        <div
                            v-if="eventsStore.pagination.last_page > 1"
                            class="flex items-center justify-center gap-2"
                        >
                            <button
                                @click="
                                    changePage(
                                        eventsStore.pagination.current_page - 1,
                                    )
                                "
                                :disabled="
                                    eventsStore.pagination.current_page === 1
                                "
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-border-dark bg-surface-dark text-slate-400 hover:border-primary hover:text-primary transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="material-symbols-outlined"
                                    >chevron_left</span
                                >
                            </button>

                            <button
                                v-for="page in visiblePages"
                                :key="page"
                                @click="changePage(page)"
                                :class="[
                                    'flex h-10 w-10 items-center justify-center rounded-lg font-bold transition-all',
                                    page === eventsStore.pagination.current_page
                                        ? 'bg-primary text-background-dark'
                                        : 'border border-border-dark bg-surface-dark text-slate-400 hover:border-primary hover:text-white',
                                ]"
                            >
                                {{ page }}
                            </button>

                            <button
                                @click="
                                    changePage(
                                        eventsStore.pagination.current_page + 1,
                                    )
                                "
                                :disabled="
                                    eventsStore.pagination.current_page ===
                                    eventsStore.pagination.last_page
                                "
                                class="flex h-10 w-10 items-center justify-center rounded-lg border border-border-dark bg-surface-dark text-slate-400 hover:border-primary hover:text-primary transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="material-symbols-outlined"
                                    >chevron_right</span
                                >
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute } from "vue-router";
import axios from "@/api/axios";
import { useEventsStore } from "../stores/events";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import EventCard from "../components/EventCard.vue";

const route = useRoute();
const eventsStore = useEventsStore();

const filters = ref({
    search: "",
    state: "",
    date_from: "",
    date_to: "",
    distance: "",
    price_range: 500,
    sort_by: "date",
});

const states = ref([]);

const distances = ref([
    { value: "5", label: "5km" },
    { value: "10", label: "10km" },
    { value: "21", label: "21km" },
    { value: "42", label: "42km" },
]);

const showMobileFilters = ref(false);

const hasActiveFilters = computed(() => {
    return (
        filters.value.search ||
        filters.value.state ||
        filters.value.date_from ||
        filters.value.date_to ||
        filters.value.distance ||
        filters.value.price_range < 500
    );
});

const activeFiltersCount = computed(() => {
    let count = 0;
    if (filters.value.search) count++;
    if (filters.value.state) count++;
    if (filters.value.date_from || filters.value.date_to) count++;
    if (filters.value.distance) count++;
    if (filters.value.price_range < 500) count++;
    return count;
});

const visiblePages = computed(() => {
    const current = eventsStore.pagination.current_page;
    const last = eventsStore.pagination.last_page;
    const delta = 2;
    const range = [];
    const left = current - delta;
    const right = current + delta;

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= left && i <= right)) {
            range.push(i);
        }
    }

    return range;
});

onMounted(async () => {
    // Buscar estados disponíveis
    await fetchStates();

    // Pegar query param da URL se vier de busca do Navbar
    const urlSearch = route.query.search;
    if (urlSearch) {
        filters.value.search = urlSearch;
    }
    await fetchEvents();
});

async function fetchStates() {
    try {
        const response = await axios.get(
            "/events/states",
        );
        states.value = response.data;
    } catch (error) {
        console.error("Erro ao buscar estados:", error);
    }
}

async function fetchEvents(page = 1) {
    try {
        const params = {
            page,
            per_page: 9,
        };

        if (filters.value.search) {
            params.search = filters.value.search;
        }
        if (filters.value.state) {
            params.state = filters.value.state;
        }
        if (filters.value.date_from) {
            params.date_from = filters.value.date_from;
        }
        if (filters.value.date_to) {
            params.date_to = filters.value.date_to;
        }
        if (filters.value.distance) {
            params.distance = filters.value.distance;
        }
        if (filters.value.price_range < 500) {
            params.max_price = filters.value.price_range;
        }
        if (filters.value.sort_by) {
            params.sort_by = filters.value.sort_by;
        }

        await eventsStore.fetchEvents(params);
    } catch (error) {
        console.error("Erro ao carregar eventos:", error);
    }
}

function applyFilters() {
    fetchEvents(1);
}

function toggleDistance(value) {
    // Toggle: se já está selecionado, desmarca; senão, seleciona
    filters.value.distance = filters.value.distance === value ? "" : value;
    applyFilters();
}

function clearFilters() {
    filters.value.search = "";
    filters.value.state = "";
    filters.value.date_from = "";
    filters.value.date_to = "";
    filters.value.distance = "";
    filters.value.price_range = 500;
    filters.value.sort_by = "date";
    fetchEvents(1);
}

function changePage(page) {
    if (page >= 1 && page <= eventsStore.pagination.last_page) {
        fetchEvents(page);
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
}
</script>

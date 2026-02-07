<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Lista de Eventos</h1>
                <p class="text-text-muted text-sm mt-1">
                    Gerencie todos os eventos esportivos da plataforma.
                </p>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-card-bg border border-surface-elevated rounded-xl p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-xl"
                        >
                            search
                        </span>
                        <input
                            v-model="filters.search"
                            type="text"
                            placeholder="Buscar por título"
                            class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                            @input="debouncedSearch"
                        />
                    </div>
                </div>

                <!-- Status Filter -->
                <div>
                    <select
                        v-model="filters.status"
                        class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-primary transition-colors"
                        @change="fetchEvents"
                    >
                        <option value="">Todos os status</option>
                        <option value="ativo">Ativos</option>
                        <option value="inativo">Inativos</option>
                    </select>
                </div>
            </div>

            <!-- Clear Filters -->
            <button
                v-if="hasActiveFilters"
                @click="clearFilters"
                class="mt-3 text-sm text-text-muted hover:text-white transition-colors flex items-center gap-1"
            >
                <span class="material-symbols-outlined text-[18px]">
                    close
                </span>
                Limpar Filtros
            </button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-20">
            <div class="text-center">
                <div
                    class="inline-block w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4"
                ></div>
                <p class="text-text-muted">Carregando eventos...</p>
            </div>
        </div>

        <!-- Events Grid -->
        <div v-else-if="events.length > 0" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div
                    v-for="event in events"
                    :key="event.id"
                    class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden hover:border-primary transition-all duration-300 group"
                >
                    <!-- Banner -->
                    <div
                        class="relative h-40 bg-gradient-to-br from-surface to-surface-elevated overflow-hidden"
                    >
                        <img
                            v-if="event.banner_url"
                            :src="event.banner_url"
                            :alt="event.title"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center"
                        >
                            <span
                                class="material-symbols-outlined text-text-muted text-6xl"
                            >
                                directions_run
                            </span>
                        </div>
                        <!-- Status Badge -->
                        <div class="absolute top-3 right-3">
                            <span
                                :class="[
                                    'px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wide',
                                    getStatusClass(getVisualStatus(event)),
                                ]"
                            >
                                {{ getVisualStatus(event) }}
                            </span>
                        </div>
                    </div>

                    <!-- Content -->
                    <div class="p-4 space-y-3">
                        <!-- Title -->
                        <h3
                            class="text-lg font-semibold text-white line-clamp-1 group-hover:text-primary transition-colors"
                        >
                            {{ event.title }}
                        </h3>

                        <!-- Info -->
                        <div class="space-y-2 text-sm text-text-secondary">
                            <!-- Location -->
                            <div class="flex items-start gap-2">
                                <span
                                    class="material-symbols-outlined text-text-muted text-[18px] mt-0.5"
                                >
                                    location_on
                                </span>
                                <span class="line-clamp-1">
                                    {{ event.city }} - {{ event.venue }}
                                </span>
                            </div>

                            <!-- Date -->
                            <div class="flex items-center gap-2">
                                <span
                                    class="material-symbols-outlined text-text-muted text-[18px]"
                                >
                                    calendar_today
                                </span>
                                <span>{{ formatDate(event.date_start) }}</span>
                            </div>

                            <!-- Distance -->
                            <div
                                v-if="event.meta?.distance"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="material-symbols-outlined text-text-muted text-[18px]"
                                >
                                    route
                                </span>
                                <span>{{ event.meta.distance }}</span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <button
                                @click="viewDetails(event.id)"
                                class="px-3 py-2 bg-surface hover:bg-surface-elevated border border-surface-elevated rounded-lg text-white text-sm font-medium transition-colors"
                            >
                                Detalhes
                            </button>
                            <button
                                @click="viewDashboard(event.id)"
                                class="px-3 py-2 bg-primary hover:bg-primary/90 rounded-lg text-white text-sm font-semibold transition-colors"
                            >
                                Dashboard
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pagination -->
            <div
                v-if="pagination.last_page > 1"
                class="flex items-center justify-between"
            >
                <p class="text-sm text-text-muted">
                    Mostrando
                    <span class="font-medium text-white">
                        {{ pagination.from }}
                    </span>
                    até
                    <span class="font-medium text-white">
                        {{ pagination.to }}
                    </span>
                    de
                    <span class="font-medium text-white">
                        {{ pagination.total }}
                    </span>
                    eventos
                </p>

                <div class="flex items-center gap-2">
                    <!-- Previous -->
                    <button
                        @click="changePage(pagination.current_page - 1)"
                        :disabled="!pagination.prev"
                        :class="[
                            'w-10 h-10 flex items-center justify-center rounded-lg border transition-colors',
                            pagination.prev
                                ? 'border-surface-elevated bg-surface hover:bg-surface-elevated text-white'
                                : 'border-surface bg-surface text-text-muted cursor-not-allowed',
                        ]"
                    >
                        <span class="material-symbols-outlined text-[20px]">
                            chevron_left
                        </span>
                    </button>

                    <!-- Page Numbers -->
                    <button
                        v-for="page in visiblePages"
                        :key="page"
                        @click="changePage(page)"
                        :class="[
                            'w-10 h-10 flex items-center justify-center rounded-lg border text-sm font-medium transition-colors',
                            page === pagination.current_page
                                ? 'bg-primary border-primary text-white'
                                : 'border-surface-elevated bg-surface hover:bg-surface-elevated text-white',
                        ]"
                    >
                        {{ page }}
                    </button>

                    <!-- Next -->
                    <button
                        @click="changePage(pagination.current_page + 1)"
                        :disabled="!pagination.next"
                        :class="[
                            'w-10 h-10 flex items-center justify-center rounded-lg border transition-colors',
                            pagination.next
                                ? 'border-surface-elevated bg-surface hover:bg-surface-elevated text-white'
                                : 'border-surface bg-surface text-text-muted cursor-not-allowed',
                        ]"
                    >
                        <span class="material-symbols-outlined text-[20px]">
                            chevron_right
                        </span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-col items-center justify-center py-20 text-center"
        >
            <span
                class="material-symbols-outlined text-text-muted text-6xl mb-4"
            >
                event_busy
            </span>
            <h3 class="text-xl font-semibold text-white mb-2">
                Nenhum evento encontrado
            </h3>
            <p class="text-text-muted">
                {{
                    hasActiveFilters
                        ? "Tente ajustar os filtros de busca."
                        : "Ainda não há eventos cadastrados."
                }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "@/api/axios";
import { EVENT_STATUS_OPTIONS } from "@/constants/eventStatus";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";
import { useEventStatus } from "@/composables/useEventStatus";

const router = useRouter();
const { getVisualStatus, getStatusClass } = useEventStatus();

const isLoading = ref(true);
const events = ref([]);
const filters = ref({
    search: "",
    status: "",
});

const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 20,
    total: 0,
    from: 0,
    to: 0,
    prev: null,
    next: null,
});

// Computed
const hasActiveFilters = computed(() => {
    return filters.value.search || filters.value.status;
});

const visiblePages = computed(() => {
    const pages = [];
    const current = pagination.value.current_page;
    const last = pagination.value.last_page;

    // Mostrar no máximo 5 páginas
    let start = Math.max(1, current - 2);
    let end = Math.min(last, current + 2);

    // Ajustar para sempre mostrar 5 páginas se possível
    if (end - start < 4) {
        if (start === 1) {
            end = Math.min(last, start + 4);
        } else {
            start = Math.max(1, end - 4);
        }
    }

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }

    return pages;
});

// Methods
const fetchEvents = async (page = 1) => {
    isLoading.value = true;
    try {
        const params = {
            page,
            per_page: 20,
        };

        if (filters.value.search) {
            params.search = filters.value.search;
        }

        if (filters.value.status) {
            params.status = filters.value.status;
        }

        const response = await api.get(API_ENDPOINTS.ORGANIZER.EVENTS, {
            params,
        });

        events.value = response.data.data;
        pagination.value = {
            current_page: response.data.meta.current_page,
            last_page: response.data.meta.last_page,
            per_page: response.data.meta.per_page,
            total: response.data.meta.total,
            from: response.data.meta.from,
            to: response.data.meta.to,
            prev: response.data.links.prev,
            next: response.data.links.next,
        };
    } catch (error) {
        console.error("Erro ao buscar eventos:", error);
    } finally {
        isLoading.value = false;
    }
};

let searchTimeout;
const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        fetchEvents(1);
    }, 500);
};

const clearFilters = () => {
    filters.value.search = "";
    filters.value.status = "";
    fetchEvents(1);
};

const changePage = (page) => {
    if (page >= 1 && page <= pagination.value.last_page) {
        fetchEvents(page);
        window.scrollTo({ top: 0, behavior: "smooth" });
    }
};

const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const viewDetails = (eventId) => {
    router.push(`/organizer/events/${eventId}`);
};

const viewDashboard = (eventId) => {
    router.push(`/organizer/events/${eventId}/dashboard`);
};

onMounted(() => {
    fetchEvents();
});
</script>

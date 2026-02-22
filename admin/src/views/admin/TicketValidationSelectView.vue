<template>
    <div class="space-y-6">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm">
            <span class="text-primary">Validação</span>
            <span class="text-text-muted">/</span>
            <span class="text-text-muted">Selecionar Evento</span>
        </nav>

        <!-- Header -->
        <div
            class="flex flex-col md:flex-row md:items-center justify-between gap-6"
        >
            <div>
                <h1 class="text-3xl font-bold text-white tracking-tight mb-2">
                    Selecionar Evento para Validação
                </h1>
                <p class="text-text-muted">
                    Escolha um evento ativo para iniciar o processo de check-in.
                </p>
            </div>
        </div>

        <!-- Search -->
        <div class="relative w-full">
            <div
                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
            >
                <span class="material-symbols-outlined text-text-muted"
                    >search</span
                >
            </div>
            <input
                v-model="searchQuery"
                type="text"
                placeholder="Buscar por nome do evento..."
                class="block w-full pl-12 pr-4 py-4 bg-card-bg border border-surface-elevated rounded-xl text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
            />
        </div>

        <!-- Loading State -->
        <div
            v-if="isLoading"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
            <div
                v-for="n in 6"
                :key="n"
                class="bg-card-bg border border-surface-elevated rounded-xl p-6 animate-pulse"
            >
                <div class="flex justify-between items-start mb-4">
                    <div
                        class="w-12 h-12 rounded-lg bg-surface border border-surface-elevated"
                    ></div>
                    <div
                        class="h-6 w-16 bg-surface rounded-full border border-surface-elevated"
                    ></div>
                </div>
                <div class="h-6 bg-surface rounded w-3/4 mb-2"></div>
                <div class="space-y-2 mb-6">
                    <div class="h-4 bg-surface rounded w-1/2"></div>
                    <div class="h-4 bg-surface rounded w-2/3"></div>
                </div>
                <div class="h-12 bg-surface rounded-lg"></div>
            </div>
        </div>

        <!-- Events Grid -->
        <div
            v-else-if="filteredEvents.length > 0"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
        >
            <div
                v-for="event in filteredEvents"
                :key="event.id"
                class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden hover:border-primary/50 transition-all group relative flex flex-col h-full"
            >
                <div class="p-6 flex flex-col h-full">
                    <!-- Icon and Badge -->
                    <div class="flex justify-between items-start mb-4">
                        <div
                            class="w-12 h-12 rounded-lg bg-background border border-surface-elevated flex items-center justify-center"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-2xl"
                                >{{ getEventIcon(event.title) }}</span
                            >
                        </div>
                        <span
                            class="px-2.5 py-0.5 rounded-full bg-primary/10 text-primary text-xs font-bold border border-primary/20 shadow-[0_0_10px_rgba(0,230,118,0.3)]"
                        >
                            Ativo
                        </span>
                    </div>

                    <!-- Event Title -->
                    <h3
                        class="text-xl font-bold text-white mb-2 group-hover:text-primary transition-colors"
                    >
                        {{ event.title }}
                    </h3>

                    <!-- Event Details -->
                    <div class="space-y-2 mb-6">
                        <div
                            class="flex items-center gap-2 text-text-muted text-sm"
                        >
                            <span class="material-symbols-outlined text-base"
                                >calendar_today</span
                            >
                            <span>{{ formatEventDate(event.date_start) }}</span>
                        </div>
                        <div
                            class="flex items-center gap-2 text-text-muted text-sm"
                        >
                            <span class="material-symbols-outlined text-base"
                                >location_on</span
                            >
                            <span>{{ event.venue }}, {{ event.city }}</span>
                        </div>
                    </div>

                    <!-- Action Button -->
                    <div class="mt-auto">
                        <button
                            @click="startValidation(event.id)"
                            class="w-full py-3 bg-primary text-black text-background font-bold rounded-lg hover:bg-opacity-90 transition-all flex items-center justify-center gap-2 shadow-[0_0_20px_rgba(0,230,118,0.4)] hover:shadow-[0_0_30px_rgba(0,230,118,0.6)]"
                        >
                            <span class="material-symbols-outlined"
                                >qr_code_scanner</span
                            >
                            Iniciar Validação
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else
            class="flex flex-col items-center justify-center py-16 text-center"
        >
            <div
                class="w-20 h-20 rounded-full bg-surface/50 border border-surface-elevated flex items-center justify-center mb-6"
            >
                <span class="material-symbols-outlined text-text-muted text-4xl"
                    >event_busy</span
                >
            </div>
            <h3 class="text-xl font-bold text-white mb-2">
                Nenhum evento encontrado
            </h3>
            <p class="text-text-muted mb-6">
                {{
                    searchQuery
                        ? "Tente ajustar sua busca"
                        : "Não há eventos ativos disponíveis no momento"
                }}
            </p>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import axios from "@/api/axios";
import { useToast } from "@/composables/useToast";

const router = useRouter();
const authStore = useAuthStore();
const toast = useToast();

const events = ref([]);
const searchQuery = ref("");
const isLoading = ref(true);

// Computed
const filteredEvents = computed(() => {
    if (!searchQuery.value) return events.value;

    const query = searchQuery.value.toLowerCase();
    return events.value.filter(
        (event) =>
            event.title.toLowerCase().includes(query) ||
            event.city.toLowerCase().includes(query) ||
            event.venue.toLowerCase().includes(query),
    );
});

// Methods
const fetchEvents = async () => {
    isLoading.value = true;
    try {
        const endpoint = authStore.isSuperAdmin
            ? "/admin/events"
            : "/organizer/events";

        const response = await axios.get(endpoint, {
            params: {
                status: "ativo",
                per_page: 100,
            },
        });

        events.value = response.data.data;
    } catch (error) {
        console.error("Erro ao buscar eventos:", error);
        toast.error("Erro ao carregar eventos");
    } finally {
        isLoading.value = false;
    }
};

const formatEventDate = (dateString) => {
    if (!dateString) return "";

    const date = new Date(dateString);
    const day = String(date.getDate()).padStart(2, "0");
    const months = [
        "Jan",
        "Fev",
        "Mar",
        "Abr",
        "Mai",
        "Jun",
        "Jul",
        "Ago",
        "Set",
        "Out",
        "Nov",
        "Dez",
    ];
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");

    return `${day} ${month} ${year} • ${hours}:${minutes}`;
};

const getEventIcon = (title) => {
    const titleLower = title.toLowerCase();

    if (
        titleLower.includes("maratona") ||
        titleLower.includes("corrida") ||
        titleLower.includes("run")
    ) {
        return "directions_run";
    }
    if (titleLower.includes("ciclismo") || titleLower.includes("bike")) {
        return "pedal_bike";
    }
    if (
        titleLower.includes("natação") ||
        titleLower.includes("nataç") ||
        titleLower.includes("swim")
    ) {
        return "pool";
    }
    if (titleLower.includes("crossfit") || titleLower.includes("fitness")) {
        return "fitness_center";
    }
    if (titleLower.includes("trail") || titleLower.includes("montanha")) {
        return "hiking";
    }
    if (titleLower.includes("night") || titleLower.includes("noturna")) {
        return "nightlife";
    }

    // Default
    return "directions_run";
};

const startValidation = (eventId) => {
    const basePath = authStore.isSuperAdmin ? "/admin" : "/organizer";
    router.push(`${basePath}/validate-tickets/${eventId}`);
};

// Lifecycle
onMounted(() => {
    fetchEvents();
});
</script>

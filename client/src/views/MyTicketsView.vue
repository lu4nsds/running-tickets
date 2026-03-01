<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar />

        <main class="max-w-5xl mx-auto px-4 py-10">
            <!-- Header -->
            <div class="flex items-center gap-4 mb-8">
                <h1 class="text-3xl font-black text-white uppercase tracking-tight">
                    Meus Ingressos
                </h1>
                <span
                    v-if="!loading && totalTickets > 0"
                    class="inline-flex flex-col items-center justify-center px-3 py-1 rounded-full bg-primary/10 border border-primary/30 text-primary text-xs font-bold text-center"
                >
                    {{ totalTickets }} Ingresso{{ totalTickets !== 1 ? "s" : "" }}
                </span>
            </div>

            <!-- Loading Skeleton -->
            <div v-if="loading" class="space-y-4">
                <div
                    v-for="i in 3"
                    :key="i"
                    class="bg-surface-dark rounded-xl border border-border-dark overflow-hidden animate-pulse flex"
                >
                    <div class="w-32 h-28 bg-slate-700 flex-shrink-0"></div>
                    <div class="flex-1 p-5 space-y-3">
                        <div class="h-5 bg-slate-700 rounded w-1/2"></div>
                        <div class="h-3 bg-slate-700/60 rounded w-1/3"></div>
                        <div class="h-3 bg-slate-700/40 rounded w-1/4"></div>
                    </div>
                    <div class="p-5 flex items-center">
                        <div class="h-10 bg-slate-700 rounded-lg w-44"></div>
                    </div>
                </div>
            </div>

            <template v-else>
                <!-- Estado vazio geral -->
                <div
                    v-if="tickets.length === 0"
                    class="flex flex-col items-center justify-center py-24 text-center"
                >
                    <div
                        class="size-20 rounded-full bg-surface-dark border border-border-dark flex items-center justify-center mb-6"
                    >
                        <svg class="w-10 h-10 text-slate-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z" />
                        </svg>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2">Nenhum ingresso encontrado</h2>
                    <p class="text-slate-400 mb-6 max-w-sm">
                        Você ainda não comprou ingressos. Explore os eventos disponíveis!
                    </p>
                    <router-link
                        :to="{ name: 'events' }"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        Explorar Eventos
                    </router-link>
                </div>

                <template v-else>
                    <!-- Tabs -->
                    <div class="flex border-b border-border-dark mb-6">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            @click="activeTab = tab.key"
                            class="px-5 py-3 text-sm font-semibold transition-colors relative"
                            :class="
                                activeTab === tab.key
                                    ? 'text-primary'
                                    : 'text-slate-400 hover:text-white'
                            "
                        >
                            {{ tab.label }}
                            <span
                                v-if="tabCount(tab.key) > 0"
                                class="ml-2 text-xs px-1.5 py-0.5 rounded-full"
                                :class="
                                    activeTab === tab.key
                                        ? 'bg-primary/20 text-primary'
                                        : 'bg-slate-700 text-slate-400'
                                "
                            >
                                {{ tabCount(tab.key) }}
                            </span>
                            <span
                                v-if="activeTab === tab.key"
                                class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-t"
                            ></span>
                        </button>
                    </div>

                    <!-- Estado vazio por tab -->
                    <div
                        v-if="filteredGroups.length === 0"
                        class="text-center py-16 text-slate-400"
                    >
                        <p>Nenhum ingresso em "{{ activeTab === 'upcoming' ? 'Próximas Corridas' : 'Histórico de Provas' }}".</p>
                    </div>

                    <!-- Lista de eventos agrupados -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="group in filteredGroups"
                            :key="group.event.id"
                            class="bg-surface-dark rounded-xl border border-border-dark overflow-hidden flex flex-col sm:flex-row"
                        >
                            <!-- Imagem com badge de data -->
                            <div class="relative w-full sm:w-36 h-36 sm:h-auto flex-shrink-0 bg-slate-800 flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="group.event.banner_url"
                                    :src="group.event.banner_url"
                                    :alt="group.event.title"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center text-slate-600 gap-1 w-full h-full"
                                >
                                    <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                                    </svg>
                                </div>

                                <!-- Badge de data -->
                                <div
                                    v-if="group.event.date_start"
                                    class="absolute top-2 left-2 bg-background-dark/90 backdrop-blur-sm rounded-lg px-2 py-1 text-center leading-none"
                                >
                                    <div class="text-[10px] font-bold text-primary uppercase">
                                        {{ formatMonth(group.event.date_start) }}
                                    </div>
                                    <div class="text-lg font-black text-white">
                                        {{ formatDay(group.event.date_start) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Conteúdo -->
                            <div class="flex-1 p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-white mb-1 truncate">
                                        {{ group.event.title }}
                                    </h3>
                                    <p v-if="group.event.city" class="text-xs text-slate-400 flex items-center gap-1 mb-2">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                        </svg>
                                        {{ group.event.city }}{{ group.event.state ? `, ${group.event.state}` : "" }}
                                    </p>
                                    <!-- Badge de quantidade de ingressos -->
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-primary/10 border border-primary/20 text-primary text-xs font-bold">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z" />
                                            </svg>
                                            {{ group.tickets.length }} Ingresso{{ group.tickets.length !== 1 ? "s" : "" }}
                                        </span>
                                        <span
                                            v-for="cat in uniqueCategories(group.tickets)"
                                            :key="cat"
                                            class="px-2 py-0.5 rounded bg-slate-700 text-slate-300 text-xs"
                                        >
                                            {{ cat }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Botão Ver Comprovante -->
                                <router-link
                                    :to="{ name: 'event-tickets', params: { eventId: group.event.id } }"
                                    class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-background-dark font-bold text-sm rounded-lg hover:bg-primary/90 transition-colors whitespace-nowrap flex-shrink-0"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zm7-2a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1zM3 12a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zm5-2a1 1 0 011-1h3a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 01-1-1zm4 2a1 1 0 102 0 1 1 0 00-2 0z" clip-rule="evenodd" />
                                    </svg>
                                    Ver Comprovante
                                </router-link>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import api from "../api/axios";

const loading = ref(true);
const tickets = ref([]);
const activeTab = ref("upcoming");

const tabs = [
    { key: "upcoming", label: "Próximas Corridas" },
    { key: "past", label: "Histórico de Provas" },
];

const totalTickets = computed(() => tickets.value.length);

// Agrupa tickets por evento
const groupedByEvent = computed(() => {
    const map = {};
    for (const item of tickets.value) {
        const id = item.event.id;
        if (!map[id]) {
            map[id] = { event: item.event, tickets: [] };
        }
        map[id].tickets.push(item);
    }
    return Object.values(map);
});

function isUpcoming(group) {
    if (!group.event?.date_start) return true;
    return new Date(group.event.date_start) >= new Date();
}

const filteredGroups = computed(() =>
    groupedByEvent.value.filter((g) =>
        activeTab.value === "upcoming" ? isUpcoming(g) : !isUpcoming(g),
    ),
);

function tabCount(key) {
    return groupedByEvent.value.filter((g) =>
        key === "upcoming" ? isUpcoming(g) : !isUpcoming(g),
    ).length;
}

function uniqueCategories(ticketList) {
    const names = ticketList
        .map((t) => {
            const dist = t.category?.distance ? `${parseFloat(t.category.distance)}km` : null;
            const name = t.category?.name || t.ticket_type?.name || null;
            return dist && name ? `${dist} – ${name}` : name || dist;
        })
        .filter(Boolean);
    return [...new Set(names)];
}

function formatMonth(dateStr) {
    return new Date(dateStr).toLocaleDateString("pt-BR", { month: "short" }).replace(".", "");
}

function formatDay(dateStr) {
    return new Date(dateStr).getDate().toString().padStart(2, "0");
}

onMounted(async () => {
    try {
        const { data } = await api.get("/tickets");
        tickets.value = data.data || [];
    } catch (err) {
        console.error("Erro ao buscar ingressos:", err);
    } finally {
        loading.value = false;
    }
});
</script>

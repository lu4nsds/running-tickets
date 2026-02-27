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
                    v-if="!loading && confirmedCount > 0"
                    class="px-3 py-1 rounded-full bg-primary/10 border border-primary/30 text-primary text-xs font-bold"
                >
                    {{ confirmedCount }} Inscrição{{ confirmedCount !== 1 ? "s" : "" }} Confirmada{{ confirmedCount !== 1 ? "s" : "" }}
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
                        v-if="filteredTickets.length === 0"
                        class="text-center py-16 text-slate-400"
                    >
                        <p>Nenhum ingresso em "{{ activeTab === 'upcoming' ? 'Próximas Corridas' : 'Histórico de Provas' }}".</p>
                    </div>

                    <!-- Lista de ingressos -->
                    <div v-else class="space-y-4">
                        <div
                            v-for="item in filteredTickets"
                            :key="item.ticket.code"
                            class="bg-surface-dark rounded-xl border border-border-dark overflow-hidden flex flex-col sm:flex-row"
                        >
                            <!-- Imagem ou placeholder com badge de data -->
                            <div class="relative w-full sm:w-32 h-32 sm:h-auto flex-shrink-0 bg-slate-800 flex items-center justify-center overflow-hidden">
                                <img
                                    v-if="item.event.banner_url"
                                    :src="item.event.banner_url"
                                    :alt="item.event.title"
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

                                <!-- Badge de data sobre a imagem -->
                                <div
                                    v-if="item.event.date_start"
                                    class="absolute top-2 left-2 bg-background-dark/90 backdrop-blur-sm rounded-lg px-2 py-1 text-center leading-none"
                                >
                                    <div class="text-[10px] font-bold text-primary uppercase">
                                        {{ formatMonth(item.event.date_start) }}
                                    </div>
                                    <div class="text-lg font-black text-white">
                                        {{ formatDay(item.event.date_start) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Conteúdo -->
                            <div class="flex-1 p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-white mb-1 truncate">
                                        {{ item.event.title }}
                                    </h3>
                                    <p class="text-sm font-semibold text-primary mb-2">
                                        <span v-if="item.category?.distance">
                                            {{ formatDistance(item.category.distance) }} –
                                        </span>
                                        {{ item.category?.name || item.ticket_type.name }}
                                    </p>
                                    <p class="text-xs text-slate-400 flex items-center gap-1.5 uppercase tracking-wide">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                        </svg>
                                        Participante: {{ item.participant?.name || "—" }}
                                    </p>
                                </div>

                                <!-- Botão QR -->
                                <button
                                    @click="openQr(item)"
                                    class="flex items-center justify-center gap-2 px-5 py-2.5 bg-primary text-background-dark font-bold text-sm rounded-lg hover:bg-primary/90 transition-colors whitespace-nowrap flex-shrink-0"
                                >
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zm7-2a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1zM3 12a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zm5-2a1 1 0 011-1h3a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 01-1-1zm4 2a1 1 0 102 0 1 1 0 00-2 0z" clip-rule="evenodd" />
                                    </svg>
                                    Ver Comprovante / QR Code
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </template>
        </main>

        <!-- Modal do QR Code -->
        <Teleport to="body">
            <div
                v-if="qrModal.open"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                @click.self="closeQr"
            >
                <div class="bg-surface-dark border border-border-dark rounded-2xl p-6 max-w-sm w-full shadow-2xl">
                    <!-- Cabeçalho -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-white">Comprovante</h3>
                            <p class="text-sm text-slate-400">{{ qrModal.item?.event?.title }}</p>
                        </div>
                        <button @click="closeQr" class="text-slate-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    <!-- QR Code -->
                    <div class="flex items-center justify-center bg-white rounded-xl p-4 mb-4">
                        <img
                            v-if="qrModal.item?.ticket?.qr_url"
                            :src="qrModal.item.ticket.qr_url"
                            :alt="`QR Code`"
                            class="size-56 object-contain"
                        />
                        <div v-else class="size-56 flex flex-col items-center justify-center gap-2">
                            <svg class="w-12 h-12 text-slate-300" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-center text-slate-400">QR Code sendo gerado...</p>
                        </div>
                    </div>

                    <!-- Detalhes -->
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-slate-400">Participante</span>
                            <span class="text-white font-semibold">{{ qrModal.item?.participant?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Categoria</span>
                            <span class="text-white">
                                <span v-if="qrModal.item?.category?.distance">{{ formatDistance(qrModal.item.category.distance) }} – </span>{{ qrModal.item?.category?.name || qrModal.item?.ticket_type?.name }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Código</span>
                            <span class="text-white font-mono text-xs">{{ qrModal.item?.ticket?.code?.slice(0, 8).toUpperCase() }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>

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

const qrModal = ref({ open: false, item: null });

const confirmedCount = computed(
    () => tickets.value.filter((t) => t.ticket.status === "active").length,
);

function isUpcoming(item) {
    if (!item.event?.date_start) return true;
    return new Date(item.event.date_start) >= new Date();
}

const filteredTickets = computed(() =>
    tickets.value.filter((t) =>
        activeTab.value === "upcoming" ? isUpcoming(t) : !isUpcoming(t),
    ),
);

function tabCount(key) {
    return tickets.value.filter((t) =>
        key === "upcoming" ? isUpcoming(t) : !isUpcoming(t),
    ).length;
}

function formatMonth(dateStr) {
    return new Date(dateStr).toLocaleDateString("pt-BR", { month: "short" }).replace(".", "");
}

function formatDay(dateStr) {
    return new Date(dateStr).getDate().toString().padStart(2, "0");
}

function formatDistance(distance) {
    const n = parseFloat(distance);
    return Number.isInteger(n) ? `${n}km` : `${n}km`;
}

function openQr(item) {
    qrModal.value = { open: true, item };
}

function closeQr() {
    qrModal.value = { open: false, item: null };
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

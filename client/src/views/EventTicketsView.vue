<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar />

        <main class="max-w-5xl mx-auto px-4 py-8">

            <!-- Breadcrumb + Voltar -->
            <div class="flex items-center justify-between mb-6 flex-wrap gap-3">
                <nav class="flex items-center gap-2 text-sm text-slate-400">
                    <router-link to="/" class="hover:text-white transition-colors">Home</router-link>
                    <span class="text-slate-600">›</span>
                    <router-link to="/meus-ingressos" class="hover:text-white transition-colors">Meus Ingressos</router-link>
                    <span class="text-slate-600">›</span>
                    <span class="text-slate-200 truncate max-w-[180px] sm:max-w-xs">
                        {{ eventData?.title || "Evento" }}
                    </span>
                </nav>

                <router-link
                    to="/meus-ingressos"
                    class="flex items-center gap-1.5 text-sm text-primary hover:text-primary/80 font-semibold transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar para Meus Ingressos
                </router-link>
            </div>

            <!-- Loading state -->
            <div v-if="loading" class="space-y-6">
                <div class="rounded-2xl overflow-hidden animate-pulse">
                    <div class="h-44 bg-slate-800"></div>
                    <div class="bg-surface-dark p-6 space-y-3">
                        <div class="h-7 bg-slate-700 rounded w-1/2"></div>
                        <div class="h-4 bg-slate-700/60 rounded w-1/3"></div>
                    </div>
                </div>
                <div class="space-y-3">
                    <div v-for="i in 3" :key="i" class="bg-surface-dark rounded-xl h-20 animate-pulse"></div>
                </div>
            </div>

            <template v-else-if="eventData">
                <!-- Hero do evento -->
                <div class="rounded-2xl overflow-hidden border border-border-dark mb-8">
                    <!-- Imagem de capa -->
                    <div class="relative h-44 bg-slate-800 flex items-center justify-center overflow-hidden">
                        <img
                            v-if="eventData.banner_url"
                            :src="eventData.banner_url"
                            :alt="eventData.title"
                            class="w-full h-full object-cover"
                        />
                        <div v-else class="absolute inset-0 flex items-center justify-center">
                            <svg class="w-16 h-16 text-slate-700" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <!-- Overlay gradiente -->
                        <div class="absolute inset-0 bg-gradient-to-t from-surface-dark via-transparent to-transparent"></div>
                    </div>

                    <!-- Infos do evento -->
                    <div class="bg-surface-dark px-6 py-5 flex flex-col sm:flex-row sm:items-center gap-4">
                        <div class="flex-1">
                            <h1 class="text-2xl font-black text-white mb-2">{{ eventData.title }}</h1>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-slate-400">
                                <span v-if="eventData.date_start" class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-primary flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                                    </svg>
                                    {{ formatFullDate(eventData.date_start) }}
                                </span>
                                <span v-if="eventData.city" class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-primary flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                    </svg>
                                    {{ eventData.city }}{{ eventData.state ? `, ${eventData.state}` : "" }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Seção Ingressos Confirmados -->
                <div class="mb-4 flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h2 class="text-lg font-bold text-white">Ingressos Confirmados</h2>
                        <p class="text-sm text-slate-400 mt-0.5">
                            Gerencie os ingressos individuais de cada participante vinculado à sua conta.
                        </p>
                    </div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-primary/10 border border-primary/30 text-primary text-sm font-bold whitespace-nowrap">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z" />
                            </svg>
                            {{ tickets.length.toString().padStart(2, "0") }} Ingresso{{ tickets.length !== 1 ? "s" : "" }}
                        </span>

                        <!-- Solicitação de cancelamento (estorno de todos os ingressos do pedido) -->
                        <button
                            v-if="canRequestCancellation"
                            @click="openCancelModal"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-red-500 text-red-400 text-sm font-bold hover:bg-red-500/10 transition-colors whitespace-nowrap"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            Solicitar cancelamento
                        </button>
                    </div>
                </div>

                <!-- Lista de tickets individuais -->
                <div class="space-y-3">
                    <div
                        v-for="item in tickets"
                        :key="item.ticket.code"
                        class="bg-surface-dark border border-border-dark rounded-xl px-5 py-4 flex flex-col sm:flex-row sm:items-center gap-4"
                    >
                        <!-- Avatar + Dados do participante -->
                        <div class="flex items-center gap-4 flex-1 min-w-0">
                            <div class="flex-shrink-0 w-11 h-11 rounded-full bg-slate-700 flex items-center justify-center">
                                <svg class="w-5 h-5 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-white font-semibold text-sm truncate">
                                    {{ item.participant?.name || "Participante" }}
                                </p>
                                <div class="flex items-center gap-2 mt-1 flex-wrap">
                                    <span class="px-2 py-0.5 rounded bg-slate-700 text-slate-300 text-xs font-medium">
                                        {{ categoryLabel(item) }}
                                    </span>
                                </div>
                                <!-- Ref do pedido + ID do ingresso, abaixo da categoria -->
                                <div class="mt-1.5 text-slate-500 text-xs">
                                    <span v-if="item.order?.reference" class="mr-3 whitespace-nowrap">
                                        Ref: <span class="font-mono">{{ item.order.reference }}</span>
                                    </span>
                                    <span class="whitespace-nowrap">
                                        <svg class="w-3 h-3 inline-block align-[-0.125em] mr-1" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v2a2 2 0 100 4v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 100-4V6z" />
                                        </svg>ID: {{ formatCode(item.ticket.code) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Status + Botão QR -->
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-5 flex-shrink-0">
                            <!-- Status -->
                            <div class="flex flex-col items-start sm:items-end">
                                <span class="text-[10px] uppercase tracking-widest text-slate-500 font-semibold mb-0.5">
                                    Status do Ingresso
                                </span>
                                <!-- Solicitação de cancelamento ativa substitui o status do ingresso -->
                                <span
                                    v-if="activeCancellationStatus(item)"
                                    class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded text-sm font-semibold whitespace-nowrap"
                                    :class="cancellationBadgeClass(activeCancellationStatus(item))"
                                >
                                    {{ cancellationBadgeLabel(activeCancellationStatus(item)) }}
                                </span>
                                <span v-else class="flex items-center gap-1.5 text-sm font-semibold"
                                    :class="statusClass(item.ticket.status)"
                                >
                                    <span class="w-2 h-2 rounded-full"
                                        :class="statusDotClass(item.ticket.status)"
                                    ></span>
                                    {{ item.ticket.status_label || statusLabel(item.ticket.status) }}
                                </span>
                            </div>

                            <!-- Botão QR Code (apenas para ingressos ativos) -->
                            <button
                                v-if="item.ticket.status === 'active'"
                                @click="openQr(item)"
                                class="flex items-center justify-center gap-2 px-4 py-2.5 bg-primary text-background-dark font-bold text-sm rounded-lg hover:bg-primary/90 transition-colors whitespace-nowrap"
                            >
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm2 2V5h1v1H5zm7-2a1 1 0 00-1 1v3a1 1 0 001 1h3a1 1 0 001-1V4a1 1 0 00-1-1h-3zm1 2v1h1V5h-1zM3 12a1 1 0 011-1h3a1 1 0 011 1v3a1 1 0 01-1 1H4a1 1 0 01-1-1v-3zm2 2v-1h1v1H5zm5-2a1 1 0 011-1h3a1 1 0 110 2h-1v1a1 1 0 11-2 0v-1h-1a1 1 0 01-1-1zm4 2a1 1 0 102 0 1 1 0 00-2 0z" clip-rule="evenodd" />
                                </svg>
                                Ver QR Code
                            </button>
                        </div>
                    </div>
                </div>
            </template>

            <!-- Erro / sem dados -->
            <div v-else class="text-center py-24 text-slate-400">
                <p class="text-lg font-semibold text-white mb-2">Evento não encontrado</p>
                <router-link to="/meus-ingressos" class="text-primary hover:underline text-sm">
                    Voltar para Meus Ingressos
                </router-link>
            </div>
        </main>

        <!-- Modal: Solicitar cancelamento -->
        <Teleport to="body">
            <div
                v-if="cancelModal.open"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                @click.self="closeCancelModal"
            >
                <div class="bg-surface-dark border border-border-dark rounded-2xl p-6 max-w-md w-full shadow-2xl">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-white">Solicitar cancelamento</h3>
                            <p class="text-sm text-slate-400">{{ eventData?.title }}</p>
                        </div>
                        <button @click="closeCancelModal" class="text-slate-400 hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <p class="text-sm text-slate-400 mb-3">
                        Selecione os pedidos que deseja cancelar. Sua solicitação será
                        avaliada pela organização e, em caso de aprovação, o valor pago será estornado.
                    </p>

                    <!-- Seleção de pedidos -->
                    <label class="block text-xs font-semibold text-slate-300 mb-1.5">
                        Pedidos <span class="text-red-400">*</span>
                    </label>
                    <div class="space-y-2 mb-4 max-h-52 overflow-y-auto pr-1">
                        <label
                            v-for="order in cancellableOrders"
                            :key="order.reference"
                            class="flex items-start gap-3 rounded-lg bg-background-dark border border-border-dark px-3 py-2.5 cursor-pointer hover:border-primary/50 transition-colors"
                        >
                            <input
                                type="checkbox"
                                :value="order.reference"
                                :checked="selectedRefs.includes(order.reference)"
                                @change="toggleRef(order.reference)"
                                class="mt-0.5 h-4 w-4 rounded border-border-dark bg-surface-dark text-primary focus:ring-primary"
                            />
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-slate-100">
                                    Ref: <span class="font-mono">{{ order.reference }}</span>
                                </span>
                                <span class="block text-xs text-slate-400 mt-0.5">
                                    {{ order.participants.join(", ") }}
                                    <span class="text-slate-500">
                                        ({{ order.count }} ingresso{{ order.count !== 1 ? "s" : "" }})
                                    </span>
                                </span>
                            </span>
                        </label>
                    </div>

                    <label class="block text-xs font-semibold text-slate-300 mb-1">
                        Motivo do cancelamento <span class="text-red-400">*</span>
                    </label>
                    <textarea
                        v-model="cancelReason"
                        rows="4"
                        maxlength="1000"
                        placeholder="Descreva o motivo do cancelamento..."
                        class="w-full rounded-lg bg-background-dark border border-border-dark px-3 py-2 text-sm text-slate-100 placeholder-slate-500 focus:outline-none focus:border-primary resize-none"
                    ></textarea>

                    <div class="flex justify-end gap-3 mt-5">
                        <button
                            @click="closeCancelModal"
                            class="px-4 py-2 text-sm font-semibold text-slate-300 hover:text-white transition-colors"
                        >
                            Voltar
                        </button>
                        <button
                            @click="submitCancellation"
                            :disabled="!cancelReason.trim() || selectedRefs.length === 0 || submitting"
                            class="px-4 py-2 bg-red-500 text-white text-sm font-bold rounded-lg hover:bg-red-600 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            {{ submitting ? "Enviando..." : "Enviar solicitação" }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>

        <!-- Modal do QR Code Individual -->
        <Teleport to="body">
            <div
                v-if="qrModal.open"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/70 backdrop-blur-sm"
                @click.self="closeQr"
            >
                <div class="bg-surface-dark border border-border-dark rounded-2xl p-6 max-w-sm w-full shadow-2xl">
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-lg font-bold text-white">QR Code</h3>
                            <p class="text-sm text-slate-400">{{ qrModal.item?.participant?.name }}</p>
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
                            :alt="`QR Code - ${qrModal.item?.participant?.name}`"
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
                            <span class="text-white">{{ categoryLabel(qrModal.item) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-400">Código</span>
                            <span class="text-white font-mono text-xs">{{ formatCode(qrModal.item?.ticket?.code) }}</span>
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
import { useRoute } from "vue-router";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import api from "../api/axios";
import { useOrdersStore } from "../stores/orders";
import { useToast } from "../composables/useToast";

const route = useRoute();
const ordersStore = useOrdersStore();
const toast = useToast();

const loading = ref(true);
const tickets = ref([]);
const eventData = ref(null);
const qrModal = ref({ open: false, item: null });

// ── Cancelamento (estorno do(s) pedido(s) deste evento) ────────────────────
const cancelModal = ref({ open: false });
const cancelReason = ref("");
const submitting = ref(false);
const selectedRefs = ref([]);

// Pedidos elegíveis a cancelamento — a regra (pago, < 7 dias, ingresso ativo,
// sem solicitação pendente/aprovada) é decidida no backend e exposta via
// `order.can_request_cancellation`. Aqui apenas agrupamos os ingressos por
// pedido para exibir na seleção do modal.
const cancellableOrders = computed(() => {
    const byRef = new Map();
    for (const item of tickets.value) {
        const order = item.order;
        if (!order || !order.can_request_cancellation) continue;
        if (!byRef.has(order.reference)) {
            byRef.set(order.reference, {
                reference: order.reference,
                participants: [],
            });
        }
        const name = item.participant?.name;
        if (name) byRef.get(order.reference).participants.push(name);
    }
    return [...byRef.values()].map((o) => ({ ...o, count: o.participants.length }));
});

const canRequestCancellation = computed(() => cancellableOrders.value.length > 0);

function toggleRef(reference) {
    const i = selectedRefs.value.indexOf(reference);
    if (i === -1) selectedRefs.value.push(reference);
    else selectedRefs.value.splice(i, 1);
}

const cancellationBadges = {
    pending: { label: "Cancelamento pendente", class: "bg-amber-500/10 border border-amber-500/30 text-amber-300" },
};

function cancellationBadgeLabel(status) {
    return cancellationBadges[status]?.label || "Cancelamento solicitado";
}

function cancellationBadgeClass(status) {
    return cancellationBadges[status]?.class || "bg-slate-700 text-slate-300";
}

// Status do cancelamento exibido no ingresso (apenas enquanto pendente).
// Quando aprovado, o próprio status do ingresso (reembolsado) é exibido.
function activeCancellationStatus(item) {
    const st = item.order?.cancellation?.status;
    return st === "pending" ? st : null;
}

function openCancelModal() {
    cancelReason.value = "";
    selectedRefs.value = []; // todos desmarcados: usuário precisa selecionar
    cancelModal.value.open = true;
}

function closeCancelModal() {
    cancelModal.value.open = false;
    cancelReason.value = "";
    selectedRefs.value = [];
}

async function submitCancellation() {
    const reason = cancelReason.value.trim();
    if (!reason || selectedRefs.value.length === 0 || submitting.value) return;
    submitting.value = true;
    try {
        // Envia um único request com os pedidos selecionados.
        await ordersStore.createCancellationBatch(selectedRefs.value, reason);
        toast.success(
            "Sua solicitação foi enviada e será avaliada pela organização.",
            "Solicitação enviada",
        );
        closeCancelModal();
        await loadTickets();
    } catch (err) {
        toast.error(
            err.response?.data?.message ||
                "Não foi possível enviar a solicitação.",
        );
    } finally {
        submitting.value = false;
    }
}

function categoryLabel(item) {
    if (!item) return "";
    const dist = item.category?.distance ? `${parseFloat(item.category.distance)}km` : null;
    const name = item.category?.name || item.ticket_type?.name || "";
    return dist ? `${dist} – ${name}` : name;
}

function formatCode(code) {
    if (!code) return "";
    // Mostra os primeiros 13 chars do UUID em maiúsculas (ex: 8F2B-91AC-44E1)
    return code.slice(0, 13).toUpperCase().replace(/-/g, "-");
}

function formatFullDate(dateStr) {
    return new Date(dateStr).toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "long",
        year: "numeric",
    });
}

function statusClass(status) {
    if (status === "active") return "text-primary";
    if (status === "inactive") return "text-amber-400";
    if (status === "used") return "text-slate-400";
    if (status === "refunded") return "text-rose-400";
    return "text-red-400";
}

function statusDotClass(status) {
    if (status === "active") return "bg-primary";
    if (status === "inactive") return "bg-amber-400";
    if (status === "used") return "bg-slate-400";
    if (status === "refunded") return "bg-rose-400";
    return "bg-red-400";
}

function statusLabel(status) {
    if (status === "active") return "Confirmado";
    if (status === "inactive") return "Inativo";
    if (status === "used") return "Utilizado";
    if (status === "refunded") return "Reembolsado";
    return "Cancelado";
}

function openQr(item) {
    qrModal.value = { open: true, item };
}

function closeQr() {
    qrModal.value = { open: false, item: null };
}

async function loadTickets() {
    try {
        const eventId = route.params.eventId;
        const { data } = await api.get("/tickets", { params: { event_id: eventId } });
        const items = data.data || [];
        tickets.value = items;
        if (items.length > 0) {
            eventData.value = items[0].event;
        }
    } catch (err) {
        console.error("Erro ao buscar ingressos do evento:", err);
    } finally {
        loading.value = false;
    }
}

onMounted(loadTickets);
</script>

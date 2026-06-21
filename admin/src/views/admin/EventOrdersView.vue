<template>
    <div class="p-6 max-w-6xl mx-auto">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm text-text-muted mb-6">
            <router-link to="/admin/events" class="hover:text-white transition-colors">
                Eventos
            </router-link>
            <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            <span class="text-text-secondary truncate max-w-xs">
                {{ event?.title || "Pedidos" }}
            </span>
        </nav>

        <div class="flex items-center gap-3 mb-6">
            <h1 class="text-2xl font-bold text-white">Pedidos</h1>
        </div>

        <!-- Tabs -->
        <div class="flex border-b border-surface-elevated mb-6">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                @click="activeTab = tab.key"
                class="px-5 py-3 text-sm font-semibold transition-colors relative focus:outline-none"
                :class="
                    activeTab === tab.key
                        ? 'text-primary'
                        : 'text-text-muted hover:text-white'
                "
            >
                {{ tab.label }}
                <span
                    v-if="tab.key === 'requests' && pendingCancellationsCount > 0"
                    class="ml-2 text-xs px-1.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300"
                >
                    {{ pendingCancellationsCount }}
                </span>
                <span
                    v-if="activeTab === tab.key"
                    class="absolute bottom-0 left-0 right-0 h-0.5 bg-primary rounded-t"
                ></span>
            </button>
        </div>

        <ErrorState
            v-if="store.error"
            :message="store.error"
            @retry="loadActiveTab"
        />

        <template v-else>
        <!-- Tab: Pedidos -->
        <div v-show="activeTab === 'orders'">
            <!-- Busca -->
            <div class="relative mb-4 max-w-md">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]"
                    >search</span
                >
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar por referência, e-mail ou CPF..."
                    class="w-full bg-card-bg border border-surface-elevated rounded-lg pl-10 pr-3 py-2.5 text-sm text-white placeholder-text-muted focus:outline-none focus:border-primary"
                />
            </div>

            <LoadingState v-if="store.isLoading" message="Carregando..." />
            <template v-else>
            <div
                v-if="store.orders.length === 0"
                class="text-center py-16 text-text-muted"
            >
                Nenhum pedido encontrado.
            </div>
            <div
                v-else
                class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden"
            >
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-text-muted text-xs uppercase tracking-wider border-b border-surface-elevated">
                            <th class="px-5 py-3">Referência</th>
                            <th class="px-5 py-3">Comprador</th>
                            <th class="px-5 py-3">Itens</th>
                            <th class="px-5 py-3">Total</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in store.orders"
                            :key="order.id"
                            class="border-t border-surface-elevated"
                        >
                            <td class="px-5 py-3 font-mono text-xs text-white">
                                {{ order.reference }}
                            </td>
                            <td class="px-5 py-3 text-text-secondary text-sm">
                                {{ order.buyer_email || "—" }}
                            </td>
                            <td class="px-5 py-3 text-text-secondary text-sm">
                                {{ order.items?.length || 0 }}
                            </td>
                            <td class="px-5 py-3 text-white text-sm">
                                {{ order.total_formatted }}
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="statusClass(order.status)"
                                >
                                    {{ order.status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-text-muted text-xs">
                                {{ formatDate(order.created_at) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <div
                v-if="store.ordersPagination.lastPage > 1"
                class="flex justify-center items-center gap-4 mt-4"
            >
                <button
                    @click="changeOrdersPage(store.ordersPagination.currentPage - 1)"
                    :disabled="store.ordersPagination.currentPage <= 1"
                    class="flex items-center justify-center w-8 h-8 rounded-lg border border-surface-elevated text-text-muted hover:text-white hover:border-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-base">chevron_left</span>
                </button>
                <span class="text-text-muted text-sm">
                    {{ store.ordersPagination.currentPage }} /
                    {{ store.ordersPagination.lastPage }}
                </span>
                <button
                    @click="changeOrdersPage(store.ordersPagination.currentPage + 1)"
                    :disabled="store.ordersPagination.currentPage >= store.ordersPagination.lastPage"
                    class="flex items-center justify-center w-8 h-8 rounded-lg border border-surface-elevated text-text-muted hover:text-white hover:border-primary transition-colors disabled:opacity-40 disabled:cursor-not-allowed"
                >
                    <span class="material-symbols-outlined text-base">chevron_right</span>
                </button>
            </div>
            </template>
        </div>

        <!-- Tab: Solicitações de cancelamento -->
        <div v-show="activeTab === 'requests'">
            <LoadingState v-if="store.isLoading" message="Carregando..." />
            <template v-else>
            <div
                v-if="store.cancellations.length === 0"
                class="text-center py-16 text-text-muted"
            >
                Nenhuma solicitação de cancelamento.
            </div>
            <div
                v-else
                class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden"
            >
                <table class="w-full">
                    <thead>
                        <tr class="text-left text-text-muted text-xs uppercase tracking-wider border-b border-surface-elevated">
                            <th class="px-5 py-3">Pedido</th>
                            <th class="px-5 py-3">Solicitante</th>
                            <th class="px-5 py-3">Motivo</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="req in store.cancellations"
                            :key="req.id"
                            class="border-t border-surface-elevated align-top"
                        >
                            <td class="px-5 py-3 font-mono text-xs text-white whitespace-nowrap">
                                {{ req.order?.reference }}
                                <div class="text-text-muted">
                                    {{ req.order?.total_formatted }}
                                </div>
                            </td>
                            <td class="px-5 py-3 text-text-secondary text-sm">
                                {{ req.requested_by_user?.name || "—" }}
                                <div class="text-text-muted text-xs">
                                    {{ req.requested_by_user?.email }}
                                </div>
                            </td>
                            <td class="px-5 py-3 text-text-secondary text-sm max-w-xs">
                                {{ req.reason }}
                                <div
                                    v-if="req.review_notes"
                                    class="text-text-muted text-xs mt-1"
                                >
                                    Obs.: {{ req.review_notes }}
                                </div>
                            </td>
                            <td class="px-5 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                    :class="requestStatusClass(req.status)"
                                >
                                    {{ req.status_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div
                                    v-if="req.status === 'pending'"
                                    class="flex items-center justify-end gap-2"
                                >
                                    <button
                                        @click="openApprove(req)"
                                        class="px-3 py-1.5 bg-primary text-black rounded-lg text-xs font-medium hover:brightness-110 transition-colors"
                                    >
                                        Aprovar
                                    </button>
                                    <button
                                        @click="openReject(req)"
                                        class="px-3 py-1.5 border border-red-500 text-red-500 rounded-lg text-xs font-medium hover:bg-red-500/10 transition-colors"
                                    >
                                        Rejeitar
                                    </button>
                                </div>
                                <span v-else class="text-text-muted text-xs">
                                    Avaliada
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            </template>
        </div>
        </template>

        <!-- Modal: Aprovar estorno -->
        <Modal
            v-model="approveModal.open"
            title="Aprovar estorno"
            :subtitle="approveModal.request?.order?.reference"
        >
            <p class="text-text-secondary">
                Ao aprovar, o valor de
                <strong class="text-white">{{
                    approveModal.request?.order?.total_formatted
                }}</strong>
                será estornado ao comprador via Mercado Pago e os ingressos serão
                reembolsados. Esta ação não pode ser desfeita.
            </p>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="approveModal.open = false"
                        class="px-4 py-2 text-sm font-medium text-text-muted hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="confirmApprove"
                        :disabled="submitting"
                        class="px-4 py-2 bg-primary text-black text-sm font-medium rounded-lg hover:brightness-110 transition-colors disabled:opacity-50"
                    >
                        {{ submitting ? "Processando..." : "Aprovar e estornar" }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Modal: Rejeitar solicitação -->
        <Modal
            v-model="rejectModal.open"
            title="Rejeitar solicitação"
            :subtitle="rejectModal.request?.order?.reference"
        >
            <p class="text-text-secondary mb-3">
                Informe o motivo da rejeição (opcional). O comprador poderá visualizar.
            </p>
            <textarea
                v-model="rejectNotes"
                rows="3"
                maxlength="1000"
                placeholder="Motivo da rejeição..."
                class="w-full rounded-lg bg-surface-elevated border border-surface-elevated px-3 py-2 text-sm text-white placeholder-text-muted focus:outline-none focus:border-primary resize-none"
            ></textarea>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="rejectModal.open = false"
                        class="px-4 py-2 text-sm font-medium text-text-muted hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="confirmReject"
                        :disabled="submitting"
                        class="px-4 py-2 bg-red-500 text-white text-sm font-medium rounded-lg hover:bg-red-600 transition-colors disabled:opacity-50"
                    >
                        {{ submitting ? "Enviando..." : "Rejeitar" }}
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useOrdersStore } from "@/stores/orders";
import { useEventsStore } from "@/stores/events";
import { useToast } from "@/composables/useToast";
import Modal from "@/components/ui/Modal.vue";
import LoadingState from "@/components/ui/LoadingState.vue";
import ErrorState from "@/components/ui/ErrorState.vue";

const route = useRoute();
const store = useOrdersStore();
const eventsStore = useEventsStore();
const toast = useToast();

const eventId = route.params.eventId;
const event = ref(null);

const tabs = [
    { key: "orders", label: "Pedidos" },
    { key: "requests", label: "Solicitações de cancelamento" },
];
const activeTab = ref("orders");

const submitting = ref(false);
const approveModal = reactive({ open: false, request: null });
const rejectModal = reactive({ open: false, request: null });
const rejectNotes = ref("");

// Busca (referência / e-mail / CPF) com debounce
const searchQuery = ref("");
let searchTimer = null;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        store.fetchEventOrders(eventId, 1, searchQuery.value.trim());
    }, 400);
});

function changeOrdersPage(page) {
    if (page < 1 || page > store.ordersPagination.lastPage) return;
    store.fetchEventOrders(eventId, page, searchQuery.value.trim());
}

const pendingCancellationsCount = computed(
    () =>
        store.cancellations.filter((r) => r.status === "pending").length,
);

async function loadActiveTab() {
    if (activeTab.value === "orders") {
        await store.fetchEventOrders(eventId, 1, searchQuery.value.trim());
    } else {
        await store.fetchCancellations(eventId);
    }
}

function openApprove(request) {
    approveModal.request = request;
    approveModal.open = true;
}

function openReject(request) {
    rejectModal.request = request;
    rejectNotes.value = "";
    rejectModal.open = true;
}

async function confirmApprove() {
    submitting.value = true;
    try {
        await store.approveCancellation(approveModal.request.id);
        toast.success("Estorno realizado com sucesso.");
        approveModal.open = false;
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Erro ao processar o estorno.",
        );
    } finally {
        submitting.value = false;
    }
}

async function confirmReject() {
    submitting.value = true;
    try {
        await store.rejectCancellation(
            rejectModal.request.id,
            rejectNotes.value.trim() || null,
        );
        toast.success("Solicitação rejeitada.");
        rejectModal.open = false;
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Erro ao rejeitar a solicitação.",
        );
    } finally {
        submitting.value = false;
    }
}

function statusClass(status) {
    return (
        {
            paid: "bg-emerald-500/10 text-emerald-300",
            pending: "bg-amber-500/10 text-amber-300",
            processing: "bg-sky-500/10 text-sky-300",
            failed: "bg-red-500/10 text-red-300",
            cancelled: "bg-slate-600/30 text-slate-300",
            refunded: "bg-sky-500/10 text-sky-300",
        }[status] || "bg-slate-600/30 text-slate-300"
    );
}

function requestStatusClass(status) {
    return (
        {
            pending: "bg-amber-500/10 text-amber-300",
            approved: "bg-emerald-500/10 text-emerald-300",
            rejected: "bg-red-500/10 text-red-300",
        }[status] || "bg-slate-600/30 text-slate-300"
    );
}

function formatDate(iso) {
    if (!iso) return "—";
    return new Date(iso).toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
    });
}

onMounted(async () => {
    // Carrega ambas as abas para popular contadores e dados.
    await Promise.all([
        store.fetchEventOrders(eventId),
        store.fetchCancellations(eventId),
    ]);
    const result = await eventsStore.fetchEvent(eventId);
    if (result.success) {
        event.value = result.data;
    }
});
</script>

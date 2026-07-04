<template>
    <div>
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

        <div class="mb-6">
            <h1 class="text-3xl font-extrabold text-white">Pedidos</h1>
            <p class="text-text-secondary mt-1">
                Pedidos e solicitações de cancelamento do evento
            </p>
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
            <!-- Busca e filtro por status -->
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative w-full sm:max-w-md">
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
                <select
                    v-model="statusFilter"
                    class="bg-card-bg border border-surface-elevated rounded-lg px-4 py-2.5 text-sm text-white focus:outline-none focus:border-primary transition-colors appearance-none cursor-pointer sm:min-w-[180px]"
                >
                    <option
                        v-for="option in ORDER_STATUS_OPTIONS"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </option>
                </select>
            </div>

            <!-- Empty state -->
            <div
                v-if="!store.isLoading && store.orders.length === 0"
                class="text-center py-16 text-text-muted"
            >
                Nenhum pedido encontrado.
            </div>

            <!-- Tabela -->
            <div v-else class="bg-card-bg rounded-xl border border-surface-elevated">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-surface-elevated">
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Referência</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Comprador</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Ingressos</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Pago com</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Total</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Status</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Data da compra</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-surface-elevated"
                        style="min-height: 462px"
                    >
                        <!-- Skeleton -->
                        <template v-if="store.isLoading">
                            <tr
                                v-for="n in 6"
                                :key="'orders-skeleton-' + n"
                                class="h-[77px]"
                            >
                                <td class="px-6 py-4">
                                    <div class="h-4 w-32 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-40 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-8 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-24 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-20 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-6 w-16 bg-surface-elevated rounded-full animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-24 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                            </tr>
                        </template>

                        <!-- Linhas -->
                        <template v-else>
                            <tr
                                v-for="order in store.orders"
                                :key="order.id"
                                @click="openOrderDetails(order)"
                                class="h-[77px] cursor-pointer hover:bg-white/[0.02] transition-colors"
                            >
                                <td class="px-6 py-4 font-mono text-xs text-white">
                                    {{ order.reference }}
                                </td>
                                <td class="px-6 py-4 text-text-secondary text-sm">
                                    {{ order.buyer_email || "—" }}
                                </td>
                                <td class="px-6 py-4 text-text-secondary text-sm">
                                    {{ order.items?.length || 0 }}
                                </td>
                                <td class="px-6 py-4 text-text-secondary text-sm">
                                    {{ order.payment_method_label || "—" }}
                                </td>
                                <td class="px-6 py-4 text-white text-sm">
                                    {{ order.total_formatted }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="statusClass(order.status)"
                                    >
                                        {{ order.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-text-muted text-xs">
                                    {{ formatDate(order.created_at) }}
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <BasePagination
                    :current-page="store.ordersPagination.currentPage"
                    :last-page="store.ordersPagination.lastPage"
                    :per-page="store.ordersPagination.perPage"
                    :total="store.ordersPagination.total"
                    :disabled="store.isLoading"
                    @change="changeOrdersPage"
                />
            </div>
        </div>

        <!-- Tab: Solicitações de cancelamento -->
        <div v-show="activeTab === 'requests'">
            <!-- Busca -->
            <div class="relative mb-4 max-w-md">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]"
                    >search</span
                >
                <input
                    v-model="cancellationSearchQuery"
                    type="text"
                    placeholder="Buscar por referência, e-mail ou CPF..."
                    class="w-full bg-card-bg border border-surface-elevated rounded-lg pl-10 pr-3 py-2.5 text-sm text-white placeholder-text-muted focus:outline-none focus:border-primary"
                />
            </div>

            <!-- Empty state -->
            <div
                v-if="!store.isLoading && store.cancellations.length === 0"
                class="text-center py-16 text-text-muted"
            >
                Nenhuma solicitação de cancelamento.
            </div>

            <!-- Tabela -->
            <div v-else class="bg-card-bg rounded-xl border border-surface-elevated">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-surface-elevated">
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Pedido</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Solicitante</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Ingressos</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Status</th>
                            <th class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Solicitado em</th>
                            <th class="text-right text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-y divide-surface-elevated"
                        style="min-height: 462px"
                    >
                        <!-- Skeleton -->
                        <template v-if="store.isLoading">
                            <tr
                                v-for="n in 6"
                                :key="'requests-skeleton-' + n"
                                class="h-[77px]"
                            >
                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div class="h-4 w-32 bg-surface-elevated rounded animate-pulse"></div>
                                        <div class="h-3 w-20 bg-surface-elevated rounded animate-pulse"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="space-y-2">
                                        <div class="h-4 w-32 bg-surface-elevated rounded animate-pulse"></div>
                                        <div class="h-3 w-40 bg-surface-elevated rounded animate-pulse"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-8 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-6 w-16 bg-surface-elevated rounded-full animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="h-4 w-24 bg-surface-elevated rounded animate-pulse"></div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="h-7 w-16 bg-surface-elevated rounded-lg animate-pulse"></div>
                                        <div class="h-7 w-16 bg-surface-elevated rounded-lg animate-pulse"></div>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <!-- Linhas -->
                        <template v-else>
                            <tr
                                v-for="req in store.cancellations"
                                :key="req.id"
                                @click="openRequestDetails(req)"
                                class="h-[77px] cursor-pointer hover:bg-white/[0.02] transition-colors"
                            >
                                <td class="px-6 py-4 font-mono text-xs text-white whitespace-nowrap">
                                    {{ req.order?.reference }}
                                    <div class="text-text-muted">
                                        {{ req.order?.total_formatted }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-text-secondary text-sm">
                                    {{ req.requested_by_user?.name || "—" }}
                                    <div class="text-text-muted text-xs">
                                        {{ req.requested_by_user?.email }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-text-secondary text-sm">
                                    {{ req.order?.items?.length || 0 }}
                                </td>
                                <td class="px-6 py-4">
                                    <span
                                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                                        :class="requestStatusClass(req.status)"
                                    >
                                        {{ req.status_label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-text-muted text-xs">
                                    {{ formatDate(req.created_at) }}
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div
                                        v-if="req.status === 'pending'"
                                        class="flex items-center justify-end gap-2"
                                    >
                                        <button
                                            @click.stop="openApprove(req)"
                                            class="px-3 py-1.5 bg-primary text-black rounded-lg text-xs font-medium hover:brightness-110 transition-colors"
                                        >
                                            Aprovar
                                        </button>
                                        <button
                                            @click.stop="openReject(req)"
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
                        </template>
                    </tbody>
                </table>

                <BasePagination
                    :current-page="store.cancellationsPagination.currentPage"
                    :last-page="store.cancellationsPagination.lastPage"
                    :per-page="store.cancellationsPagination.perPage"
                    :total="store.cancellationsPagination.total"
                    :disabled="store.isLoading"
                    @change="changeCancellationsPage"
                />
            </div>
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

        <!-- Modal: Detalhes do pedido / ingressos -->
        <OrderDetailsModal
            v-model="detailsModal.open"
            :order="detailsModal.order"
            :cancellation="detailsModal.cancellation"
        />
    </div>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from "vue";
import { useRoute } from "vue-router";
import { useOrdersStore } from "@/stores/orders";
import { useEventsStore } from "@/stores/events";
import { useToast } from "@/composables/useToast";
import Modal from "@/components/ui/Modal.vue";
import OrderDetailsModal from "@/components/orders/OrderDetailsModal.vue";
import ErrorState from "@/components/ui/ErrorState.vue";
import BasePagination from "@/components/ui/BasePagination.vue";
import { ORDER_STATUS_OPTIONS } from "@/constants/orderStatus";

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

const detailsModal = reactive({ open: false, order: null, cancellation: null });

function openOrderDetails(order) {
    detailsModal.order = order;
    detailsModal.cancellation = null;
    detailsModal.open = true;
}

function openRequestDetails(req) {
    detailsModal.order = req.order;
    detailsModal.cancellation = req;
    detailsModal.open = true;
}

// Busca (referência / e-mail / CPF) com debounce
const searchQuery = ref("");
let searchTimer = null;
watch(searchQuery, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        store.fetchEventOrders(eventId, 1, searchQuery.value.trim(), statusFilter.value);
    }, 400);
});

// Filtro por status (padrão: todos). "" = opção "Todos" (sem filtro no backend).
const statusFilter = ref("");
watch(statusFilter, () => {
    store.fetchEventOrders(eventId, 1, searchQuery.value.trim(), statusFilter.value);
});

function changeOrdersPage(page) {
    if (page < 1 || page > store.ordersPagination.lastPage) return;
    store.fetchEventOrders(eventId, page, searchQuery.value.trim(), statusFilter.value);
}

// Busca de cancelamentos (referência / e-mail / CPF) com debounce
const cancellationSearchQuery = ref("");
let cancellationSearchTimer = null;
watch(cancellationSearchQuery, () => {
    clearTimeout(cancellationSearchTimer);
    cancellationSearchTimer = setTimeout(() => {
        store.fetchCancellations(eventId, 1, cancellationSearchQuery.value.trim());
    }, 400);
});

function changeCancellationsPage(page) {
    if (page < 1 || page > store.cancellationsPagination.lastPage) return;
    store.fetchCancellations(eventId, page, cancellationSearchQuery.value.trim());
}

const pendingCancellationsCount = computed(
    () =>
        store.cancellations.filter((r) => r.status === "pending").length,
);

async function loadActiveTab() {
    if (activeTab.value === "orders") {
        await store.fetchEventOrders(eventId, 1, searchQuery.value.trim(), statusFilter.value);
    } else {
        await store.fetchCancellations(
            eventId,
            1,
            cancellationSearchQuery.value.trim(),
        );
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
        store.fetchEventOrders(eventId, 1, searchQuery.value.trim(), statusFilter.value),
        store.fetchCancellations(eventId),
    ]);
    const result = await eventsStore.fetchEvent(eventId);
    if (result.success) {
        event.value = result.data;
    }
});
</script>

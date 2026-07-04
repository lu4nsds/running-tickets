<template>
    <Modal
        :model-value="modelValue"
        size="xl"
        title="Detalhes do pedido"
        :subtitle="order?.reference"
        @update:model-value="$emit('update:modelValue', $event)"
    >
        <div v-if="order" class="space-y-5">
            <!-- Resumo -->
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-text-muted text-xs uppercase tracking-wider">
                        Comprador
                    </p>
                    <p class="text-white">{{ order.buyer_email || "—" }}</p>
                </div>
                <div>
                    <p class="text-text-muted text-xs uppercase tracking-wider">
                        Pago com
                    </p>
                    <p class="text-white">{{ order.payment_method_label || "—" }}</p>
                </div>
                <div>
                    <p class="text-text-muted text-xs uppercase tracking-wider">
                        Total
                    </p>
                    <p class="text-white">{{ order.total_formatted }}</p>
                </div>
                <div>
                    <p class="text-text-muted text-xs uppercase tracking-wider">
                        Status do pedido
                    </p>
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium mt-1"
                        :class="orderStatusClass(order.status)"
                    >
                        {{ order.status_label }}
                    </span>
                </div>
                <div>
                    <p class="text-text-muted text-xs uppercase tracking-wider">
                        Data da compra
                    </p>
                    <p class="text-white">{{ formatDate(order.created_at) }}</p>
                </div>
            </div>

            <!-- Motivo do cancelamento -->
            <div
                v-if="cancellation"
                class="rounded-lg border border-surface-elevated bg-surface-elevated/40 p-4"
            >
                <div class="flex items-center justify-between mb-2">
                    <p
                        class="text-text-muted text-xs uppercase tracking-wider"
                    >
                        Motivo do cancelamento
                    </p>
                    <span
                        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium"
                        :class="requestStatusClass(cancellation.status)"
                    >
                        {{ cancellation.status_label }}
                    </span>
                </div>
                <p class="text-text-secondary text-sm whitespace-pre-line">
                    {{ cancellation.reason || "—" }}
                </p>
                <p
                    v-if="cancellation.review_notes"
                    class="text-text-muted text-xs mt-2"
                >
                    Obs. da avaliação: {{ cancellation.review_notes }}
                </p>
            </div>

            <!-- Ingressos -->
            <div>
                <p
                    class="text-text-muted text-xs uppercase tracking-wider mb-2"
                >
                    Ingressos ({{ order.items?.length || 0 }})
                </p>
                <div class="space-y-2">
                    <div
                        v-for="item in order.items"
                        :key="item.id"
                        class="rounded-lg border border-surface-elevated p-3"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-white text-sm font-medium truncate">
                                    {{ item.participant_data?.name || "Participante" }}
                                </p>
                                <p class="text-text-muted text-xs">
                                    {{ item.ticket_type?.name || "—" }}
                                    <template v-if="item.category?.name">
                                        · {{ item.category.name }}
                                    </template>
                                </p>
                            </div>
                            <span
                                v-if="item.ticket"
                                class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium whitespace-nowrap"
                                :class="ticketStatusClass(item.ticket.status)"
                            >
                                {{ item.ticket.status_label }}
                            </span>
                        </div>
                        <div
                            v-if="item.participant_data?.cpf || item.ticket?.code"
                            class="flex items-end justify-between gap-3 mt-2"
                        >
                            <p class="text-text-muted text-xs">
                                <template v-if="item.participant_data?.cpf">
                                    CPF: {{ item.participant_data.cpf }}
                                </template>
                            </p>
                            <p
                                v-if="item.ticket?.code"
                                class="font-mono text-[11px] text-text-muted text-right"
                            >
                                ID: {{ item.ticket.code }}
                            </p>
                        </div>
                    </div>
                    <div
                        v-if="!order.items?.length"
                        class="text-text-muted text-sm text-center py-4"
                    >
                        Nenhum ingresso neste pedido.
                    </div>
                </div>
            </div>
        </div>
    </Modal>
</template>

<script setup>
import Modal from "@/components/ui/Modal.vue";

defineProps({
    modelValue: { type: Boolean, required: true },
    order: { type: Object, default: null },
    cancellation: { type: Object, default: null },
});

defineEmits(["update:modelValue"]);

function orderStatusClass(status) {
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

function ticketStatusClass(status) {
    return (
        {
            active: "bg-emerald-500/10 text-emerald-300",
            used: "bg-sky-500/10 text-sky-300",
            cancelled: "bg-slate-600/30 text-slate-300",
            refunded: "bg-red-500/10 text-red-300",
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
</script>

<template>
    <tr
        class="border-b border-surface-elevated hover:bg-surface/30 transition-colors"
    >
        <!-- Name & Description -->
        <td class="py-4 px-4">
            <div>
                <p class="text-white font-medium">{{ ticketType.name }}</p>
                <p
                    v-if="ticketType.description"
                    class="text-text-muted text-sm mt-0.5 line-clamp-1"
                >
                    {{ ticketType.description }}
                </p>
            </div>
        </td>

        <!-- Price -->
        <td class="py-4 px-4">
            <p class="text-white font-semibold">
                {{
                    formatCurrency(ticketType.price_cents, ticketType.currency)
                }}
            </p>
        </td>

        <!-- Sale Period -->
        <td class="py-4 px-4">
            <div class="text-sm">
                <p class="text-text-secondary">
                    <span class="text-text-muted">Início:</span>
                    {{ formatDate(ticketType.start_sale) }}
                </p>
                <p class="text-text-secondary mt-0.5">
                    <span class="text-text-muted">Fim:</span>
                    {{ formatDate(ticketType.end_sale) }}
                </p>
            </div>
        </td>

        <!-- Quota -->
        <td class="py-4 px-4">
            <p class="text-white font-medium">{{ ticketType.quota }}</p>
        </td>

        <!-- Status -->
        <td class="py-4 px-4">
            <span
                :class="[
                    'px-2 py-1 rounded text-xs font-semibold inline-block',
                    ticketType.active
                        ? 'bg-primary/10 text-primary'
                        : 'bg-red-500/10 text-red-400',
                ]"
            >
                {{ ticketType.active ? "Ativo" : "Inativo" }}
            </span>
        </td>

        <!-- Actions Slot -->
        <td v-if="showActions" class="py-4 px-4">
            <slot name="actions" :ticket-type="ticketType"></slot>
        </td>
    </tr>
</template>

<script setup>
import { useCurrency } from "@/composables/useCurrency";

const { formatCurrency } = useCurrency();

defineProps({
    ticketType: {
        type: Object,
        required: true,
    },
    showActions: {
        type: Boolean,
        default: false,
    },
});

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};
</script>

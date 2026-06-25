<script setup>
import { computed } from "vue";

const props = defineProps({
    currentPage: { type: Number, required: true },
    lastPage: { type: Number, required: true },
    perPage: { type: Number, required: true },
    total: { type: Number, required: true },
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["change"]);

const paginationInfo = computed(() => {
    const from = props.total > 0 ? (props.currentPage - 1) * props.perPage + 1 : 0;
    const to = Math.min(props.currentPage * props.perPage, props.total);
    return { from, to };
});

const visiblePages = computed(() => {
    const current = props.currentPage;
    const last = props.lastPage;
    const pages = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push("...");

        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);

        for (let i = start; i <= end; i++) pages.push(i);

        if (current < last - 2) pages.push("...");
        pages.push(last);
    }

    return pages;
});

function goToPage(page) {
    if (page < 1 || page > props.lastPage || page === props.currentPage || props.disabled) {
        return;
    }
    emit("change", page);
}
</script>

<template>
    <div
        v-if="total > 0"
        class="flex items-center justify-between px-6 py-4 border-t border-surface-elevated"
    >
        <p class="text-text-muted text-sm">
            Mostrando {{ paginationInfo.from }} a {{ paginationInfo.to }} de
            {{ total }} resultados
        </p>
        <div class="flex items-center gap-1">
            <!-- Prev -->
            <button
                @click="goToPage(currentPage - 1)"
                :disabled="currentPage === 1 || disabled"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-text-muted hover:text-white hover:bg-surface-elevated disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-lg"
            >
                &lt;
            </button>

            <!-- Page Numbers -->
            <template v-for="page in visiblePages" :key="page">
                <button
                    v-if="page !== '...'"
                    @click="goToPage(page)"
                    :disabled="disabled"
                    class="w-9 h-9 rounded-lg text-sm font-medium transition-colors"
                    :class="
                        page === currentPage
                            ? 'bg-primary text-black'
                            : 'text-text-secondary hover:text-white hover:bg-surface-elevated disabled:opacity-50'
                    "
                >
                    {{ page }}
                </button>
                <span v-else class="px-2 text-text-muted">...</span>
            </template>

            <!-- Next -->
            <button
                @click="goToPage(currentPage + 1)"
                :disabled="currentPage === lastPage || disabled"
                class="w-9 h-9 flex items-center justify-center rounded-lg text-text-muted hover:text-white hover:bg-surface-elevated disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-lg"
            >
                &gt;
            </button>
        </div>
    </div>
</template>

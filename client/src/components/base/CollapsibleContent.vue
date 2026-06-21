<script setup>
import { ref, computed, onMounted } from "vue";
import { useResizeObserver } from "@vueuse/core";

const props = defineProps({
    // Altura (px) que o conteúdo ocupa antes de colapsar.
    collapsedHeight: { type: Number, default: 320 },
});

const contentEl = ref(null);
const expanded = ref(false);
const fullHeight = ref(0);

// Só vale colapsar se o conteúdo for bem maior que o limite.
const canCollapse = computed(
    () => fullHeight.value > props.collapsedHeight + 48
);

const maxHeightStyle = computed(() => {
    if (!canCollapse.value) return "none";
    return expanded.value ? `${fullHeight.value}px` : `${props.collapsedHeight}px`;
});

function measure() {
    if (contentEl.value) fullHeight.value = contentEl.value.scrollHeight;
}

onMounted(measure);
useResizeObserver(contentEl, measure);
</script>

<template>
    <div>
        <div class="relative">
            <div
                ref="contentEl"
                class="overflow-hidden transition-[max-height] duration-300 ease-in-out"
                :style="{ maxHeight: maxHeightStyle }"
            >
                <slot></slot>
            </div>

            <!-- Fade na base enquanto colapsado -->
            <div
                v-if="canCollapse && !expanded"
                class="pointer-events-none absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-background-dark to-transparent"
            ></div>
        </div>

        <button
            v-if="canCollapse"
            type="button"
            class="mt-3 w-full flex items-center justify-center gap-1.5 rounded-lg border border-border-dark py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-surface-dark"
            @click="expanded = !expanded"
        >
            <span>{{ expanded ? "Ver menos" : "Ver mais" }}</span>
            <svg
                class="h-4 w-4 transition-transform duration-300"
                :class="{ 'rotate-180': expanded }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                />
            </svg>
        </button>
    </div>
</template>

<template>
    <div
        class="bg-card-bg border border-surface-elevated rounded-xl p-4 hover:border-primary/30 transition-colors"
    >
        <div class="flex items-start justify-between mb-3">
            <div class="flex-1">
                <h4 class="text-white font-semibold text-base mb-1">
                    {{ category.name }}
                </h4>

                <div
                    class="flex items-center gap-2 text-sm text-text-secondary"
                >
                    <!-- Gender Icon -->
                    <span class="flex items-center gap-1">
                        <span
                            class="material-symbols-outlined text-[16px] text-text-muted"
                        >
                            {{ genderIcon }}
                        </span>
                        <span>{{ genderLabel }}</span>
                    </span>

                    <!-- Age Range -->
                    <span class="text-text-muted">•</span>
                    <span>{{ ageRangeLabel }}</span>
                </div>
            </div>

            <!-- Status Badge -->
            <span
                :class="[
                    'px-2 py-1 rounded text-xs font-semibold',
                    category.active
                        ? 'bg-primary/10 text-primary'
                        : 'bg-red-500/10 text-red-400',
                ]"
            >
                {{ category.active ? "Ativa" : "Inativa" }}
            </span>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";

const props = defineProps({
    category: {
        type: Object,
        required: true,
    },
});

const genderIcon = computed(() => {
    const genderMap = {
        M: "male",
        F: "female",
        U: "transgender",
    };
    return genderMap[props.category.gender] || "person";
});

const genderLabel = computed(() => {
    const genderMap = {
        M: "Masculino",
        F: "Feminino",
        U: "Unissex",
    };
    return genderMap[props.category.gender] || "Unissex";
});

const ageRangeLabel = computed(() => {
    const min = props.category.min_age;
    const max = props.category.max_age;

    if (!min && !max) return "Todas as idades";
    if (min && !max) return `${min}+ anos`;
    if (!min && max) return `Até ${max} anos`;
    return `${min}-${max} anos`;
});
</script>

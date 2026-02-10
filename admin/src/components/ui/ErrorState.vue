<template>
    <div
        :class="[
            'flex flex-col items-center justify-center text-center',
            paddingClasses[padding],
        ]"
    >
        <span
            class="material-symbols-outlined text-red-400 mb-4"
            :class="iconSizeClasses[size]"
        >
            error
        </span>
        <h3 class="text-xl font-semibold text-white mb-2">
            {{ title }}
        </h3>
        <p class="text-text-muted mb-6 max-w-md">
            {{ message }}
        </p>
        <button
            v-if="showRetry"
            @click="$emit('retry')"
            class="px-6 py-3 bg-primary text-background-dark font-bold rounded-lg hover:brightness-110 transition-all flex items-center gap-2"
        >
            <span class="material-symbols-outlined text-lg">refresh</span>
            <span>Tentar Novamente</span>
        </button>
    </div>
</template>

<script setup>
const props = defineProps({
    title: {
        type: String,
        default: "Erro ao carregar",
    },
    message: {
        type: String,
        default: "Não foi possível carregar os dados. Tente novamente.",
    },
    showRetry: {
        type: Boolean,
        default: true,
    },
    size: {
        type: String,
        default: "md",
        validator: (value) => ["sm", "md", "lg"].includes(value),
    },
    padding: {
        type: String,
        default: "large",
        validator: (value) => ["small", "medium", "large"].includes(value),
    },
});

defineEmits(["retry"]);

const iconSizeClasses = {
    sm: "text-4xl",
    md: "text-6xl",
    lg: "text-8xl",
};

const paddingClasses = {
    small: "py-8",
    medium: "py-12",
    large: "py-20",
};
</script>

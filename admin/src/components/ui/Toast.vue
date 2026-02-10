<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="visible"
            :class="[
                'fixed right-4 top-20 z-50 max-w-md w-full sm:w-96',
                'bg-card-bg border rounded-xl p-4 shadow-2xl',
                'flex items-start gap-3',
                typeClasses[type].border,
            ]"
        >
            <div
                :class="[
                    'flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center',
                    typeClasses[type].bg,
                ]"
            >
                <span
                    class="material-symbols-outlined text-xl"
                    :class="typeClasses[type].icon"
                >
                    {{ typeClasses[type].iconName }}
                </span>
            </div>

            <div class="flex-1 min-w-0">
                <h3 v-if="title" class="text-white font-semibold text-sm mb-1">
                    {{ title }}
                </h3>
                <p class="text-text-secondary text-sm">
                    {{ message }}
                </p>
            </div>

            <button
                @click="close"
                class="flex-shrink-0 text-text-muted hover:text-white transition-colors"
            >
                <span class="material-symbols-outlined text-xl">close</span>
            </button>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted, watch } from "vue";

const props = defineProps({
    type: {
        type: String,
        default: "success",
        validator: (value) =>
            ["success", "error", "warning", "info"].includes(value),
    },
    title: {
        type: String,
        default: "",
    },
    message: {
        type: String,
        required: true,
    },
    duration: {
        type: Number,
        default: 5000,
    },
    onClose: {
        type: Function,
        default: () => {},
    },
});

const visible = ref(false);
let timeoutId = null;

const typeClasses = {
    success: {
        border: "border-primary/20",
        bg: "bg-primary/10",
        icon: "text-primary",
        iconName: "check_circle",
    },
    error: {
        border: "border-red-500/20",
        bg: "bg-red-500/10",
        icon: "text-red-500",
        iconName: "error",
    },
    warning: {
        border: "border-yellow-500/20",
        bg: "bg-yellow-500/10",
        icon: "text-yellow-500",
        iconName: "warning",
    },
    info: {
        border: "border-blue-500/20",
        bg: "bg-blue-500/10",
        icon: "text-blue-400",
        iconName: "info",
    },
};

const close = () => {
    visible.value = false;
    if (timeoutId) {
        clearTimeout(timeoutId);
    }
    setTimeout(() => {
        props.onClose();
    }, 200);
};

onMounted(() => {
    visible.value = true;
    if (props.duration > 0) {
        timeoutId = setTimeout(() => {
            close();
        }, props.duration);
    }
});

watch(
    () => props.duration,
    (newDuration) => {
        if (timeoutId) {
            clearTimeout(timeoutId);
        }
        if (newDuration > 0) {
            timeoutId = setTimeout(() => {
                close();
            }, newDuration);
        }
    },
);
</script>

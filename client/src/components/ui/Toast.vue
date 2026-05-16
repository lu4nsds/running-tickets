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
                'bg-surface-darker border rounded-xl p-4 shadow-2xl',
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
                <!-- success -->
                <svg v-if="type === 'success'" class="w-5 h-5" :class="typeClasses[type].icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
                <!-- error -->
                <svg v-else-if="type === 'error'" class="w-5 h-5" :class="typeClasses[type].icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <!-- warning -->
                <svg v-else-if="type === 'warning'" class="w-5 h-5" :class="typeClasses[type].icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
                <!-- info -->
                <svg v-else class="w-5 h-5" :class="typeClasses[type].icon" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>

            <div class="flex-1 min-w-0">
                <h3 v-if="title" class="text-white font-semibold text-sm mb-1">
                    {{ title }}
                </h3>
                <p class="text-slate-400 text-sm">
                    {{ message }}
                </p>
            </div>

            <button
                @click="close"
                class="flex-shrink-0 text-slate-500 hover:text-white transition-colors"
            >
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
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
    },
    error: {
        border: "border-red-500/20",
        bg: "bg-red-500/10",
        icon: "text-red-500",
    },
    warning: {
        border: "border-yellow-500/20",
        bg: "bg-yellow-500/10",
        icon: "text-yellow-500",
    },
    info: {
        border: "border-blue-500/20",
        bg: "bg-blue-500/10",
        icon: "text-blue-400",
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

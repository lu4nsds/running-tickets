<template>
    <Teleport to="body">
        <Transition name="modal">
            <div
                v-if="modelValue"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
                @click.self="close"
            >
                <!-- Overlay -->
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                ></div>

                <!-- Modal Card -->
                <div
                    class="relative bg-card-bg border border-surface-elevated rounded-xl shadow-2xl max-w-md w-full p-6 animate-modal-enter"
                >
                    <!-- Header -->
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <h3 class="text-xl font-bold text-white">
                                {{ title }}
                            </h3>
                            <p
                                v-if="subtitle"
                                class="text-sm text-text-secondary mt-1"
                            >
                                {{ subtitle }}
                            </p>
                        </div>
                        <button
                            @click="close"
                            class="text-text-muted hover:text-white transition-colors p-1"
                        >
                            <span class="material-symbols-outlined text-[20px]"
                                >close</span
                            >
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="text-text-secondary">
                        <slot />
                    </div>

                    <!-- Footer -->
                    <div
                        v-if="$slots.footer"
                        class="mt-6 pt-4 border-t border-surface-elevated"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: "",
    },
    closeOnOverlay: {
        type: Boolean,
        default: true,
    },
});

const emit = defineEmits(["update:modelValue"]);

const close = () => {
    if (props.closeOnOverlay) {
        emit("update:modelValue", false);
    }
};
</script>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.animate-modal-enter {
    animation: modal-scale 0.2s ease;
}

@keyframes modal-scale {
    from {
        transform: scale(0.95);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

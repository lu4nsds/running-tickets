<template>
    <Transition name="fade">
        <div
            v-if="show"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
        >
            <div class="w-full max-w-md mx-4 bg-card-bg border border-surface-elevated rounded-xl shadow-2xl p-6">
                <!-- Ícone -->
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                        :class="isUrgent ? 'bg-red-500/10' : 'bg-yellow-500/10'"
                    >
                        <span class="material-symbols-outlined text-[22px]"
                            :class="isUrgent ? 'text-red-400' : 'text-yellow-400'"
                        >schedule</span>
                    </div>
                    <div>
                        <h3 class="text-white font-semibold">Sessão expirando</h3>
                        <p class="text-text-muted text-xs">Sua sessão será encerrada automaticamente</p>
                    </div>
                </div>

                <!-- Countdown -->
                <div class="flex items-center justify-center my-6">
                    <div class="text-center">
                        <span
                            class="text-5xl font-mono font-bold tabular-nums"
                            :class="isUrgent ? 'text-red-400' : 'text-yellow-400'"
                        >{{ formattedTime }}</span>
                        <p class="text-text-muted text-sm mt-2">restantes</p>
                    </div>
                </div>

                <p class="text-text-secondary text-sm text-center mb-6">
                    Faça login novamente para continuar usando o sistema sem perder o acesso.
                </p>

                <!-- Ações -->
                <div class="flex gap-3">
                    <button
                        v-if="canDismiss"
                        @click="$emit('dismiss')"
                        class="flex-1 py-2.5 border border-surface-elevated text-text-muted rounded-lg hover:border-primary/40 hover:text-white transition-colors text-sm"
                    >
                        Continuar navegando
                    </button>
                    <button
                        @click="$emit('relogin')"
                        class="flex-1 py-2.5 bg-primary text-black font-semibold rounded-lg hover:brightness-110 transition-all text-sm"
                    >
                        Fazer login novamente
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({
    show:          { type: Boolean, required: true },
    formattedTime: { type: String,  required: true },
    secondsRemaining: { type: Number, default: null },
    canDismiss:    { type: Boolean, default: true },
})

defineEmits(['dismiss', 'relogin'])

const isUrgent = computed(() => props.secondsRemaining !== null && props.secondsRemaining <= 60)
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

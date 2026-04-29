import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const WARNING_SECONDS = 5 * 60
const FINAL_SECONDS   = 60

export function useSessionExpiry() {
    const auth   = useAuthStore()
    const router = useRouter()
    const route  = useRoute()

    const secondsRemaining = ref(null)
    let isLoggingOut = false
    let interval     = null

    const showWarning = computed(() =>
        secondsRemaining.value !== null && secondsRemaining.value <= WARNING_SECONDS
    )

    const canDismiss = computed(() =>
        secondsRemaining.value !== null && secondsRemaining.value > FINAL_SECONDS
    )

    const formattedTime = computed(() => {
        if (secondsRemaining.value === null) return ''
        const m = Math.floor(secondsRemaining.value / 60)
        const s = secondsRemaining.value % 60
        return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`
    })

    async function tick() {
        if (!auth.tokenExpiresAt || isLoggingOut) return

        const remaining = Math.floor((auth.tokenExpiresAt - Date.now()) / 1000)

        if (remaining <= 0) {
            isLoggingOut = true
            secondsRemaining.value = 0
            clearInterval(interval)
            await auth.logout()
            router.push({ path: '/login', query: { redirect: route.fullPath, reason: 'expired' } })
            return
        }

        secondsRemaining.value = remaining
    }

    onMounted(() => {
        tick()
        interval = setInterval(tick, 1000)
    })

    onUnmounted(() => {
        clearInterval(interval)
    })

    return { showWarning, canDismiss, secondsRemaining, formattedTime }
}

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-white">Comunicação</h1>
                <p class="text-text-secondary mt-1">
                    Gerencie os canais de notificação da plataforma
                </p>
            </div>
        </div>

        <!-- WhatsApp Card -->
        <div class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden">
            <!-- Card Header -->
            <div class="flex items-center justify-between px-6 py-5 border-b border-surface-elevated">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-surface rounded-xl flex items-center justify-center border border-surface-elevated">
                        <span class="material-symbols-outlined text-primary text-2xl">chat</span>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-white">WhatsApp</h2>
                        <p class="text-text-muted text-sm mt-0.5">
                            Envio automático de ingressos após confirmação de pagamento
                        </p>
                    </div>
                </div>

                <!-- Status Badge -->
                <span
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold"
                    :class="statusBadgeClass"
                >
                    <span class="w-1.5 h-1.5 rounded-full" :class="statusDotClass"></span>
                    {{ statusLabel }}
                </span>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Info + Actions -->
                    <div class="space-y-6">
                        <!-- Info Rows -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between py-3 border-b border-surface-elevated">
                                <span class="text-text-muted text-sm">Canal</span>
                                <span class="text-white text-sm font-medium">WhatsApp (Baileys)</span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-surface-elevated">
                                <span class="text-text-muted text-sm">Escopo</span>
                                <span class="text-white text-sm font-medium">Plataforma</span>
                            </div>
                            <div class="flex items-center justify-between py-3 border-b border-surface-elevated">
                                <span class="text-text-muted text-sm">Status</span>
                                <span class="text-sm font-medium" :class="statusTextClass">{{ statusLabel }}</span>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <span class="text-text-muted text-sm">Notificações automáticas</span>
                                <span class="text-white text-sm font-medium">Comprador + Participantes</span>
                            </div>
                        </div>

                        <!-- Error Message -->
                        <div
                            v-if="error"
                            class="flex items-start gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl"
                        >
                            <span class="material-symbols-outlined text-red-400 text-[20px] mt-0.5">error</span>
                            <p class="text-red-400 text-sm">{{ error }}</p>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3">
                            <button
                                v-if="sessionStatus !== 'open'"
                                :disabled="loading || sessionStatus === 'connecting'"
                                @click="connect"
                                class="flex items-center gap-2 px-6 py-3 bg-primary text-black rounded-xl font-semibold shadow-[0_0_20px_rgba(0,230,118,0.4)] hover:shadow-[0_0_30px_rgba(0,230,118,0.6)] transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                            >
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ loading ? 'sync' : 'link' }}
                                </span>
                                {{ loading ? 'Conectando…' : sessionStatus === 'connecting' ? 'Aguardando QR…' : 'Conectar' }}
                            </button>

                            <button
                                v-if="sessionStatus === 'open' || sessionStatus === 'connecting'"
                                :disabled="loading"
                                @click="disconnect"
                                class="flex items-center gap-2 px-4 py-2.5 border border-red-500 text-red-400 rounded-lg font-medium hover:bg-red-500/10 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span class="material-symbols-outlined text-[20px]">link_off</span>
                                Desconectar
                            </button>
                        </div>

                        <!-- Connected Info -->
                        <div
                            v-if="sessionStatus === 'open'"
                            class="flex items-start gap-3 p-4 bg-primary/5 border border-primary/20 rounded-xl"
                        >
                            <span class="material-symbols-outlined text-primary text-[20px] mt-0.5">check_circle</span>
                            <div>
                                <p class="text-white text-sm font-medium">Número conectado</p>
                                <p class="text-text-muted text-xs mt-0.5">
                                    As notificações de ingressos serão enviadas automaticamente após o pagamento ser confirmado.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Right: QR Code -->
                    <div class="flex flex-col items-center justify-center">
                        <!-- QR Loading -->
                        <div
                            v-if="sessionStatus === 'connecting' && !qrImage"
                            class="flex flex-col items-center gap-4 p-8"
                        >
                            <div class="w-48 h-48 bg-surface-elevated rounded-xl animate-pulse"></div>
                            <p class="text-text-muted text-sm">Gerando QR Code…</p>
                        </div>

                        <!-- QR Code -->
                        <div
                            v-else-if="qrImage"
                            class="flex flex-col items-center gap-4 p-6 bg-surface rounded-xl border border-surface-elevated"
                        >
                            <p class="text-text-secondary text-sm text-center">
                                Escaneie com o WhatsApp do celular
                            </p>
                            <div class="bg-white p-3 rounded-xl">
                                <img :src="qrImage" alt="QR Code WhatsApp" class="w-48 h-48" />
                            </div>
                            <div class="flex items-center gap-2 text-text-muted text-xs">
                                <span class="material-symbols-outlined text-[16px]">schedule</span>
                                O QR Code atualiza automaticamente a cada ~20s
                            </div>
                        </div>

                        <!-- Idle Placeholder -->
                        <div
                            v-else-if="sessionStatus !== 'open'"
                            class="flex flex-col items-center gap-4 py-10 text-center"
                        >
                            <div class="w-20 h-20 bg-surface rounded-xl flex items-center justify-center border border-surface-elevated">
                                <span class="material-symbols-outlined text-text-muted text-[48px]">qr_code</span>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">QR Code aparecerá aqui</p>
                                <p class="text-text-muted text-xs mt-1">
                                    Clique em Conectar para iniciar
                                </p>
                            </div>
                        </div>

                        <!-- Connected Placeholder -->
                        <div
                            v-else
                            class="flex flex-col items-center gap-4 py-10 text-center"
                        >
                            <div class="w-20 h-20 bg-primary/10 rounded-xl flex items-center justify-center border border-primary/20">
                                <span class="material-symbols-outlined text-primary text-[48px]">smartphone</span>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">Sessão ativa</p>
                                <p class="text-text-muted text-xs mt-1">
                                    Celular conectado e pronto para enviar mensagens
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- How it works card -->
        <div class="bg-card-bg border border-surface-elevated rounded-xl p-6">
            <h3 class="flex items-center gap-2 text-white font-semibold mb-5">
                <span class="material-symbols-outlined text-primary text-[20px]">info</span>
                Como funciona
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-primary text-sm font-bold">1</span>
                    </div>
                    <div>
                        <p class="text-white text-sm font-medium">Pagamento confirmado</p>
                        <p class="text-text-muted text-xs mt-1">
                            Após o Mercado Pago confirmar o pagamento, os ingressos são gerados automaticamente.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-primary text-sm font-bold">2</span>
                    </div>
                    <div>
                        <p class="text-white text-sm font-medium">Comprador notificado</p>
                        <p class="text-text-muted text-xs mt-1">
                            O comprador recebe uma mensagem com todos os ingressos do pedido no WhatsApp.
                        </p>
                    </div>
                </div>

                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-lg bg-primary/10 border border-primary/20 flex items-center justify-center flex-shrink-0 mt-0.5">
                        <span class="text-primary text-sm font-bold">3</span>
                    </div>
                    <div>
                        <p class="text-white text-sm font-medium">Participante notificado</p>
                        <p class="text-text-muted text-xs mt-1">
                            Cada participante recebe individualmente o seu ingresso, caso o número seja diferente do comprador.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { whatsappApi } from '@/api/admin/whatsapp'
import QRCode from 'qrcode'

const sessionStatus  = ref('closed')
const qrImage        = ref(null)
const loading        = ref(false)
const error          = ref(null)
const lastQrString   = ref(null)   // cache para evitar re-render sem necessidade

let pollInterval = null

const statusLabel = computed(() => ({
    connecting: 'Conectando',
    open:       'Conectado',
    closed:     'Desconectado',
    error:      'Erro',
}[sessionStatus.value] ?? 'Desconhecido'))

const statusBadgeClass = computed(() => ({
    connecting: 'bg-yellow-500/10 text-yellow-400',
    open:       'bg-primary/10 text-primary',
    closed:     'bg-surface-elevated text-text-muted',
    error:      'bg-red-500/10 text-red-400',
}[sessionStatus.value] ?? 'bg-surface-elevated text-text-muted'))

const statusDotClass = computed(() => ({
    connecting: 'bg-yellow-400 animate-pulse',
    open:       'bg-primary',
    closed:     'bg-text-muted',
    error:      'bg-red-400',
}[sessionStatus.value] ?? 'bg-text-muted'))

const statusTextClass = computed(() => ({
    connecting: 'text-yellow-400',
    open:       'text-primary',
    closed:     'text-text-muted',
    error:      'text-red-400',
}[sessionStatus.value] ?? 'text-text-muted'))

async function renderQr(rawQr) {
    if (!rawQr) { qrImage.value = null; lastQrString.value = null; return }
    // Só re-renderiza se a string do QR mudou de fato
    if (rawQr === lastQrString.value) return
    lastQrString.value = rawQr
    qrImage.value = await QRCode.toDataURL(rawQr, { width: 200, margin: 1 })
}

async function fetchStatus() {
    try {
        const { data } = await whatsappApi.status()
        sessionStatus.value = data.status
        await renderQr(data.qr)

        if (data.status !== 'connecting') stopPolling()
    } catch {
        // silencia erros de polling
    }
}

function startPolling() {
    stopPolling()
    pollInterval = setInterval(fetchStatus, 3000)
}

function stopPolling() {
    if (pollInterval) { clearInterval(pollInterval); pollInterval = null }
}

async function connect() {
    loading.value = true
    error.value   = null

    try {
        const { data } = await whatsappApi.connect()
        sessionStatus.value = data.status
        await renderQr(data.qr)

        if (data.status === 'connecting') startPolling()
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Erro ao conectar. Verifique se o gateway está rodando.'
    } finally {
        loading.value = false
    }
}

async function disconnect() {
    loading.value = true
    error.value   = null
    stopPolling()

    try {
        await whatsappApi.disconnect()
        sessionStatus.value = 'closed'
        lastQrString.value  = null
        qrImage.value       = null
    } catch (err) {
        error.value = err.response?.data?.message ?? 'Erro ao desconectar.'
    } finally {
        loading.value = false
    }
}

onMounted(async () => {
    await fetchStatus()
    // Se já estava conectando (sessão persistida), retoma o polling
    if (sessionStatus.value === 'connecting') startPolling()
})
onUnmounted(stopPolling)
</script>

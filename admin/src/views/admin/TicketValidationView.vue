<template>
    <div class="-m-4 flex h-[calc(100vh-4rem)] overflow-hidden">
        <!-- Main Scanner Area -->
        <div class="flex-1 flex flex-col min-w-0 p-4">
            <!-- Header with Breadcrumb + Title + Stats -->
            <div class="flex flex-col gap-2 relative z-10">
                <!-- Breadcrumb -->
                <nav class="flex text-sm font-medium text-text-muted mb-1">
                    <ol class="flex items-center gap-2 flex-wrap">
                        <li>
                            <router-link
                                :to="basePath + '/validate-tickets'"
                                class="text-primary hover:text-green-400 transition-colors"
                            >
                                Validação
                            </router-link>
                        </li>
                        <li>
                            <span class="material-symbols-outlined text-base"
                                >chevron_right</span
                            >
                        </li>
                        <li class="text-slate-400">{{ eventName }}</li>
                        <li>
                            <span class="material-symbols-outlined text-base"
                                >chevron_right</span
                            >
                        </li>
                        <li class="text-slate-500">Scanner</li>
                    </ol>
                </nav>

                <!-- Title + Stats -->
                <div
                    class="flex flex-col md:flex-row md:items-end md:justify-between gap-4"
                >
                    <div>
                        <h1
                            class="text-2xl md:text-4xl font-bold text-white tracking-tight"
                        >
                            Scanner de Validação
                        </h1>
                        <p class="text-text-muted mt-2 text-base md:text-lg">
                            {{ getSubtitle }}
                        </p>
                    </div>

                    <!-- Stats -->
                    <div
                        v-if="!isLoadingStats"
                        class="flex gap-4 md:gap-8 mb-0 md:mb-2"
                    >
                        <div class="flex flex-col items-end">
                            <span
                                class="text-xs text-text-muted uppercase tracking-wider font-semibold"
                                >Validado</span
                            >
                            <span class="text-lg font-bold text-white">{{
                                stats.validated
                            }}</span>
                        </div>
                        <div class="w-px h-8 bg-surface-elevated my-auto"></div>
                        <div class="flex flex-col items-end">
                            <span
                                class="text-xs text-text-muted uppercase tracking-wider font-semibold"
                                >Restante</span
                            >
                            <span class="text-lg font-bold text-primary">{{
                                stats.active
                            }}</span>
                        </div>
                        <div class="w-px h-8 bg-surface-elevated my-auto"></div>
                        <div class="flex flex-col items-end">
                            <span
                                class="text-xs text-text-muted uppercase tracking-wider font-semibold"
                                >Capacidade</span
                            >
                            <span class="text-lg font-bold text-white"
                                >{{ stats.validation_percentage }}%</span
                            >
                        </div>
                    </div>
                    <div v-else class="flex gap-4 md:gap-8 animate-pulse">
                        <div class="flex flex-col items-end">
                            <div class="h-3 bg-surface rounded w-16 mb-2"></div>
                            <div class="h-6 bg-surface rounded w-12"></div>
                        </div>
                        <div class="w-px h-8 bg-surface-elevated my-auto"></div>
                        <div class="flex flex-col items-end">
                            <div class="h-3 bg-surface rounded w-16 mb-2"></div>
                            <div class="h-6 bg-surface rounded w-12"></div>
                        </div>
                        <div class="w-px h-8 bg-surface-elevated my-auto"></div>
                        <div class="flex flex-col items-end">
                            <div class="h-3 bg-surface rounded w-20 mb-2"></div>
                            <div class="h-6 bg-surface rounded w-14"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Scanner Content -->
            <div
                class="flex-1 flex items-center justify-center relative overflow-hidden bg-background"
            >
                <!-- Grid Background -->
                <div
                    class="absolute inset-0 z-0 opacity-5 pointer-events-none"
                    style="
                        background-image:
                            linear-gradient(#252b4a 1px, transparent 1px),
                            linear-gradient(90deg, #252b4a 1px, transparent 1px);
                        background-size: 40px 40px;
                    "
                ></div>

                <!-- WAITING STATE -->
                <div
                    v-if="scannerState === 'waiting'"
                    class="w-full max-w-2xl flex flex-col items-center gap-6 md:gap-8 z-10 mt-0 md:-mt-10"
                >
                    <!-- QR Code Icon with Animation -->
                    <div class="relative group">
                        <div
                            class="absolute inset-0 bg-primary/10 rounded-2xl blur-2xl animate-pulse"
                        ></div>
                        <div
                            class="relative bg-card-bg border border-surface-elevated rounded-2xl p-8 md:p-16 flex items-center justify-center shadow-2xl"
                        >
                            <span
                                class="material-symbols-outlined text-primary/90"
                                :style="qrIconStyle"
                            >
                                qr_code_2
                            </span>
                            <!-- Scanning Line Animation -->
                            <div
                                class="absolute top-0 left-0 w-full h-0.5 bg-primary opacity-80 animate-scan"
                                style="box-shadow: 0 0 20px #00e676"
                            ></div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div
                        class="w-full max-w-md flex flex-col gap-3 md:gap-4 mt-4 md:mt-6"
                    >
                        <!-- Scan Button -->
                        <button
                            @click="startScanning"
                            :disabled="isScanning"
                            class="group w-full h-12 md:h-14 bg-card-bg border border-primary/50 hover:border-primary text-primary hover:bg-primary/5 rounded-lg flex items-center justify-center gap-3 transition-all duration-300 shadow-[0_0_10px_rgba(0,230,118,0.1)] hover:shadow-[0_0_20px_rgba(0,230,118,0.2)] disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span
                                class="material-symbols-outlined group-hover:scale-110 transition-transform"
                                >camera_alt</span
                            >
                            <span
                                class="text-base md:text-lg font-bold tracking-wide"
                                >Escanear QR Code</span
                            >
                        </button>

                        <!-- Manual Input -->
                        <div class="relative group">
                            <div
                                class="absolute -inset-0.5 bg-primary/20 rounded-lg blur opacity-0 group-focus-within:opacity-100 transition duration-500"
                            ></div>
                            <div
                                class="relative flex items-center bg-card-bg rounded-lg border border-surface-elevated group-focus-within:border-primary group-focus-within:ring-1 group-focus-within:ring-primary transition-all duration-300"
                            >
                                <span
                                    class="pl-3 md:pl-4 text-text-muted group-focus-within:text-primary transition-colors"
                                >
                                    <span class="material-symbols-outlined"
                                        >keyboard</span
                                    >
                                </span>
                                <input
                                    v-model="manualCode"
                                    @keyup.enter="handleManualCode"
                                    class="w-full bg-transparent border-none text-white placeholder-text-muted focus:ring-0 h-12 md:h-14 px-3 md:px-4 text-base md:text-lg font-medium font-mono tracking-wide"
                                    placeholder="Digitar código manualmente"
                                    type="text"
                                />
                                <div class="pr-3 md:pr-4 hidden sm:flex">
                                    <span
                                        class="px-2 py-1 rounded bg-background border border-surface-elevated text-xs font-bold text-text-muted font-mono group-focus-within:text-slate-300 group-focus-within:border-slate-600 transition-colors"
                                    >
                                        Enter
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SUCCESS STATE -->
                <div
                    v-else-if="scannerState === 'success'"
                    class="w-full max-w-2xl flex flex-col items-center gap-6 md:gap-8 z-10 animate-scale-in"
                >
                    <div class="relative w-full">
                        <div
                            class="absolute inset-0 bg-primary/5 rounded-3xl blur-3xl"
                        ></div>
                        <div
                            class="relative bg-card-bg border border-surface-elevated rounded-3xl p-6 md:p-10 flex flex-col items-center justify-center shadow-2xl overflow-hidden"
                        >
                            <div
                                class="absolute top-0 w-full h-1 bg-gradient-to-r from-transparent via-primary to-transparent opacity-50"
                            ></div>
                            <div
                                class="flex flex-col items-center text-center space-y-4 md:space-y-6"
                            >
                                <div class="relative">
                                    <div
                                        class="absolute inset-0 bg-primary/20 rounded-full blur-xl animate-pulse"
                                    ></div>
                                    <div
                                        class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-primary/10 border-2 border-primary flex items-center justify-center relative shadow-[0_0_30px_rgba(0,230,118,0.3)]"
                                    >
                                        <span
                                            class="material-symbols-outlined text-primary text-4xl md:text-5xl font-bold"
                                            >check</span
                                        >
                                    </div>
                                </div>
                                <h2
                                    class="text-2xl md:text-4xl font-bold text-primary tracking-tight"
                                    style="
                                        text-shadow: 0 0 20px
                                            rgba(0, 230, 118, 0.3);
                                    "
                                >
                                    Ticket Validado com Sucesso!
                                </h2>
                                <div
                                    class="w-full h-px bg-surface-elevated my-2"
                                ></div>
                                <div class="w-full space-y-4 md:space-y-6">
                                    <div class="flex flex-col gap-1">
                                        <span
                                            class="text-xs text-text-muted uppercase tracking-wider font-semibold"
                                            >Participante</span
                                        >
                                        <h3
                                            class="text-xl md:text-2xl font-bold text-white"
                                        >
                                            {{
                                                validationResult?.participant
                                                    ?.name || "N/A"
                                            }}
                                        </h3>
                                        <div
                                            v-if="
                                                validationResult?.participant
                                                    ?.cpf ||
                                                validationResult?.participant
                                                    ?.email
                                            "
                                            class="flex flex-wrap items-center justify-center gap-3 md:gap-4 text-xs md:text-sm text-slate-400 mt-2"
                                        >
                                            <span
                                                v-if="
                                                    validationResult
                                                        ?.participant?.cpf
                                                "
                                                class="flex items-center gap-1"
                                            >
                                                <span
                                                    class="material-symbols-outlined text-base"
                                                    >badge</span
                                                >
                                                {{
                                                    validationResult.participant
                                                        .cpf
                                                }}
                                            </span>
                                            <span
                                                v-if="
                                                    validationResult
                                                        ?.participant?.cpf &&
                                                    validationResult
                                                        ?.participant?.email
                                                "
                                                class="w-1 h-1 rounded-full bg-slate-600"
                                            ></span>
                                            <span
                                                v-if="
                                                    validationResult
                                                        ?.participant?.email
                                                "
                                                class="flex items-center gap-1"
                                            >
                                                <span
                                                    class="material-symbols-outlined text-base"
                                                    >mail</span
                                                >
                                                {{
                                                    validationResult.participant
                                                        .email
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <div
                                        v-if="
                                            validationResult?.ticket
                                                ?.ticket_type ||
                                            validationResult?.ticket?.category
                                        "
                                        class="grid grid-cols-2 gap-4 bg-background/50 rounded-xl p-4 border border-surface-elevated/50"
                                    >
                                        <div
                                            v-if="
                                                validationResult?.ticket
                                                    ?.ticket_type
                                            "
                                            class="flex flex-col items-center"
                                            :class="{
                                                'border-r border-surface-elevated/50':
                                                    validationResult?.ticket
                                                        ?.category,
                                            }"
                                        >
                                            <span
                                                class="text-xs text-text-muted uppercase tracking-wider font-semibold mb-1"
                                                >Tipo</span
                                            >
                                            <span
                                                class="text-base md:text-lg font-bold text-white"
                                                >{{
                                                    validationResult.ticket
                                                        .ticket_type.name
                                                }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="
                                                validationResult?.ticket
                                                    ?.category
                                            "
                                            class="flex flex-col items-center"
                                        >
                                            <span
                                                class="text-xs text-text-muted uppercase tracking-wider font-semibold mb-1"
                                                >Categoria</span
                                            >
                                            <span
                                                class="text-base md:text-lg font-bold text-white"
                                                >{{
                                                    validationResult.ticket
                                                        .category.name
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <div
                                        class="flex flex-col items-center gap-1"
                                    >
                                        <span
                                            class="text-xs text-slate-500 font-mono"
                                            >UUID:
                                            {{
                                                formatTicketCode(
                                                    validationResult?.ticket
                                                        ?.code,
                                                )
                                            }}</span
                                        >
                                        <span
                                            class="text-xs text-green-500/70 font-mono flex items-center gap-1"
                                        >
                                            <span
                                                class="material-symbols-outlined text-xs"
                                                >verified</span
                                            >
                                            Assinatura Digital Verificada
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full max-w-md space-y-4">
                        <button
                            @click="resetScanner"
                            class="group w-full h-14 md:h-16 bg-primary hover:bg-primary/90 text-black rounded-xl flex items-center justify-center gap-3 transition-all duration-300 shadow-[0_0_20px_rgba(0,230,118,0.6)] hover:scale-[1.02] active:scale-[0.98]"
                        >
                            <span
                                class="text-base md:text-lg font-bold tracking-wide uppercase"
                                >Próximo Ticket</span
                            >
                            <span
                                class="material-symbols-outlined font-bold group-hover:translate-x-1 transition-transform"
                                >arrow_forward</span
                            >
                        </button>
                    </div>
                </div>

                <!-- ERROR STATE -->
                <div
                    v-else-if="scannerState === 'error'"
                    class="w-full max-w-2xl flex flex-col items-center gap-6 md:gap-8 z-10 animate-scale-in"
                >
                    <div class="relative w-full">
                        <div
                            class="absolute inset-0 bg-red-500/5 rounded-3xl blur-3xl"
                        ></div>
                        <div
                            class="relative bg-card-bg border border-red-500/30 rounded-3xl p-6 md:p-10 flex flex-col items-center justify-center shadow-2xl overflow-hidden"
                        >
                            <div
                                class="absolute top-0 w-full h-1 bg-gradient-to-r from-transparent via-red-500 to-transparent opacity-50"
                            ></div>
                            <div
                                class="flex flex-col items-center text-center space-y-4 md:space-y-6"
                            >
                                <div class="relative">
                                    <div
                                        class="absolute inset-0 bg-red-500/20 rounded-full blur-xl animate-pulse"
                                    ></div>
                                    <div
                                        class="w-20 h-20 md:w-24 md:h-24 rounded-full bg-red-500/10 border-2 border-red-500 flex items-center justify-center relative shadow-[0_0_30px_rgba(239,68,68,0.3)]"
                                    >
                                        <span
                                            class="material-symbols-outlined text-red-500 text-4xl md:text-5xl font-bold"
                                            >close</span
                                        >
                                    </div>
                                </div>
                                <h2
                                    class="text-2xl md:text-4xl font-bold text-red-500 tracking-tight"
                                >
                                    Ticket Inválido!
                                </h2>
                                <div
                                    class="w-full h-px bg-surface-elevated my-2"
                                ></div>
                                <div class="w-full space-y-4">
                                    <div class="flex flex-col gap-2">
                                        <span
                                            class="text-xs text-text-muted uppercase tracking-wider font-semibold"
                                            >Motivo</span
                                        >
                                        <p class="text-lg text-slate-300">
                                            {{ errorDetails?.message }}
                                        </p>
                                        <span
                                            v-if="errorDetails?.status_label"
                                            class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-red-500/10 text-red-400 text-sm font-medium border border-red-500/30 mt-2"
                                        >
                                            {{ errorDetails.status_label }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="errorDetails?.code"
                                        class="flex flex-col items-center gap-1 pt-4"
                                    >
                                        <span
                                            class="text-xs text-slate-500 font-mono"
                                            >Código:
                                            {{
                                                formatTicketCode(
                                                    errorDetails.code,
                                                )
                                            }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-full max-w-md">
                        <button
                            @click="resetScanner"
                            class="group w-full h-14 md:h-16 bg-card-bg border border-red-500/50 hover:border-red-500 text-red-400 hover:bg-red-500/5 rounded-xl flex items-center justify-center gap-3 transition-all duration-300"
                        >
                            <span
                                class="material-symbols-outlined group-hover:scale-110 transition-transform"
                                >refresh</span
                            >
                            <span
                                class="text-base md:text-lg font-bold tracking-wide"
                                >Tentar Novamente</span
                            >
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scanner Modal -->
            <div
                v-if="showScannerModal"
                class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 flex items-center justify-center p-4"
                @click.self="stopScanning"
            >
                <div
                    class="bg-card-bg rounded-2xl border border-surface-elevated p-6 max-w-2xl w-full shadow-2xl"
                >
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-white">
                            Scanner de QR Code
                        </h3>
                        <button
                            @click="stopScanning"
                            class="text-slate-400 hover:text-white transition-colors"
                        >
                            <span class="material-symbols-outlined">close</span>
                        </button>
                    </div>
                    <div
                        id="qr-reader"
                        class="rounded-lg overflow-hidden border-2 border-primary/30"
                    ></div>
                    <p class="text-center text-text-muted text-sm mt-4">
                        Aponte a câmera para o QR Code do ticket
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Sidebar - History -->
        <aside
            class="w-80 bg-card-bg border-l border-surface-elevated shrink-0 hidden xl:flex flex-col h-[calc(100vh-4rem)]"
        >
            <div class="p-4 border-b border-surface-elevated shrink-0">
                <h3
                    class="text-lg font-bold text-white flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-text-muted"
                        >history</span
                    >
                    Últimas Validações
                </h3>
            </div>

            <!-- History List -->
            <div class="flex-1 overflow-y-auto p-4 space-y-3 bg-background/30">
                <div
                    v-if="validationHistory.length === 0"
                    class="flex flex-col items-center justify-center py-16 text-center"
                >
                    <span
                        class="material-symbols-outlined text-text-muted text-4xl mb-4"
                        >receipt_long</span
                    >
                    <p class="text-text-muted text-sm">
                        Nenhuma validação ainda
                    </p>
                </div>

                <div
                    v-for="(item, index) in validationHistory"
                    :key="item.code + item.timestamp"
                    class="bg-card-bg rounded-lg p-4 border transition-colors"
                    :class="[
                        item.valid
                            ? 'border-surface-elevated hover:border-primary/30 group'
                            : 'border-surface-elevated hover:border-red-500/30 group',
                        index === 0 ? 'opacity-100' : '',
                        index === 1 ? 'opacity-90' : '',
                        index === 2 ? 'opacity-75' : '',
                        index >= 3 ? 'opacity-60' : '',
                    ]"
                >
                    <div class="flex justify-between items-start mb-2">
                        <span
                            class="font-bold font-mono transition-colors"
                            :class="
                                item.valid
                                    ? 'text-white group-hover:text-primary'
                                    : 'text-slate-300 group-hover:text-red-400'
                            "
                        >
                            {{ formatTicketCode(item.code) }}
                        </span>
                        <span class="text-xs text-text-muted">{{
                            item.time
                        }}</span>
                    </div>
                    <div class="flex items-center gap-2 mb-2">
                        <div
                            class="w-2 h-2 rounded-full"
                            :class="
                                item.valid
                                    ? 'bg-primary shadow-[0_0_8px_#00e676]'
                                    : 'bg-red-500'
                            "
                        ></div>
                        <span
                            class="text-sm font-medium"
                            :class="
                                item.valid ? 'text-primary' : 'text-red-400'
                            "
                        >
                            {{ item.message }}
                        </span>
                    </div>
                    <p v-if="item.participant" class="text-xs text-text-muted">
                        {{ item.participant }}
                    </p>
                </div>
            </div>
        </aside>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import axios from "@/api/axios";
import { Html5Qrcode } from "html5-qrcode";

const route = useRoute();
const authStore = useAuthStore();

const eventId = computed(() => route.params.eventId);
const eventName = ref("Carregando...");
const manualCode = ref("");
const validationHistory = ref([]);
const isLoadingStats = ref(true);
const isMobile = ref(window.innerWidth < 768);

// Scanner states
const scannerState = ref("waiting"); // 'waiting' | 'scanning' | 'success' | 'error'
const validationResult = ref(null);
const errorDetails = ref(null);
const showScannerModal = ref(false);
const isScanning = ref(false);
let html5QrCode = null;
let autoResetTimeout = null;

const stats = ref({
    total: 0,
    validated: 0,
    active: 0,
    validation_percentage: 0,
});

// Subtitle dinâmico baseado no estado
const getSubtitle = computed(() => {
    switch (scannerState.value) {
        case "waiting":
            return "Aguardando leitura do ticket...";
        case "success":
            return "Resultado da leitura do ticket";
        case "error":
            return "Erro na validação do ticket";
        default:
            return "Aguardando leitura do ticket...";
    }
});

// Estilo do ícone QR code
const qrIconStyle = computed(() => ({
    fontSize: isMobile.value ? "100px" : "140px",
    fontVariationSettings: '"wght" 200',
}));

// Detectar mudanças no tamanho da tela
const handleResize = () => {
    isMobile.value = window.innerWidth < 768;
};

const basePath = computed(() =>
    authStore.isSuperAdmin ? "/admin" : "/organizer",
);

const fetchEventData = async () => {
    isLoadingStats.value = true;
    try {
        const endpoint = authStore.isSuperAdmin
            ? `/admin/events/${eventId.value}`
            : `/organizer/events/${eventId.value}`;

        const response = await axios.get(endpoint);
        const event = response.data.data;

        eventName.value = event.title;

        if (event.ticket_stats) {
            stats.value = event.ticket_stats;
        }
    } catch (error) {
        console.error("Erro ao buscar evento:", error);
    } finally {
        isLoadingStats.value = false;
    }
};

// Iniciar scanner de câmera
const startScanning = async () => {
    try {
        isScanning.value = true;
        showScannerModal.value = true;

        // Aguardar o DOM atualizar
        await new Promise((resolve) => setTimeout(resolve, 100));

        html5QrCode = new Html5Qrcode("qr-reader");

        const config = {
            fps: 10,
            qrbox: { width: 250, height: 250 },
            aspectRatio: 1.0,
        };

        // Verificar se o navegador suporta acesso à câmera
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            throw new Error("Navegador não suporta acesso à câmera");
        }

        // Verificar se está em contexto seguro (HTTPS ou localhost)
        const isSecureContext =
            window.isSecureContext ||
            window.location.protocol === "https:" ||
            window.location.hostname === "localhost" ||
            window.location.hostname === "127.0.0.1";

        if (!isSecureContext) {
            throw new Error("HTTPS_REQUIRED");
        }

        await html5QrCode.start(
            { facingMode: "environment" }, // Câmera traseira
            config,
            onScanSuccess,
            onScanError,
        );
    } catch (error) {
        console.error("Erro ao iniciar scanner:", error);
        showScannerModal.value = false;
        isScanning.value = false;

        // Mensagem específica para problemas de HTTPS
        if (error.message === "HTTPS_REQUIRED") {
            alert(
                "⚠️ Câmera bloqueada!\n\n" +
                    "Navegadores móveis exigem HTTPS para acessar a câmera.\n\n" +
                    "Soluções:\n" +
                    "1. Use o input manual abaixo\n" +
                    "2. Configure HTTPS no servidor\n" +
                    "3. Acesse via computador/localhost",
            );
            return;
        }

        // Tentar câmera frontal como fallback
        if (
            error.name === "NotAllowedError" ||
            error.name === "PermissionDeniedError"
        ) {
            alert(
                "❌ Permissão negada!\n\n" +
                    "Por favor, permita o acesso à câmera nas configurações do navegador.",
            );
            return;
        }

        // Tentar qualquer câmera disponível
        try {
            const cameras = await Html5Qrcode.getCameras();
            if (cameras && cameras.length > 0) {
                showScannerModal.value = true;
                isScanning.value = true;
                html5QrCode = new Html5Qrcode("qr-reader");

                await html5QrCode.start(
                    cameras[0].id,
                    config,
                    onScanSuccess,
                    onScanError,
                );
            } else {
                throw new Error("Nenhuma câmera encontrada");
            }
        } catch (fallbackError) {
            console.error("Erro no fallback:", fallbackError);
            alert(
                "📱 Não foi possível acessar a câmera.\n\n" +
                    "Use o campo de input manual abaixo para validar.",
            );
        }
    }
};

// Callback quando QR Code é lido com sucesso
const onScanSuccess = (decodedText) => {
    stopScanning();
    validateTicket(decodedText.trim());
};

// Callback para erros de scan (pode ser ignorado, ocorre quando não detecta QR)
const onScanError = () => {
    // Silencioso - erros de scan são normais quando não há QR code no frame
};

// Parar scanner
const stopScanning = async () => {
    try {
        if (html5QrCode && html5QrCode.isScanning) {
            await html5QrCode.stop();
            html5QrCode.clear();
        }
    } catch (error) {
        console.error("Erro ao parar scanner:", error);
    } finally {
        showScannerModal.value = false;
        isScanning.value = false;
        html5QrCode = null;
    }
};

// Reset do scanner para estado inicial
const resetScanner = () => {
    if (autoResetTimeout) {
        clearTimeout(autoResetTimeout);
        autoResetTimeout = null;
    }
    scannerState.value = "waiting";
    validationResult.value = null;
    errorDetails.value = null;
};

// Validação manual via input
const handleManualCode = async () => {
    if (!manualCode.value.trim()) return;

    const code = manualCode.value.trim();
    await validateTicket(code);
    manualCode.value = "";
};

// Função principal de validação
const validateTicket = async (code) => {
    const now = new Date();
    const time = now.toLocaleTimeString("pt-BR", {
        hour: "2-digit",
        minute: "2-digit",
    });

    try {
        const response = await axios.post(`/tickets/${code}/validate`);

        if (response.data.valid) {
            // Sucesso
            validationResult.value = response.data;
            scannerState.value = "success";

            // Adicionar ao histórico
            validationHistory.value.unshift({
                code: code,
                valid: true,
                message: "Acesso Permitido",
                participant: response.data.participant?.name || null,
                time: time,
                timestamp: now.getTime(),
            });

            // Atualizar estatísticas
            stats.value.validated++;
            stats.value.active--;
            stats.value.validation_percentage =
                stats.value.total > 0
                    ? Math.round(
                          (stats.value.validated / stats.value.total) * 100,
                      )
                    : 0;

            // Auto-reset após 10 segundos
            autoResetTimeout = setTimeout(() => {
                resetScanner();
            }, 10000);
        }
    } catch (error) {
        // Erro na validação
        const errorMessage = error.response?.data?.message || "Ticket inválido";
        const statusLabel = error.response?.data?.status_label || null;
        const status = error.response?.data?.status || null;

        errorDetails.value = {
            message: errorMessage,
            status_label: statusLabel,
            status: status,
            code: code,
        };
        scannerState.value = "error";

        // Adicionar ao histórico como erro
        validationHistory.value.unshift({
            code: code,
            valid: false,
            message: errorMessage,
            participant: null,
            time: time,
            timestamp: now.getTime(),
        });

        // Auto-reset após 8 segundos
        autoResetTimeout = setTimeout(() => {
            resetScanner();
        }, 8000);
    }

    // Manter apenas os últimos 20
    if (validationHistory.value.length > 20) {
        validationHistory.value = validationHistory.value.slice(0, 20);
    }
};

const formatTicketCode = (code) => {
    if (!code) return "N/A";
    // Se for UUID, pegar apenas os primeiros 8 caracteres
    if (code.length > 12) {
        return `${code.substring(0, 8).toUpperCase()}`;
    }
    return code.toUpperCase();
};

onMounted(() => {
    fetchEventData();
    window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleResize);
    stopScanning();
    if (autoResetTimeout) {
        clearTimeout(autoResetTimeout);
    }
});
</script>

<style scoped>
@keyframes scan {
    0%,
    100% {
        top: 10%;
        opacity: 0;
    }
    10% {
        opacity: 1;
    }
    90% {
        opacity: 1;
    }
    50% {
        top: 90%;
    }
}

.animate-scan {
    animation: scan 2.5s ease-in-out infinite;
}

@keyframes scaleIn {
    0% {
        transform: scale(0.95);
        opacity: 0;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.animate-scale-in {
    animation: scaleIn 0.3s ease-out forwards;
}
</style>

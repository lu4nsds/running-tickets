<template>
    <div class="space-y-6">
        <!-- Header -->
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">
                Configurações de Pagamento
            </h1>
            <p class="text-text-muted">
                Gerencie as configurações de recebimento de cada evento
            </p>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="space-y-4">
            <SkeletonCard v-for="i in 3" :key="i" type="info-section" />
        </div>

        <!-- Error State -->
        <ErrorState
            v-else-if="error"
            title="Erro ao carregar configurações"
            :message="error"
            @retry="fetchPaymentSettings"
        />

        <!-- Content -->
        <div v-else-if="data" class="space-y-6">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-text-muted text-sm">
                                Total de Eventos
                            </p>
                            <p class="text-3xl font-bold text-white mt-2">
                                {{ data.summary.total_events }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 rounded-full bg-surface flex items-center justify-center"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-2xl"
                            >
                                event
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-text-muted text-sm">Configurados</p>
                            <p class="text-3xl font-bold text-primary mt-2">
                                {{ data.summary.configured }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 rounded-full bg-primary/10 flex items-center justify-center"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-2xl"
                            >
                                check_circle
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-text-muted text-sm">Pendentes</p>
                            <p class="text-3xl font-bold text-yellow-500 mt-2">
                                {{ data.summary.pending }}
                            </p>
                        </div>
                        <div
                            class="w-12 h-12 rounded-full bg-yellow-500/10 flex items-center justify-center"
                        >
                            <span
                                class="material-symbols-outlined text-yellow-500 text-2xl"
                            >
                                warning
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Events List -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h2 class="text-xl font-bold text-white mb-6">
                    Eventos e Status de Configuração
                </h2>

                <div class="space-y-4">
                    <div
                        v-for="event in data.events"
                        :key="event.id"
                        class="bg-surface/30 border border-surface-elevated rounded-lg p-6 hover:bg-surface/50 transition-colors"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex-1">
                                <div class="flex items-center gap-3 mb-3">
                                    <h3
                                        class="text-lg font-semibold text-white"
                                    >
                                        {{ event.title }}
                                    </h3>
                                    <span
                                        :class="[
                                            'inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold',
                                            event.has_payout_config
                                                ? 'bg-primary/10 text-primary'
                                                : 'bg-yellow-500/10 text-yellow-500',
                                        ]"
                                    >
                                        <span
                                            class="material-symbols-outlined text-sm"
                                        >
                                            {{
                                                event.has_payout_config
                                                    ? "check_circle"
                                                    : "warning"
                                            }}
                                        </span>
                                        {{
                                            event.has_payout_config
                                                ? "Configurado"
                                                : "Pendente"
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center gap-4 text-sm text-text-muted mb-4"
                                >
                                    <span class="flex items-center gap-1">
                                        <span
                                            class="material-symbols-outlined text-sm"
                                        >
                                            calendar_today
                                        </span>
                                        {{ formatDate(event.date_start) }}
                                    </span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded text-xs font-medium',
                                            getStatusClass(event.status),
                                        ]"
                                    >
                                        {{ event.status }}
                                    </span>
                                </div>

                                <!-- Payout Info -->
                                <div
                                    v-if="
                                        event.has_payout_config &&
                                        event.payout_summary
                                    "
                                    class="bg-background-dark/50 rounded-lg p-4 space-y-2 min-h-[120px] flex flex-col justify-center"
                                >
                                    <div class="flex items-center gap-2">
                                        <span class="text-text-muted text-sm"
                                            >Método:</span
                                        >
                                        <span
                                            class="text-white font-medium capitalize"
                                        >
                                            {{ event.payout_summary.method }}
                                        </span>
                                    </div>
                                    <div
                                        v-if="event.payout_summary.provider"
                                        class="flex items-center gap-2"
                                    >
                                        <span class="text-text-muted text-sm"
                                            >Provedor:</span
                                        >
                                        <span
                                            class="text-white font-medium capitalize"
                                        >
                                            {{ event.payout_summary.provider }}
                                        </span>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="text-text-muted text-sm"
                                            >Modo:</span
                                        >
                                        <span
                                            class="text-white font-medium capitalize"
                                        >
                                            {{
                                                event.payout_summary
                                                    .payout_mode === "direct"
                                                    ? "Direto"
                                                    : "Plataforma"
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="bg-yellow-500/5 border border-yellow-500/20 rounded-lg p-4 min-h-[120px] flex items-center"
                                >
                                    <p class="text-yellow-500 text-sm">
                                        <span
                                            class="material-symbols-outlined text-sm align-middle mr-1"
                                        >
                                            info
                                        </span>
                                        Configure o recebimento para que este
                                        evento possa processar vendas
                                    </p>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="flex flex-col gap-3">
                                <button
                                    v-if="
                                        event.has_payout_config &&
                                        event.payout_summary?.payout_mode !==
                                            'platform'
                                    "
                                    @click="editPayoutConfig(event.id)"
                                    class="px-4 py-2.5 bg-surface hover:bg-surface-elevated border border-surface-elevated rounded-lg text-white font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap min-w-[140px]"
                                >
                                    <span
                                        class="material-symbols-outlined text-[18px]"
                                    >
                                        edit
                                    </span>
                                    Editar
                                </button>
                                <button
                                    v-else-if="!event.has_payout_config"
                                    @click="configurePayment(event.id)"
                                    class="px-4 py-2.5 bg-primary hover:brightness-110 text-background-dark font-semibold rounded-lg transition-all flex items-center justify-center gap-2 whitespace-nowrap min-w-[140px]"
                                >
                                    <span
                                        class="material-symbols-outlined text-[18px]"
                                    >
                                        settings
                                    </span>
                                    Configurar
                                </button>

                                <button
                                    @click="viewEventDetails(event.id)"
                                    class="px-4 py-2.5 bg-surface hover:bg-surface-elevated border border-surface-elevated rounded-lg text-text-secondary hover:text-white font-medium transition-colors flex items-center justify-center gap-2 whitespace-nowrap min-w-[140px]"
                                >
                                    <span
                                        class="material-symbols-outlined text-[18px]"
                                    >
                                        visibility
                                    </span>
                                    Ver Evento
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div
                        v-if="data.events.length === 0"
                        class="text-center py-12"
                    >
                        <span
                            class="material-symbols-outlined text-text-muted text-6xl mb-4 block"
                        >
                            event_busy
                        </span>
                        <p class="text-text-muted">Nenhum evento encontrado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "@/api/axios";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";
import { useLoading } from "@/composables/useLoading";
import ErrorState from "@/components/ui/ErrorState.vue";
import SkeletonCard from "@/components/ui/SkeletonCard.vue";

const router = useRouter();
const { isLoading, error, withLoading } = useLoading(true);

const data = ref(null);

const fetchPaymentSettings = async () => {
    await withLoading(async () => {
        const response = await api.get(
            API_ENDPOINTS.ORGANIZER.PAYMENT_SETTINGS,
        );
        data.value = response.data;
    });
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const getStatusClass = (status) => {
    const classes = {
        ativo: "bg-primary/10 text-primary",
        rascunho: "bg-surface text-text-muted",
        cancelado: "bg-red-500/10 text-red-500",
        encerrado: "bg-surface text-text-muted",
    };
    return classes[status] || "bg-surface text-text-muted";
};

const configurePayment = (eventId) => {
    router.push(`/organizer/events/${eventId}/payout/config`);
};

const editPayoutConfig = (eventId) => {
    router.push(`/organizer/events/${eventId}/payout/config`);
};

const viewEventDetails = (eventId) => {
    router.push(`/organizer/events/${eventId}`);
};

onMounted(() => {
    fetchPaymentSettings();
});
</script>

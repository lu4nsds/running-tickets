<template>
    <div class="space-y-6">
        <!-- Proteção: Apenas Admin pode editar -->
        <ErrorState
            v-if="!authStore.canEditPaymentSettings"
            title="Acesso Negado"
            message="Apenas administradores do organizador podem configurar pagamentos. Entre em contato com o administrador."
        />

        <div v-else-if="isLoading" class="space-y-4">
            <SkeletonCard v-for="i in 2" :key="i" type="info-section" />
        </div>

        <ErrorState
            v-else-if="error"
            title="Erro ao carregar configuração"
            :message="error"
            @retry="loadData"
        />

        <div v-else class="space-y-6">
            <div>
                <router-link
                    to="/organizer/payment-settings"
                    class="text-primary hover:underline mb-4 inline-flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-sm"
                        >arrow_back</span
                    >
                    Voltar para lista
                </router-link>
                <h1 class="text-2xl sm:text-3xl font-bold text-white mb-2 mt-4">
                    Configurar Recebimento
                </h1>
                <p class="text-sm sm:text-base text-text-muted">
                    {{ event?.title }}
                </p>
            </div>

            <div
                v-if="!payoutConfig"
                class="bg-yellow-500/10 border border-yellow-500/20 rounded-xl p-4 sm:p-6"
            >
                <p class="text-sm sm:text-base text-yellow-500">
                    <span
                        class="material-symbols-outlined text-sm align-middle mr-1"
                    >
                        info
                    </span>
                    O modo de pagamento ainda não foi configurado pelo
                    administrador.
                </p>
            </div>

            <div
                v-else-if="payoutConfig.payout_mode === 'platform'"
                class="bg-card-bg border border-surface-elevated rounded-xl p-6 sm:p-8"
            >
                <div class="text-center space-y-4">
                    <div
                        class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary/10 flex items-center justify-center mx-auto"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-4xl sm:text-5xl"
                        >
                            account_balance
                        </span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-bold text-white">
                        Recebimento via Plataforma
                    </h2>
                    <p
                        class="text-sm sm:text-base text-text-secondary max-w-2xl mx-auto"
                    >
                        Este evento está configurado para receber pagamentos
                        através da conta Mercado Pago da plataforma Running
                        Tickets. Os valores serão repassados conforme acordo
                        estabelecido.
                    </p>
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary/10 text-primary rounded-full text-sm font-semibold"
                    >
                        <span class="material-symbols-outlined text-sm"
                            >check_circle</span
                        >
                        Configurado
                    </div>
                </div>
            </div>

            <div
                v-else-if="payoutConfig.payout_mode === 'direct'"
                class="space-y-6"
            >
                <div
                    v-if="event?.status !== 'ativo'"
                    class="bg-blue-500/10 border border-blue-500/20 rounded-xl p-4 sm:p-6"
                >
                    <p class="text-sm sm:text-base text-blue-400">
                        <span
                            class="material-symbols-outlined text-sm align-middle mr-1"
                        >
                            info
                        </span>
                        Configurar credenciais válidas ativará seu evento
                        automaticamente
                    </p>
                </div>

                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-4 sm:p-6 space-y-6"
                >
                    <div>
                        <h2
                            class="text-lg sm:text-xl font-bold text-white mb-2"
                        >
                            Credenciais do Mercado Pago
                        </h2>
                        <p class="text-text-muted text-sm">
                            Adicione suas credenciais para receber pagamentos
                            diretamente na sua conta
                        </p>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-white font-medium mb-2">
                                Public Key
                            </label>
                            <input
                                v-model="formData.public_key"
                                type="text"
                                placeholder="APP_USR-xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"
                                class="w-full px-4 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:ring-2 focus:ring-primary"
                            />
                        </div>

                        <div>
                            <label class="block text-white font-medium mb-2">
                                Access Token
                            </label>
                            <div class="relative">
                                <input
                                    v-model="formData.access_token"
                                    :type="
                                        showAccessToken ? 'text' : 'password'
                                    "
                                    placeholder="APP_USR-xxxxxxxxxx-xxxxxx-xxxxxxxxxxxxxxxxxxxxxxxxxx-xxxxxxxxx"
                                    class="w-full px-4 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:ring-2 focus:ring-primary pr-12"
                                />
                                <button
                                    @click="showAccessToken = !showAccessToken"
                                    type="button"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-text-muted hover:text-white"
                                >
                                    <span
                                        class="material-symbols-outlined text-xl"
                                    >
                                        {{
                                            showAccessToken
                                                ? "visibility_off"
                                                : "visibility"
                                        }}
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="validationResult"
                        class="rounded-lg p-4"
                        :class="[
                            validationResult.valid
                                ? 'bg-primary/10 border border-primary/20'
                                : 'bg-red-500/10 border border-red-500/20',
                        ]"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="material-symbols-outlined mt-0.5"
                                :class="[
                                    validationResult.valid
                                        ? 'text-primary'
                                        : 'text-red-500',
                                ]"
                            >
                                {{
                                    validationResult.valid
                                        ? "check_circle"
                                        : "error"
                                }}
                            </span>
                            <div class="flex-1">
                                <p
                                    class="font-semibold mb-1"
                                    :class="[
                                        validationResult.valid
                                            ? 'text-primary'
                                            : 'text-red-500',
                                    ]"
                                >
                                    {{
                                        validationResult.valid
                                            ? "Credenciais válidas!"
                                            : "Erro na validação"
                                    }}
                                </p>
                                <p
                                    v-if="
                                        validationResult.valid &&
                                        validationResult.account_info
                                    "
                                    class="text-sm text-text-secondary"
                                >
                                    Email:
                                    {{ validationResult.account_info.email
                                    }}<br />
                                    Usuário: @{{
                                        validationResult.account_info.nickname
                                    }}
                                </p>
                                <p v-else class="text-sm text-red-400">
                                    {{ validationResult.error }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3">
                        <button
                            @click="testCredentials"
                            :disabled="!formData.access_token || isTesting"
                            class="w-full sm:w-auto px-6 py-3 bg-surface hover:bg-surface-elevated border border-surface-elevated rounded-lg text-white font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span
                                v-if="isTesting"
                                class="material-symbols-outlined text-sm animate-spin"
                            >
                                progress_activity
                            </span>
                            <span
                                v-else
                                class="material-symbols-outlined text-sm"
                            >
                                verified_user
                            </span>
                            {{
                                isTesting ? "Testando..." : "Testar Credenciais"
                            }}
                        </button>

                        <button
                            @click="saveConfiguration"
                            :disabled="!validationResult?.valid || isSaving"
                            class="w-full sm:flex-1 px-6 py-3 bg-primary hover:brightness-110 text-background-dark font-semibold rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        >
                            <span
                                v-if="isSaving"
                                class="material-symbols-outlined text-sm animate-spin"
                            >
                                progress_activity
                            </span>
                            <span
                                v-else
                                class="material-symbols-outlined text-sm"
                            >
                                save
                            </span>
                            {{
                                isSaving ? "Salvando..." : "Salvar Configuração"
                            }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import api from "@/api/axios";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";
import { useLoading } from "@/composables/useLoading";
import { useToast } from "@/composables/useToast";
import { useAuthStore } from "@/stores/auth";
import ErrorState from "@/components/ui/ErrorState.vue";
import SkeletonCard from "@/components/ui/SkeletonCard.vue";

const router = useRouter();
const route = useRoute();
const { isLoading, error, withLoading } = useLoading(true);
const toast = useToast();
const authStore = useAuthStore();

const event = ref(null);
const payoutConfig = ref(null);
const showAccessToken = ref(false);
const isTesting = ref(false);
const isSaving = ref(false);
const validationResult = ref(null);

const formData = ref({
    public_key: "",
    access_token: "",
});

const loadData = async () => {
    await withLoading(async () => {
        const eventId = route.params.id;

        const [eventResponse, payoutResponse] = await Promise.all([
            api.get(API_ENDPOINTS.ORGANIZER.EVENT.DETAIL(eventId)),
            api
                .get(API_ENDPOINTS.ORGANIZER.EVENT.PAYOUT(eventId))
                .catch(() => ({ data: null })),
        ]);

        event.value = eventResponse.data.data || eventResponse.data;
        payoutConfig.value = payoutResponse.data;

        if (
            payoutConfig.value?.payout_mode === "direct" &&
            payoutConfig.value?.details?.access_token_masked
        ) {
            formData.value.public_key =
                payoutConfig.value.details.public_key || "";
        }
    });
};

const testCredentials = async () => {
    if (!formData.value.access_token) return;

    isTesting.value = true;
    validationResult.value = null;

    try {
        const response = await api.post(
            API_ENDPOINTS.ORGANIZER.EVENT.VALIDATE_PAYOUT(route.params.id),
            { access_token: formData.value.access_token },
        );

        validationResult.value = response.data;
    } catch (err) {
        validationResult.value = {
            valid: false,
            error: err.response?.data?.error || "Erro ao validar credenciais",
        };
    } finally {
        isTesting.value = false;
    }
};

const saveConfiguration = async () => {
    if (!validationResult.value?.valid) return;

    isSaving.value = true;

    try {
        await api.put(API_ENDPOINTS.ORGANIZER.EVENT.PAYOUT(route.params.id), {
            details: formData.value,
        });

        toast.success("Configuração salva com sucesso! Evento ativado.");

        setTimeout(() => {
            router.push(`/organizer/events/${route.params.id}`);
        }, 500);
    } catch (err) {
        toast.error(
            err.response?.data?.message ||
                err.message ||
                "Erro ao salvar configuração",
        );
    } finally {
        isSaving.value = false;
    }
};

onMounted(() => {
    loadData();
});
</script>

<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Recebimento</h1>
                <p class="text-text-muted mt-1">
                    Conecte sua conta do Mercado Pago para receber os pagamentos
                    diretamente (split de pagamento).
                </p>
            </div>
        </div>

        <LoadingState v-if="store.isLoading" message="Carregando conexão..." />

        <ErrorState
            v-else-if="store.error && !store.account"
            :message="store.error"
            @retry="load"
        />

        <template v-else>
            <!-- Card de status -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <div class="flex items-start gap-4">
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                        :class="
                            store.isConnected
                                ? 'bg-primary/10'
                                : 'bg-surface-elevated'
                        "
                    >
                        <span
                            class="material-symbols-outlined text-[24px]"
                            :class="
                                store.isConnected
                                    ? 'text-primary'
                                    : 'text-text-muted'
                            "
                        >
                            account_balance_wallet
                        </span>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <h3 class="text-white font-semibold">
                                Mercado Pago
                            </h3>
                            <span
                                class="text-xs font-semibold px-2 py-0.5 rounded-full"
                                :class="
                                    store.isConnected
                                        ? 'bg-primary/10 text-primary'
                                        : 'bg-surface-elevated text-text-muted'
                                "
                            >
                                {{ store.isConnected ? "Conectado" : "Não conectado" }}
                            </span>
                        </div>

                        <p
                            v-if="store.isConnected"
                            class="text-text-muted text-sm mt-1"
                        >
                            Conta
                            <span class="font-mono text-text-secondary">{{
                                store.account.provider_account_id || "••••"
                            }}</span>
                            · conectada em
                            {{ formatDate(store.account.connected_at) }}
                        </p>
                        <p v-else class="text-text-muted text-sm mt-1">
                            Enquanto não conectada, os eventos deste organizador
                            continuam no modo centralizado (repasse feito pela
                            plataforma).
                        </p>
                    </div>

                    <!-- Ações (somente organizador Admin) -->
                    <div v-if="canManage" class="shrink-0">
                        <button
                            v-if="!store.isConnected"
                            @click="handleConnect"
                            :disabled="store.isConnecting"
                            class="flex items-center gap-2 px-4 py-2 bg-primary text-background-dark font-bold rounded-lg hover:brightness-110 transition-all disabled:opacity-60"
                        >
                            <span class="material-symbols-outlined text-[18px]">
                                link
                            </span>
                            {{ store.isConnecting ? "Redirecionando..." : "Conectar" }}
                        </button>
                        <button
                            v-else
                            @click="showDisconnect = true"
                            class="flex items-center gap-2 px-4 py-2 border border-surface-elevated text-text-secondary rounded-lg hover:border-red-400 hover:text-red-400 transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]">
                                link_off
                            </span>
                            Desconectar
                        </button>
                    </div>
                </div>

                <p
                    v-if="!canManage"
                    class="text-text-muted text-xs mt-4 border-t border-surface-elevated pt-4"
                >
                    Apenas um administrador do organizador pode conectar ou
                    desconectar a conta do Mercado Pago.
                </p>
            </div>
        </template>

        <!-- Modal de confirmação de desconexão -->
        <Modal
            v-model="showDisconnect"
            title="Desconectar Mercado Pago"
            subtitle="Novos pagamentos deixarão de usar o split."
            size="md"
        >
            <p>
                Tem certeza que deseja desconectar a conta do Mercado Pago? Os
                eventos em modo split precisarão ser reconfigurados após uma nova
                conexão.
            </p>
            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDisconnect = false"
                        class="px-4 py-2 border border-surface-elevated text-text-secondary rounded-lg hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="handleDisconnect"
                        class="px-4 py-2 bg-red-500/90 text-white font-semibold rounded-lg hover:bg-red-500 transition-colors"
                    >
                        Desconectar
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { usePaymentAccountStore } from "@/stores/paymentAccount";
import { useToast } from "@/composables/useToast";
import LoadingState from "@/components/ui/LoadingState.vue";
import ErrorState from "@/components/ui/ErrorState.vue";
import Modal from "@/components/ui/Modal.vue";

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const store = usePaymentAccountStore();
const toast = useToast();

const showDisconnect = ref(false);

const canManage = computed(
    () => auth.isSuperAdmin || auth.isCurrentOrganizerAdmin,
);

function formatDate(value) {
    if (!value) return "—";
    return new Date(value).toLocaleDateString("pt-BR");
}

async function load() {
    await store.fetchStatus();
}

async function handleConnect() {
    const result = await store.connect();
    if (result.success && result.url) {
        window.location.href = result.url;
    } else {
        toast.error(result.error || "Não foi possível iniciar a conexão.");
    }
}

async function handleDisconnect() {
    showDisconnect.value = false;
    const result = await store.disconnect();
    if (result.success) {
        toast.success("Conta do Mercado Pago desconectada.");
    } else {
        toast.error(result.error || "Erro ao desconectar.");
    }
}

onMounted(async () => {
    // Retorno do callback OAuth
    if (route.query.connected === "1") {
        toast.success("Mercado Pago conectado com sucesso!");
        router.replace({ query: {} });
    } else if (route.query.connected === "0") {
        toast.error("Não foi possível concluir a conexão com o Mercado Pago.");
        router.replace({ query: {} });
    }

    await load();
});
</script>

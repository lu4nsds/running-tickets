<template>
    <div
        class="min-h-screen text-slate-100 flex items-center justify-center relative overflow-hidden"
        style="background-color: #080b10"
    >
        <!-- Glows atmosféricos -->
        <div class="absolute inset-0 pointer-events-none">
            <div
                class="absolute top-0 left-1/4 w-96 h-96 rounded-full opacity-20"
                style="
                    background: radial-gradient(
                        circle,
                        #00e677 0%,
                        transparent 70%
                    );
                    filter: blur(80px);
                    transform: translate(-50%, -40%);
                "
            ></div>
            <div
                class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full opacity-10"
                style="
                    background: radial-gradient(
                        circle,
                        #00cc6a 0%,
                        transparent 70%
                    );
                    filter: blur(100px);
                    transform: translate(30%, 40%);
                "
            ></div>
        </div>

        <div class="relative z-10 w-full max-w-md px-4">
            <div
                class="bg-surface-dark rounded-2xl border border-border-dark p-8 shadow-lg"
            >
                <!-- Logo -->
                <div class="flex items-center justify-center gap-2 mb-8">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <span class="material-symbols-outlined text-4xl"
                            >mail</span
                        >
                    </div>
                    <span class="text-2xl font-black tracking-tight text-white">
                        RUNNING <span class="text-primary">TICKETS</span>
                    </span>
                </div>

                <!-- Título -->
                <h1 class="text-2xl font-bold text-white text-center mb-2">
                    Verifique seu Email
                </h1>
                <p class="text-slate-400 text-center mb-8">
                    Enviamos um email de verificação para
                    <span class="text-white font-semibold">{{
                        userEmail
                    }}</span>
                </p>

                <!-- Instruções -->
                <div
                    class="bg-blue-500/10 border border-blue-500/20 rounded-lg p-4 mb-6"
                >
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-blue-400"
                            >info</span
                        >
                        <div class="text-sm text-slate-300">
                            <p class="font-semibold text-blue-400 mb-2">
                                Para continuar:
                            </p>
                            <ol class="list-decimal list-inside space-y-1">
                                <li>Acesse sua caixa de entrada</li>
                                <li>Abra o email de verificação</li>
                                <li>Clique no link de verificação</li>
                            </ol>
                            <p class="mt-3 text-xs text-slate-400">
                                Não encontrou? Verifique a pasta de spam.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Mensagem de Sucesso ao Reenviar -->
                <div
                    v-if="resendSuccess"
                    class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg"
                >
                    <div class="flex items-center gap-2">
                        <span class="material-symbols-outlined text-green-500"
                            >check_circle</span
                        >
                        <p class="text-green-400 text-sm">
                            Email reenviado com sucesso!
                        </p>
                    </div>
                </div>

                <!-- Erro -->
                <div
                    v-if="error"
                    class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-lg"
                >
                    <p class="text-red-400 text-sm">{{ error }}</p>
                </div>

                <!-- Botão Reenviar -->
                <button
                    @click="handleResend"
                    :disabled="loading || resendCooldown > 0"
                    class="w-full rounded-lg border border-border-dark bg-background-dark px-4 py-3 font-medium text-white transition-colors hover:border-primary hover:bg-surface-dark disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="resendCooldown > 0">
                        Aguarde {{ resendCooldown }}s para reenviar
                    </span>
                    <span v-else-if="loading"> Reenviando... </span>
                    <span v-else> Reenviar Email de Verificação </span>
                </button>

                <!-- Link de Logout -->
                <div class="mt-6 text-center">
                    <button
                        @click="handleLogout"
                        class="text-slate-400 hover:text-primary text-sm"
                    >
                        Sair da conta
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { resendVerificationEmail } from "@/api/auth";

const router = useRouter();
const authStore = useAuthStore();

const loading = ref(false);
const error = ref(null);
const resendSuccess = ref(false);
const resendCooldown = ref(0);

const userEmail = computed(() => authStore.user?.email || "");

let cooldownInterval = null;

onMounted(async () => {
    // Se não estiver autenticado, redirecionar para login
    if (!authStore.isAuthenticated) {
        router.push("/entrar");
        return;
    }

    // Se já verificou o email, redirecionar para home
    if (authStore.user?.email_verified_at) {
        router.push("/");
    }
});

async function handleResend() {
    loading.value = true;
    error.value = null;
    resendSuccess.value = false;

    try {
        await resendVerificationEmail();
        resendSuccess.value = true;

        // Iniciar cooldown de 60 segundos
        resendCooldown.value = 60;
        cooldownInterval = setInterval(() => {
            resendCooldown.value--;
            if (resendCooldown.value <= 0) {
                clearInterval(cooldownInterval);
            }
        }, 1000);
    } catch (err) {
        error.value =
            err.response?.data?.message ||
            "Erro ao reenviar email. Tente novamente.";
    } finally {
        loading.value = false;
    }
}

async function handleLogout() {
    await authStore.logout();
    router.push("/entrar");
}
</script>

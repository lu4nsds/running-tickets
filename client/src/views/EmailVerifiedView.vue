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
                <!-- Loading -->
                <div v-if="loading" class="text-center">
                    <div class="flex items-center justify-center mb-6">
                        <div
                            class="animate-spin rounded-full h-16 w-16 border-b-2 border-primary"
                        ></div>
                    </div>
                    <h2 class="text-xl font-bold text-white mb-2">
                        Verificando email...
                    </h2>
                    <p class="text-slate-400">Por favor, aguarde</p>
                </div>

                <!-- Sucesso -->
                <div v-else-if="success" class="text-center">
                    <div class="flex items-center justify-center mb-6">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full bg-green-500/10"
                        >
                            <span
                                class="material-symbols-outlined text-5xl text-green-500"
                                >check_circle</span
                            >
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">
                        Email Verificado!
                    </h2>
                    <p class="text-slate-400 mb-6">
                        Sua conta foi verificada com sucesso.
                        {{
                            alreadyVerified
                                ? "Você já pode usar o sistema."
                                : "Redirecionando..."
                        }}
                    </p>

                    <div v-if="alreadyVerified" class="space-y-3">
                        <button
                            @click="goToHome"
                            class="w-full rounded-lg bg-primary px-4 py-3 font-bold text-background-dark transition-colors hover:bg-primary-dark"
                        >
                            Ir para Página Inicial
                        </button>
                        <router-link
                            to="/entrar"
                            class="block text-slate-400 hover:text-primary text-sm"
                        >
                            Fazer Login
                        </router-link>
                    </div>
                </div>

                <!-- Erro -->
                <div v-else-if="error" class="text-center">
                    <div class="flex items-center justify-center mb-6">
                        <div
                            class="flex h-16 w-16 items-center justify-center rounded-full bg-red-500/10"
                        >
                            <span
                                class="material-symbols-outlined text-5xl text-red-500"
                                >error</span
                            >
                        </div>
                    </div>
                    <h2 class="text-2xl font-bold text-white mb-2">
                        Erro na Verificação
                    </h2>
                    <p class="text-red-400 mb-6">{{ error }}</p>

                    <div class="space-y-3">
                        <router-link
                            to="/verificar-email"
                            class="block w-full rounded-lg bg-primary px-4 py-3 font-bold text-background-dark text-center transition-colors hover:bg-primary-dark"
                        >
                            Solicitar Novo Email
                        </router-link>
                        <router-link
                            to="/entrar"
                            class="block text-slate-400 hover:text-primary text-sm"
                        >
                            Voltar para Login
                        </router-link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import { verifyEmail } from "@/api/auth";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const loading = ref(true);
const success = ref(false);
const error = ref(null);
const alreadyVerified = ref(false);

onMounted(async () => {
    const id = route.params.id;
    const hash = route.params.hash;
    const expires = route.query.expires;
    const signature = route.query.signature;

    if (!id || !hash || !expires || !signature) {
        error.value = "Link de verificação inválido.";
        loading.value = false;
        return;
    }

    try {
        const response = await verifyEmail(id, hash, expires, signature);

        success.value = true;
        alreadyVerified.value = response.data.already_verified || false;

        // Se não for já verificado, atualizar o store e redirecionar
        if (!alreadyVerified.value) {
            // Atualizar dados do usuário no store se estiver logado
            if (authStore.isAuthenticated) {
                await authStore.fetchUser();
            }

            // Redirecionar após 2 segundos
            setTimeout(() => {
                if (authStore.isAuthenticated) {
                    router.push("/");
                } else {
                    router.push("/entrar");
                }
            }, 2000);
        }
    } catch (err) {
        error.value =
            err.response?.data?.message ||
            err.response?.data?.errors?.email?.[0] ||
            "Link de verificação inválido ou expirado.";
    } finally {
        loading.value = false;
    }
});

function goToHome() {
    if (authStore.isAuthenticated) {
        router.push("/");
    } else {
        router.push("/entrar");
    }
}
</script>

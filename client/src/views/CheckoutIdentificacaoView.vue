<template>
    <div class="min-h-screen bg-background-dark text-slate-100 flex flex-col">
        <Navbar />

        <div class="flex-1 flex flex-col items-center justify-center px-4 py-8 sm:py-12">
            <!-- Logo -->
            <div class="mb-6 sm:mb-8">
                <div
                    class="w-12 h-12 sm:w-14 sm:h-14 rounded-full bg-primary flex items-center justify-center"
                >
                    <svg
                        class="w-7 h-7 sm:w-8 sm:h-8 text-background-dark"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                    >
                        <path
                            d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"
                        />
                    </svg>
                </div>
            </div>

            <!-- Stepper -->
            <div class="flex items-center gap-1.5 sm:gap-2 mb-8 sm:mb-12">
                <!-- Step 1: Inscrição -->
                <div class="flex items-center gap-1.5">
                    <div
                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-surface-dark border border-border-dark text-slate-500 text-xs font-bold flex items-center justify-center flex-shrink-0"
                    >
                        1
                    </div>
                    <span class="hidden sm:block text-sm text-slate-500">Inscrição</span>
                </div>

                <!-- Connector -->
                <div class="w-8 sm:w-12 h-px bg-primary/40"></div>

                <!-- Step 2: Identificação (ativo) -->
                <div class="flex items-center gap-1.5">
                    <div
                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-primary text-background-dark text-xs font-bold flex items-center justify-center flex-shrink-0"
                    >
                        2
                    </div>
                    <span class="text-xs sm:text-sm font-bold text-white">Identificação</span>
                </div>

                <!-- Connector -->
                <div class="w-8 sm:w-12 h-px bg-border-dark"></div>

                <!-- Step 3: Checkout -->
                <div class="flex items-center gap-1.5">
                    <div
                        class="w-6 h-6 sm:w-7 sm:h-7 rounded-full bg-surface-dark border border-border-dark text-slate-500 text-xs font-bold flex items-center justify-center flex-shrink-0"
                    >
                        3
                    </div>
                    <span class="hidden sm:block text-sm text-slate-500">Checkout</span>
                </div>
            </div>

            <!-- Cards -->
            <div class="w-full max-w-3xl flex flex-col sm:grid sm:grid-cols-2 gap-4 sm:gap-5">
                <!-- Card: Já sou corredor -->
                <div
                    class="bg-surface-dark border-2 border-primary rounded-2xl p-5 sm:p-7 flex flex-col"
                >
                    <!-- Ícone -->
                    <div
                        class="w-10 h-10 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center mb-4 sm:mb-5"
                    >
                        <svg
                            class="w-5 h-5 text-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>

                    <h2 class="text-lg sm:text-xl font-bold text-white mb-1">
                        Já sou corredor
                    </h2>
                    <p class="text-slate-400 text-sm mb-5 sm:mb-6">
                        Acesse sua conta para uma experiência personalizada.
                    </p>

                    <!-- Benefícios -->
                    <ul class="space-y-2 sm:space-y-2.5 mb-6 sm:mb-8">
                        <li
                            v-for="benefit in loggedBenefits"
                            :key="benefit"
                            class="flex items-center gap-2.5 text-sm text-slate-300"
                        >
                            <span
                                class="w-5 h-5 rounded-full bg-primary/20 border border-primary flex items-center justify-center flex-shrink-0"
                            >
                                <svg
                                    class="w-3 h-3 text-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </span>
                            {{ benefit }}
                        </li>
                    </ul>

                    <div class="mt-auto space-y-3">
                        <!-- Botão primário: Login -->
                        <button
                            @click="goToLogin"
                            class="w-full py-3.5 bg-primary text-background-dark font-bold rounded-xl hover:bg-primary/90 active:scale-95 transition-all flex items-center justify-center gap-2 text-sm sm:text-base"
                        >
                            Entrar com minha conta
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"
                                />
                            </svg>
                        </button>

                        <!-- Botão secundário: Criar conta -->
                        <button
                            @click="goToRegister"
                            class="w-full py-3 border border-primary/40 text-primary text-sm font-medium rounded-xl hover:bg-primary/10 active:scale-95 transition-all"
                        >
                            Criar conta
                        </button>
                    </div>
                </div>

                <!-- Card: Continuar como convidado -->
                <div
                    class="bg-surface-dark border border-border-dark rounded-2xl p-5 sm:p-7 flex flex-col"
                >
                    <!-- Ícone -->
                    <div
                        class="w-10 h-10 rounded-full bg-slate-800 border border-border-dark flex items-center justify-center mb-4 sm:mb-5"
                    >
                        <svg
                            class="w-5 h-5 text-slate-400"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                            />
                        </svg>
                    </div>

                    <h2 class="text-lg sm:text-xl font-bold text-white mb-1">
                        Continuar como convidado
                    </h2>
                    <p class="text-slate-400 text-sm mb-5 sm:mb-6">
                        Prossiga sem a necessidade de criar uma conta agora.
                    </p>

                    <!-- Características -->
                    <ul class="space-y-2 sm:space-y-2.5 mb-6 sm:mb-8">
                        <li
                            v-for="feature in guestFeatures"
                            :key="feature"
                            class="flex items-center gap-2.5 text-sm text-slate-400"
                        >
                            <span
                                class="w-5 h-5 rounded-full bg-slate-800 border border-border-dark flex items-center justify-center flex-shrink-0"
                            >
                                <svg
                                    class="w-3 h-3 text-slate-500"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="3"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </span>
                            {{ feature }}
                        </li>
                    </ul>

                    <div class="mt-auto">
                        <button
                            @click="continueAsGuest"
                            class="w-full py-3.5 bg-transparent border border-slate-600 text-white font-bold rounded-xl hover:border-slate-400 hover:bg-white/5 active:scale-95 transition-all flex items-center justify-center gap-2 text-sm sm:text-base"
                        >
                            Seguir sem login
                            <svg
                                class="w-4 h-4"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 8l4 4m0 0l-4 4m4-4H3"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import Navbar from "../components/Navbar.vue";

const router = useRouter();
const authStore = useAuthStore();

const loggedBenefits = [
    "Acesso rápido aos tickets",
    "Histórico de eventos",
    "Dados pré-preenchidos",
];

const guestFeatures = ["Compra rápida sem senha", "Identificação manual"];

onMounted(() => {
    // Sempre limpa a flag de guest ao entrar nesta tela (novo fluxo de compra)
    sessionStorage.removeItem("checkoutGuest");

    // Se já está autenticado, não precisa desta tela
    if (authStore.isAuthenticated) {
        router.replace({ name: "checkout" });
        return;
    }
    // Se não há dados de checkout, redirecionar para home
    const checkoutData = localStorage.getItem("checkout_data");
    if (!checkoutData) {
        router.replace({ name: "home" });
    }
});

function goToLogin() {
    router.push({ name: "login", query: { redirect: "/checkout" } });
}

function goToRegister() {
    router.push({ name: "register", query: { redirect: "/checkout" } });
}

function continueAsGuest() {
    sessionStorage.setItem("checkoutGuest", "true");
    router.push({ name: "checkout" });
}
</script>

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

            <!-- Card único -->
            <div class="w-full max-w-sm bg-surface-dark border-2 border-primary rounded-2xl p-6 sm:p-8 flex flex-col items-center text-center">
                <!-- Ícone cadeado -->
                <div
                    class="w-14 h-14 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center mb-5"
                >
                    <span class="material-symbols-outlined text-primary text-3xl">lock</span>
                </div>

                <h2 class="text-xl sm:text-2xl font-bold text-white mb-2">
                    Acesse sua conta para continuar
                </h2>
                <p class="text-slate-400 text-sm mb-6">
                    Garanta sua participação na prova com segurança e agilidade.
                </p>

                <!-- Box de benefícios -->
                <div class="w-full bg-surface-darker rounded-xl p-4 mb-6 text-left">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-3">
                        Vantagens de estar logado:
                    </p>
                    <ul class="space-y-2.5">
                        <li
                            v-for="benefit in benefits"
                            :key="benefit.text"
                            class="flex items-center gap-3 text-sm text-slate-300"
                        >
                            <span class="material-symbols-outlined text-primary text-[20px] flex-shrink-0">
                                {{ benefit.icon }}
                            </span>
                            {{ benefit.text }}
                        </li>
                    </ul>
                </div>

                <!-- Botão primário -->
                <button
                    @click="goToLogin"
                    class="w-full py-3.5 bg-primary text-background-dark font-bold rounded-xl hover:bg-primary/90 active:scale-95 transition-all flex items-center justify-center gap-2 text-sm sm:text-base mb-4"
                >
                    Entrar com minha conta
                    <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                </button>

                <!-- Link de cadastro -->
                <p class="text-sm text-slate-400">
                    Ainda não tenho conta?
                    <button
                        @click="goToRegister"
                        class="text-primary font-medium hover:underline ml-1"
                    >
                        Cadastre-se
                    </button>
                </p>
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

const benefits = [
    { icon: "local_activity", text: "Acesso rápido aos tickets" },
    { icon: "history",        text: "Histórico de eventos" },
    { icon: "edit_note",      text: "Dados pré-preenchidos" },
];

onMounted(() => {
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
</script>

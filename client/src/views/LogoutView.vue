<template>
    <div
        class="min-h-screen bg-background-dark flex flex-col items-center justify-center px-4"
    >
        <!-- Ícone animado -->
        <div class="relative mb-10">
            <!-- Anel externo pulsante -->
            <div
                class="absolute inset-0 rounded-full border-2 border-primary/40 animate-ping"
            ></div>
            <!-- Anel intermediário -->
            <div
                class="absolute inset-2 rounded-full border border-primary/20 animate-pulse"
            ></div>
            <!-- Círculo principal com raio -->
            <div
                class="relative w-24 h-24 rounded-full bg-surface-dark border-2 border-primary flex items-center justify-center shadow-[0_0_32px_rgba(16,185,129,0.35)]"
            >
                <svg
                    class="w-10 h-10 text-primary"
                    viewBox="0 0 24 24"
                    fill="currentColor"
                >
                    <path d="M7 2v11h3v9l7-12h-4l4-8z" />
                </svg>
            </div>
        </div>

        <!-- Textos -->
        <h1 class="text-2xl sm:text-3xl font-bold text-white mb-3 text-center">
            Saindo da sua conta...
        </h1>
        <p class="text-primary text-sm sm:text-base text-center max-w-xs leading-relaxed">
            Estamos encerrando sua sessão de forma segura.<br />
            Até a próxima corrida!
        </p>
    </div>
</template>

<script setup>
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const authStore = useAuthStore();

onMounted(async () => {
    sessionStorage.removeItem("checkoutGuest");
    await Promise.all([
        authStore.logout(),
        new Promise((resolve) => setTimeout(resolve, 1500)),
    ]);
    router.replace({ name: "home" });
});
</script>

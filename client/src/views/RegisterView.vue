<template>
    <div
        class="min-h-screen text-slate-100 flex items-center justify-center py-12 relative overflow-hidden"
        style="background-color: #080B10;"
    >
        <!-- Glows atmosféricos -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full opacity-20"
                style="background: radial-gradient(circle, #00e677 0%, transparent 70%); filter: blur(80px); transform: translate(-50%, -40%);"
            ></div>
            <div class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full opacity-10"
                style="background: radial-gradient(circle, #00cc6a 0%, transparent 70%); filter: blur(100px); transform: translate(30%, 40%);"
            ></div>
            <div class="absolute top-1/2 right-0 w-64 h-64 rounded-full opacity-8"
                style="background: radial-gradient(circle, #00e677 0%, transparent 70%); filter: blur(90px); transform: translate(40%, -50%);"
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
                            >bolt</span
                        >
                    </div>
                    <span class="text-2xl font-black tracking-tight text-white">
                        RUNNING <span class="text-primary">TICKETS</span>
                    </span>
                </div>

                <!-- Título -->
                <h1 class="text-2xl font-bold text-white text-center mb-2">
                    Criar Conta
                </h1>
                <p class="text-slate-400 text-center mb-8">
                    Junte-se aos corredores
                </p>

                <!-- Formulário -->
                <form @submit.prevent="handleRegister" class="space-y-4">
                    <div>
                        <label
                            for="name"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Nome Completo
                        </label>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            required
                            class="block w-full rounded-lg border border-border-dark bg-background-dark px-4 py-3 text-white placeholder-slate-500 focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="Seu nome"
                        />
                    </div>

                    <div>
                        <label
                            for="email"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            E-mail
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            required
                            class="block w-full rounded-lg border border-border-dark bg-background-dark px-4 py-3 text-white placeholder-slate-500 focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="seu@email.com"
                        />
                    </div>

                    <div>
                        <label
                            for="password"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Senha
                        </label>
                        <input
                            id="password"
                            v-model="form.password"
                            type="password"
                            required
                            minlength="8"
                            class="block w-full rounded-lg border border-border-dark bg-background-dark px-4 py-3 text-white placeholder-slate-500 focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="••••••••"
                        />
                    </div>

                    <div>
                        <label
                            for="password_confirmation"
                            class="block text-sm font-medium text-slate-300 mb-2"
                        >
                            Confirmar Senha
                        </label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            type="password"
                            required
                            class="block w-full rounded-lg border border-border-dark bg-background-dark px-4 py-3 text-white placeholder-slate-500 focus:border-primary focus:ring-1 focus:ring-primary"
                            placeholder="••••••••"
                        />
                    </div>

                    <div v-if="error" class="text-red-400 text-sm text-center">
                        {{ error }}
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full rounded-lg bg-primary px-4 py-3 font-bold text-background-dark transition-colors hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ loading ? "Cadastrando..." : "Cadastrar" }}
                    </button>
                </form>

                <!-- Divisor -->
                <div class="flex items-center gap-3 my-6">
                    <div class="flex-1 border-t border-border-dark"></div>
                    <span class="text-slate-500 text-sm">ou</span>
                    <div class="flex-1 border-t border-border-dark"></div>
                </div>

                <!-- Google OAuth -->
                <button
                    type="button"
                    @click="loginWithGoogle"
                    class="w-full flex items-center justify-center gap-3 rounded-lg border border-border-dark bg-background-dark px-4 py-3 text-white font-medium hover:border-slate-500 hover:bg-surface-dark transition-colors"
                >
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Cadastrar com Google
                </button>

                <!-- Links -->
                <div class="mt-6 text-center">
                    <p class="text-slate-400 text-sm">
                        Já tem uma conta?
                        <router-link
                            to="/entrar"
                            class="text-primary hover:text-primary-dark font-semibold"
                        >
                            Entrar
                        </router-link>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = ref({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
});

const loading = ref(false);
const error = ref(null);

async function handleRegister() {
    if (form.value.password !== form.value.password_confirmation) {
        error.value = "As senhas não coincidem";
        return;
    }

    loading.value = true;
    error.value = null;

    try {
        await authStore.register(form.value);
        router.push(route.query.redirect || "/");
    } catch (err) {
        error.value = err.response?.data?.message || "Erro ao cadastrar";
    } finally {
        loading.value = false;
    }
}

function loginWithGoogle() {
    const apiUrl = import.meta.env.VITE_API_URL.replace("/api", "");
    window.location.href = `${apiUrl}/api/auth/google`;
}
</script>

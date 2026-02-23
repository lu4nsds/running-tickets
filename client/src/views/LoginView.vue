<template>
    <div
        class="min-h-screen bg-background-dark text-slate-100 flex items-center justify-center"
    >
        <div class="w-full max-w-md px-4">
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
                    Entrar
                </h1>
                <p class="text-slate-400 text-center mb-8">
                    Acesse sua conta para continuar
                </p>

                <!-- Formulário -->
                <form @submit.prevent="handleLogin" class="space-y-4">
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
                        {{ loading ? "Entrando..." : "Entrar" }}
                    </button>
                </form>

                <!-- Links -->
                <div class="mt-6 text-center">
                    <p class="text-slate-400 text-sm">
                        Não tem uma conta?
                        <router-link
                            to="/cadastro"
                            class="text-primary hover:text-primary-dark font-semibold"
                        >
                            Cadastre-se
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
    email: "",
    password: "",
});

const loading = ref(false);
const error = ref(null);

async function handleLogin() {
    loading.value = true;
    error.value = null;

    try {
        await authStore.login(form.value.email, form.value.password);

        // Redireciona para a página solicitada ou home
        const redirect = route.query.redirect || "/";
        router.push(redirect);
    } catch (err) {
        error.value = err.response?.data?.message || "Erro ao fazer login";
    } finally {
        loading.value = false;
    }
}
</script>

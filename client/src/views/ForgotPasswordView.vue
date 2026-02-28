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
                            >lock</span
                        >
                    </div>
                    <span class="text-2xl font-black tracking-tight text-white">
                        RUNNING <span class="text-primary">TICKETS</span>
                    </span>
                </div>

                <!-- Título -->
                <h1 class="text-2xl font-bold text-white text-center mb-2">
                    Esqueceu sua senha?
                </h1>
                <p class="text-slate-400 text-center mb-8">
                    Não se preocupe, enviaremos um link para redefinir sua senha
                </p>

                <!-- Mensagem de Sucesso -->
                <div
                    v-if="success"
                    class="mb-6 p-4 bg-green-500/10 border border-green-500/20 rounded-lg"
                >
                    <div class="flex items-start gap-3">
                        <span class="material-symbols-outlined text-green-500"
                            >check_circle</span
                        >
                        <div>
                            <p class="text-green-400 font-semibold">
                                Email enviado!
                            </p>
                            <p class="text-slate-300 text-sm mt-1">
                                Verifique sua caixa de entrada e siga as
                                instruções para redefinir sua senha.
                            </p>
                            <p class="text-slate-400 text-xs mt-2">
                                Não recebeu? Verifique a pasta de spam.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Formulário -->
                <form
                    v-if="!success"
                    @submit.prevent="handleSubmit"
                    class="space-y-4"
                >
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
                            :disabled="loading"
                            class="block w-full rounded-lg border border-border-dark bg-background-dark px-4 py-3 text-white placeholder-slate-500 focus:border-primary focus:ring-1 focus:ring-primary disabled:opacity-50 disabled:cursor-not-allowed"
                            placeholder="seu@email.com"
                        />
                    </div>

                    <div
                        v-if="error"
                        class="text-red-400 text-sm text-center bg-red-500/10 border border-red-500/20 rounded-lg p-3"
                    >
                        {{ error }}
                    </div>

                    <button
                        type="submit"
                        :disabled="loading"
                        class="w-full rounded-lg bg-primary px-4 py-3 font-bold text-background-dark transition-colors hover:bg-primary-dark disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{
                            loading
                                ? "Enviando..."
                                : "Enviar Link de Redefinição"
                        }}
                    </button>
                </form>

                <!-- Link de Voltar -->
                <div class="mt-6 text-center">
                    <router-link
                        to="/entrar"
                        class="text-slate-400 hover:text-primary text-sm inline-flex items-center gap-1"
                    >
                        <span class="material-symbols-outlined text-base"
                            >arrow_back</span
                        >
                        Voltar para o login
                    </router-link>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { forgotPassword } from "@/api/auth";

const form = ref({
    email: "",
});

const loading = ref(false);
const error = ref(null);
const success = ref(false);

async function handleSubmit() {
    loading.value = true;
    error.value = null;

    try {
        await forgotPassword(form.value.email);
        success.value = true;
    } catch (err) {
        error.value =
            err.response?.data?.message ||
            err.response?.data?.errors?.email?.[0] ||
            "Erro ao enviar email. Tente novamente.";
    } finally {
        loading.value = false;
    }
}
</script>

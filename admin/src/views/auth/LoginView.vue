<template>
    <div
        class="flex items-center justify-center min-h-screen relative overflow-hidden"
    >
        <!-- Background blur effects -->
        <div
            class="fixed top-0 right-0 w-[500px] h-[500px] bg-primary/5 rounded-full blur-[120px] -z-10 translate-x-1/2 -translate-y-1/2 pointer-events-none"
        ></div>
        <div
            class="fixed bottom-0 left-0 w-[400px] h-[400px] bg-primary/5 rounded-full blur-[100px] -z-10 -translate-x-1/2 translate-y-1/2 pointer-events-none"
        ></div>

        <div class="w-full max-w-[440px] px-6 py-12 relative">
            <!-- Logo and Title -->
            <div class="flex flex-col items-center mb-10">
                <div
                    class="w-16 h-16 bg-primary rounded-xl flex items-center justify-center mb-6 shadow-primary"
                >
                    <span
                        class="material-symbols-outlined text-background-dark text-4xl font-bold"
                    >
                        payments
                    </span>
                </div>
                <h1 class="text-3xl font-bold tracking-tight text-white mb-2">
                    Running Tickets
                </h1>
                <p class="text-gray-400 text-sm font-normal">
                    Backoffice Organizador & Portal Admin
                </p>
            </div>

            <!-- Login Card -->
            <div
                class="bg-card-bg border border-surface-elevated p-8 rounded-xl shadow-2xl"
            >
                <form @submit.prevent="handleLogin" class="space-y-6">
                    <!-- Email Field -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="email"
                            class="text-sm font-medium text-gray-300 ml-1"
                        >
                            Endereço de E-mail
                        </label>
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-gray-500 text-[20px] group-focus-within:text-primary transition-colors"
                                >
                                    mail
                                </span>
                            </div>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                placeholder="nome@empresa.com.br"
                                class="w-full pl-11 pr-4 py-3.5 bg-input-bg border border-input-border text-white rounded-lg focus:ring-1 focus:ring-primary focus:border-primary transition-all placeholder:text-gray-500 outline-none"
                                :disabled="isLoading"
                            />
                        </div>
                    </div>

                    <!-- Password Field -->
                    <div class="flex flex-col gap-2">
                        <div class="flex justify-between items-center ml-1">
                            <label
                                for="password"
                                class="text-sm font-medium text-gray-300"
                            >
                                Senha
                            </label>
                            <a
                                href="#"
                                class="text-xs font-medium text-primary hover:text-primary/80 transition-colors"
                                @click.prevent="handleForgotPassword"
                            >
                                Esqueceu a senha?
                            </a>
                        </div>
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-gray-500 text-[20px] group-focus-within:text-primary transition-colors"
                                >
                                    lock
                                </span>
                            </div>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                required
                                placeholder="••••••••"
                                class="w-full pl-11 pr-12 py-3.5 bg-input-bg border border-input-border text-white rounded-lg focus:ring-1 focus:ring-primary focus:border-primary transition-all placeholder:text-gray-500 outline-none"
                                :disabled="isLoading"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-gray-300"
                                @click="showPassword = !showPassword"
                            >
                                <span
                                    class="material-symbols-outlined text-[20px]"
                                >
                                    {{
                                        showPassword
                                            ? "visibility_off"
                                            : "visibility"
                                    }}
                                </span>
                            </button>
                        </div>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center gap-3 ml-1">
                        <input
                            id="remember"
                            v-model="form.remember"
                            type="checkbox"
                            class="w-4 h-4 rounded border-input-border bg-input-bg text-primary focus:ring-primary focus:ring-offset-background-dark focus:ring-offset-2"
                        />
                        <label
                            for="remember"
                            class="text-sm text-gray-400 select-none"
                        >
                            Lembrar este dispositivo
                        </label>
                    </div>

                    <!-- Error Message -->
                    <div
                        v-if="errorMessage"
                        class="bg-red-500/10 border border-red-500/20 rounded-lg p-3"
                    >
                        <p class="text-red-400 text-sm">{{ errorMessage }}</p>
                    </div>

                    <!-- Submit Button -->
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="w-full py-4 bg-primary text-background-dark font-bold text-base rounded-lg hover:brightness-110 active:scale-[0.98] transition-all shadow-primary-strong flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <template v-if="!isLoading">
                            <span>Entrar</span>
                            <span
                                class="material-symbols-outlined font-bold text-[20px]"
                            >
                                arrow_forward
                            </span>
                        </template>
                        <template v-else>
                            <LoadingSpinner size="sm" color="dark" />
                            <span>Entrando...</span>
                        </template>
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-10 text-center space-y-4">
                <p
                    class="text-gray-600 text-xs uppercase tracking-[0.2em] font-semibold"
                >
                    Infraestrutura Multi-Tenant
                </p>
                <div class="flex justify-center gap-6">
                    <a
                        href="#"
                        class="text-gray-500 hover:text-primary text-xs transition-colors"
                    >
                        Política de Privacidade
                    </a>
                    <a
                        href="#"
                        class="text-gray-500 hover:text-primary text-xs transition-colors"
                    >
                        Central de Suporte
                    </a>
                </div>
            </div>
        </div>

        <!-- Modal Esqueceu a Senha -->
        <Modal
            v-model="showForgotPasswordModal"
            title="Recuperar senha"
            subtitle="Enviaremos um link para o seu e-mail"
            @update:model-value="onModalClose"
        >
            <!-- Estado: sucesso -->
            <div v-if="forgotSuccess" class="space-y-4 text-center py-2">
                <span class="material-symbols-outlined text-primary text-5xl">mark_email_read</span>
                <p class="text-white font-semibold">Link enviado!</p>
                <p class="text-text-muted text-sm">
                    Enviamos as instruções para
                    <strong class="text-white">{{ forgotEmail }}</strong>.
                    Verifique sua caixa de entrada.
                </p>
            </div>

            <!-- Estado: formulário -->
            <div v-else class="space-y-4">
                <p class="text-text-muted text-sm">
                    Digite o e-mail da sua conta e enviaremos um link para redefinir sua senha.
                </p>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <span class="material-symbols-outlined text-gray-500 text-[20px] group-focus-within:text-primary transition-colors">
                            mail
                        </span>
                    </div>
                    <input
                        v-model="forgotEmail"
                        type="email"
                        placeholder="seu@email.com"
                        class="w-full pl-11 pr-4 py-3 bg-input-bg border border-input-border text-white rounded-lg focus:ring-1 focus:ring-primary focus:border-primary transition-all placeholder:text-gray-500 outline-none"
                        :class="{ 'border-red-500': forgotError }"
                        :disabled="forgotLoading"
                        @keyup.enter="handleForgotSubmit"
                    />
                </div>
                <p v-if="forgotError" class="text-xs text-red-400">{{ forgotError }}</p>
            </div>

            <template #footer>
                <button
                    v-if="forgotSuccess"
                    @click="showForgotPasswordModal = false"
                    class="w-full py-3 bg-primary text-background-dark font-bold rounded-lg hover:brightness-110 transition-all"
                >
                    Fechar
                </button>
                <button
                    v-else
                    @click="handleForgotSubmit"
                    :disabled="forgotLoading || !forgotEmail.trim()"
                    class="w-full py-3 bg-primary text-background-dark font-bold rounded-lg hover:brightness-110 transition-all flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span v-if="forgotLoading" class="material-symbols-outlined animate-spin text-[18px]">progress_activity</span>
                    {{ forgotLoading ? 'Enviando...' : 'Enviar link de recuperação' }}
                </button>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import Modal from "@/components/ui/Modal.vue";
import LoadingSpinner from "@/components/ui/LoadingSpinner.vue";
import api from "@/api/axios";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";

const router = useRouter();
const authStore = useAuthStore();

const form = ref({
    email: "",
    password: "",
    remember: false,
});

const showPassword = ref(false);
const errorMessage = ref("");
const isLoading = ref(false);
const showForgotPasswordModal = ref(false);

// Forgot password state
const forgotEmail = ref("");
const forgotLoading = ref(false);
const forgotError = ref("");
const forgotSuccess = ref(false);

const handleLogin = async () => {
    isLoading.value = true;
    errorMessage.value = "";

    try {
        const result = await authStore.login(
            form.value.email,
            form.value.password,
            form.value.remember,
        );

        if (result.success) {
            if (authStore.isSuperAdmin) {
                router.push("/admin/dashboard");
            } else if (authStore.hasOrganizers) {
                router.push("/organizer/dashboard");
            } else {
                errorMessage.value =
                    "Usuário sem permissões de acesso ao sistema.";
            }
        } else {
            errorMessage.value = result.error || "E-mail ou senha incorretos.";
        }
    } catch (error) {
        errorMessage.value = "Erro ao fazer login. Tente novamente.";
        console.error("Login error:", error);
    } finally {
        isLoading.value = false;
    }
};

const handleForgotPassword = () => {
    showForgotPasswordModal.value = true;
};

const handleForgotSubmit = async () => {
    if (!forgotEmail.value.trim()) return;

    forgotLoading.value = true;
    forgotError.value = "";

    try {
        await api.post(API_ENDPOINTS.AUTH.FORGOT_PASSWORD, {
            email: forgotEmail.value,
            source: "admin",
        });
        forgotSuccess.value = true;
    } catch (err) {
        forgotError.value =
            err.response?.data?.errors?.email?.[0] ||
            err.response?.data?.message ||
            "Erro ao enviar o link. Tente novamente.";
    } finally {
        forgotLoading.value = false;
    }
};

const onModalClose = (open) => {
    if (!open) {
        forgotEmail.value = "";
        forgotError.value = "";
        forgotSuccess.value = false;
    }
};
</script>

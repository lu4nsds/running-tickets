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
                        lock_reset
                    </span>
                </div>
                <h1 class="text-3xl font-bold tracking-tight text-white mb-2">
                    Redefinir Senha
                </h1>
                <p class="text-gray-400 text-sm font-normal text-center">
                    Crie uma nova senha para sua conta
                </p>
            </div>

            <!-- Invalid link -->
            <div
                v-if="!token || !email"
                class="bg-card-bg border border-red-500/30 p-8 rounded-xl text-center space-y-4"
            >
                <span class="material-symbols-outlined text-red-400 text-5xl">link_off</span>
                <p class="text-white font-semibold">Link inválido</p>
                <p class="text-gray-400 text-sm">
                    Este link é inválido ou está incompleto.
                    <router-link to="/login" class="text-primary hover:underline">Voltar ao login</router-link>
                </p>
            </div>

            <!-- Reset Card -->
            <div
                v-else
                class="bg-card-bg border border-surface-elevated p-8 rounded-xl shadow-2xl"
            >
                <!-- Success state -->
                <div v-if="success" class="text-center space-y-4">
                    <span class="material-symbols-outlined text-primary text-5xl">check_circle</span>
                    <p class="text-white font-semibold">Senha redefinida!</p>
                    <p class="text-gray-400 text-sm">Redirecionando para o login...</p>
                </div>

                <!-- Form -->
                <form v-else @submit.prevent="handleReset" class="space-y-6">
                    <!-- Nova Senha -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="password"
                            class="text-sm font-medium text-gray-300 ml-1"
                        >
                            Nova Senha
                        </label>
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
                                placeholder="••••••••"
                                class="w-full pl-11 pr-12 py-3.5 bg-input-bg border border-input-border text-white rounded-lg focus:ring-1 focus:ring-primary focus:border-primary transition-all placeholder:text-gray-500 outline-none"
                                :class="{ 'border-red-500 focus:ring-red-500': errors.password }"
                                :disabled="isLoading"
                            />
                            <button
                                type="button"
                                class="absolute inset-y-0 right-0 pr-4 flex items-center text-gray-500 hover:text-gray-300"
                                @click="showPassword = !showPassword"
                            >
                                <span class="material-symbols-outlined text-[20px]">
                                    {{ showPassword ? "visibility_off" : "visibility" }}
                                </span>
                            </button>
                        </div>
                        <p v-if="errors.password" class="text-xs text-red-400 ml-1">
                            {{ errors.password[0] }}
                        </p>
                    </div>

                    <!-- Confirmar Senha -->
                    <div class="flex flex-col gap-2">
                        <label
                            for="password_confirmation"
                            class="text-sm font-medium text-gray-300 ml-1"
                        >
                            Confirmar Nova Senha
                        </label>
                        <div class="relative group">
                            <div
                                class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-gray-500 text-[20px] group-focus-within:text-primary transition-colors"
                                >
                                    lock_clock
                                </span>
                            </div>
                            <input
                                id="password_confirmation"
                                v-model="form.passwordConfirmation"
                                :type="showPassword ? 'text' : 'password'"
                                placeholder="••••••••"
                                class="w-full pl-11 pr-4 py-3.5 bg-input-bg border border-input-border text-white rounded-lg focus:ring-1 focus:ring-primary focus:border-primary transition-all placeholder:text-gray-500 outline-none"
                                :class="{ 'border-red-500 focus:ring-red-500': errors.password_confirmation }"
                                :disabled="isLoading"
                            />
                        </div>
                        <p v-if="errors.password_confirmation" class="text-xs text-red-400 ml-1">
                            {{ errors.password_confirmation[0] }}
                        </p>
                    </div>

                    <p class="text-xs text-gray-500 ml-1">
                        A senha deve ter pelo menos 8 caracteres, letras maiúsculas, minúsculas e números.
                    </p>

                    <!-- Error global -->
                    <div
                        v-if="errorMessage"
                        class="bg-red-500/10 border border-red-500/20 rounded-lg p-3"
                    >
                        <p class="text-red-400 text-sm">{{ errorMessage }}</p>
                    </div>

                    <!-- Submit -->
                    <button
                        type="submit"
                        :disabled="isLoading"
                        class="w-full py-4 bg-primary text-background-dark font-bold text-base rounded-lg hover:brightness-110 active:scale-[0.98] transition-all shadow-primary-strong flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <template v-if="!isLoading">
                            <span>Redefinir Senha</span>
                            <span class="material-symbols-outlined font-bold text-[20px]">check</span>
                        </template>
                        <template v-else>
                            <span class="material-symbols-outlined animate-spin text-[20px]">progress_activity</span>
                            <span>Redefinindo...</span>
                        </template>
                    </button>

                    <p class="text-center text-sm text-gray-500">
                        <router-link to="/login" class="text-primary hover:underline">
                            Voltar ao login
                        </router-link>
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '@/api/axios'
import { API_ENDPOINTS } from '@/constants/apiEndpoints'

const route = useRoute()
const router = useRouter()

const token = route.query.token
const email = route.query.email

const form = ref({
    password: '',
    passwordConfirmation: '',
})

const showPassword = ref(false)
const isLoading = ref(false)
const success = ref(false)
const errorMessage = ref('')
const errors = ref({})

const handleReset = async () => {
    isLoading.value = true
    errorMessage.value = ''
    errors.value = {}

    try {
        await api.post(API_ENDPOINTS.AUTH.RESET_PASSWORD, {
            token,
            email,
            password:              form.value.password,
            password_confirmation: form.value.passwordConfirmation,
        })

        success.value = true
        setTimeout(() => router.push('/login'), 2000)
    } catch (err) {
        const status = err.response?.status
        if (status === 422 || status === 410) {
            const data = err.response.data
            if (data.errors) {
                errors.value = data.errors
            } else {
                errorMessage.value = data.message || 'Token inválido ou expirado.'
            }
        } else {
            errorMessage.value = 'Erro ao redefinir senha. Tente novamente.'
        }
    } finally {
        isLoading.value = false
    }
}
</script>

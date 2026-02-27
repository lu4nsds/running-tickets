<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar />

        <main class="flex items-center justify-center min-h-[80vh] px-4">
            <div class="max-w-2xl w-full text-center">
                <!-- Success Icon -->
                <div class="mb-8 flex justify-center">
                    <div
                        class="relative size-24 rounded-full bg-primary/10 flex items-center justify-center border-2 border-primary"
                    >
                        <div
                            class="absolute inset-0 rounded-full bg-primary/20 animate-ping"
                        ></div>
                        <svg
                            class="size-12 text-primary relative z-10"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Success Message -->
                <h1
                    class="text-4xl md:text-5xl font-black text-white mb-4 uppercase tracking-tight"
                >
                    Pagamento Aprovado!
                </h1>
                <p class="text-xl text-slate-300 mb-2">
                    Sua inscrição foi confirmada com sucesso
                </p>
                <p class="text-sm text-slate-500 mb-12">
                    Enviamos um e-mail de confirmação com seus ingressos
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <router-link
                        v-if="isAuthenticated"
                        :to="{ name: 'my-tickets' }"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/20"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Ver Meus Ingressos
                    </router-link>
                    <router-link
                        :to="{ name: 'events' }"
                        :class="[
                            'inline-flex items-center justify-center gap-2 px-8 py-4 font-bold rounded-lg transition-all',
                            isAuthenticated
                                ? 'border border-border-dark bg-surface-dark text-white hover:border-primary hover:text-primary transition-colors'
                                : 'bg-primary text-background-dark hover:bg-primary/90 shadow-lg shadow-primary/20',
                        ]"
                    >
                        Buscar Mais Eventos
                    </router-link>
                </div>

                <!-- Banner de criação de conta (somente para guests) -->
                <div
                    v-if="!isAuthenticated"
                    class="mt-8 bg-surface-dark border border-primary/30 rounded-xl p-6 text-left"
                >
                    <h3 class="text-lg font-bold text-white mb-2">
                        Quer acessar seus ingressos a qualquer momento?
                    </h3>
                    <p class="text-slate-400 text-sm mb-4">
                        Crie uma conta com o e-mail usado no pedido e seus
                        ingressos ficarão salvos automaticamente.
                    </p>
                    <router-link
                        :to="{ name: 'register' }"
                        class="inline-flex items-center gap-2 px-6 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        Criar Conta Gratuita
                    </router-link>
                </div>

                <!-- Info Card -->
                <div
                    class="mt-12 bg-surface-dark border border-border-dark rounded-xl p-6 text-left"
                >
                    <h3
                        class="text-lg font-bold text-white mb-4 flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5 text-primary"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Próximos Passos
                    </h3>
                    <ul class="space-y-3 text-slate-300 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="text-primary mt-1">✓</span>
                            <span>
                                Você receberá um e-mail com os detalhes da sua
                                inscrição e QR Code dos ingressos
                            </span>
                        </li>
                        <li
                            v-if="isAuthenticated"
                            class="flex items-start gap-3"
                        >
                            <span class="text-primary mt-1">✓</span>
                            <span>
                                Seus ingressos foram salvos em "Meus Ingressos"
                                e podem ser acessados a qualquer momento
                            </span>
                        </li>
                        <li v-else class="flex items-start gap-3">
                            <span class="text-primary mt-1">✓</span>
                            <span>
                                Guarde o e-mail com seus ingressos, você
                                precisará do QR Code para entrar no evento
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-primary mt-1">✓</span>
                            <span>
                                Apresente o QR Code no dia do evento para
                                realizar o check-in
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { onMounted, computed } from "vue";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import { useAuthStore } from "../stores/auth";

const authStore = useAuthStore();
const isAuthenticated = computed(() => authStore.isAuthenticated);

onMounted(() => {
    // Limpar localStorage
    localStorage.removeItem("checkout_data");
    localStorage.removeItem("checkout_participants");
    localStorage.removeItem("payment_order");
});
</script>

<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar />

        <main class="flex items-center justify-center min-h-[80vh] px-4">
            <div class="max-w-2xl w-full text-center">
                <!-- Pending Icon -->
                <div class="mb-8 flex justify-center">
                    <div
                        class="relative size-24 rounded-full bg-yellow-500/10 flex items-center justify-center border-2 border-yellow-500"
                    >
                        <svg
                            class="size-12 text-yellow-500 animate-pulse"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Pending Message -->
                <h1
                    class="text-4xl md:text-5xl font-black text-white mb-4 uppercase tracking-tight"
                >
                    Pagamento Pendente
                </h1>
                <p class="text-xl text-slate-300 mb-2">
                    Aguardando confirmação do pagamento
                </p>
                <p class="text-sm text-slate-500 mb-12">
                    Você receberá um e-mail assim que o pagamento for aprovado
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <router-link
                        :to="{ name: 'my-orders' }"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-yellow-500 text-background-dark font-bold rounded-lg hover:bg-yellow-600 transition-all shadow-lg shadow-yellow-500/20"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" />
                            <path
                                fill-rule="evenodd"
                                d="M4 5a2 2 0 012-2 3 3 0 003 3h2a3 3 0 003-3 2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5zm3 4a1 1 0 000 2h.01a1 1 0 100-2H7zm3 0a1 1 0 000 2h3a1 1 0 100-2h-3zm-3 4a1 1 0 100 2h.01a1 1 0 100-2H7zm3 0a1 1 0 100 2h3a1 1 0 100-2h-3z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Ver Meus Pedidos
                    </router-link>
                    <router-link
                        :to="{ name: 'home' }"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 border border-border-dark bg-surface-dark text-white font-bold rounded-lg hover:border-yellow-500 hover:text-yellow-500 transition-colors"
                    >
                        Voltar para Início
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
                            class="w-5 h-5 text-yellow-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        O que acontece agora?
                    </h3>
                    <ul class="space-y-3 text-slate-300 text-sm">
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-500 mt-1">⏳</span>
                            <span>
                                Seu pedido foi criado e está aguardando a
                                confirmação do pagamento
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-500 mt-1">⏳</span>
                            <span>
                                Para pagamentos via PIX, a confirmação pode
                                levar alguns minutos. Para boleto ou
                                transferência bancária, pode levar até 2 dias
                                úteis
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-500 mt-1">⏳</span>
                            <span>
                                Você receberá um e-mail assim que o pagamento
                                for aprovado e seus ingressos estarão
                                disponíveis
                            </span>
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="text-yellow-500 mt-1">⏳</span>
                            <span>
                                Você pode acompanhar o status do seu pedido em
                                "Meus Pedidos"
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Warning Card -->
                <div
                    class="mt-6 bg-yellow-500/5 border border-yellow-500/20 rounded-xl p-5 text-left"
                >
                    <div class="flex items-start gap-3">
                        <svg
                            class="w-5 h-5 text-yellow-500 mt-0.5 flex-shrink-0"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div>
                            <h4 class="font-bold text-yellow-500 mb-1">
                                Atenção
                            </h4>
                            <p class="text-sm text-slate-300">
                                Não feche esta página se você ainda não
                                finalizou o pagamento. Se o pagamento for
                                cancelado ou expirar, você precisará criar um
                                novo pedido.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
</script>

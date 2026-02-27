<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar />

        <main class="flex items-center justify-center min-h-[80vh] px-4">
            <div class="max-w-2xl w-full text-center">
                <!-- Error Icon -->
                <div class="mb-8 pt-8 flex justify-center">
                    <div
                        class="relative size-24 rounded-full flex items-center justify-center border-2"
                        :class="
                            errorReason === 'sold_out'
                                ? 'bg-yellow-500/10 border-yellow-500'
                                : 'bg-red-500/10 border-red-500'
                        "
                    >
                        <!-- Error Icon -->
                        <svg
                            v-if="errorReason !== 'sold_out'"
                            class="size-12 text-red-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <!-- Warning Icon for sold out -->
                        <svg
                            v-else
                            class="size-12 text-yellow-500"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                    </div>
                </div>

                <!-- Error Message -->
                <h1
                    class="text-4xl md:text-5xl font-black text-white mb-4 uppercase tracking-tight"
                >
                    {{ errorDetails.title }}
                </h1>
                <p class="text-xl text-slate-300 mb-2">
                    {{ errorDetails.subtitle }}
                </p>
                <p
                    v-if="errorMessage"
                    class="text-sm text-slate-400 mb-8 max-w-xl mx-auto"
                >
                    {{ errorMessage }}
                </p>
                <p v-else class="text-sm text-slate-500 mb-12">
                    Por favor, verifique os dados e tente novamente
                </p>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <router-link
                        v-if="errorReason !== 'sold_out'"
                        :to="{ name: 'payment', params: route.params }"
                        class="inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/20"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Tentar Novamente
                    </router-link>
                    <router-link
                        :to="{ name: 'events' }"
                        :class="
                            errorReason === 'sold_out'
                                ? 'inline-flex items-center justify-center gap-2 px-8 py-4 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-all shadow-lg shadow-primary/20'
                                : 'inline-flex items-center justify-center gap-2 px-8 py-4 border border-border-dark bg-surface-dark text-white font-bold rounded-lg hover:border-primary hover:text-primary transition-colors'
                        "
                    >
                        Ver Outros Eventos
                    </router-link>
                </div>

                <!-- Common Reasons Card -->
                <div
                    class="mt-12 bg-surface-dark border border-border-dark rounded-xl p-6 text-left"
                >
                    <h3
                        class="text-lg font-bold text-white mb-4 flex items-center gap-2"
                    >
                        <svg
                            class="w-5 h-5"
                            :class="
                                errorReason === 'sold_out'
                                    ? 'text-yellow-500'
                                    : 'text-red-500'
                            "
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{
                            errorReason === "sold_out"
                                ? "O que fazer agora?"
                                : "Possíveis Causas"
                        }}
                    </h3>
                    <ul class="space-y-3 text-slate-300 text-sm">
                        <li
                            v-for="(tip, index) in errorDetails.tips"
                            :key="index"
                            class="flex items-start gap-3"
                        >
                            <span
                                class="mt-1"
                                :class="
                                    errorReason === 'sold_out'
                                        ? 'text-yellow-500'
                                        : 'text-red-500'
                                "
                                >•</span
                            >
                            <span>
                                <strong class="text-white"
                                    >{{ tip.title }}:</strong
                                >
                                {{ tip.desc }}
                            </span>
                        </li>
                    </ul>
                </div>

                <!-- Help Card -->
                <div
                    class="mt-6 bg-blue-500/5 border border-blue-500/20 rounded-xl p-5 text-left"
                >
                    <div class="flex items-start gap-3">
                        <svg
                            class="w-5 h-5 text-blue-500 mt-0.5 flex-shrink-0"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        <div>
                            <h4 class="font-bold text-blue-500 mb-1">
                                Precisa de Ajuda?
                            </h4>
                            <p class="text-sm text-slate-300">
                                Fale conosco pelo WhatsApp para ajuda com seu
                                pagamento.
                            </p>
                            <a
                                href="https://wa.me/5584999999999"
                                target="_blank"
                                class="text-primary text-xs font-bold mt-2 inline-flex items-center gap-1 hover:underline"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.890-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"
                                    />
                                </svg>
                                Chamar no WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { computed } from "vue";
import { useRoute } from "vue-router";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";

const route = useRoute();

// Pega os parâmetros da query string
const errorReason = computed(() => route.query.reason || "generic_error");
const errorMessage = computed(
    () => route.query.message || "Não foi possível processar seu pagamento",
);

// Mensagens e dicas específicas por tipo de erro
const errorDetails = computed(() => {
    const details = {
        payment_rejected: {
            title: "Pagamento Recusado",
            subtitle: "Seu pagamento foi recusado pela operadora do cartão",
            icon: "error",
            tips: [
                {
                    title: "Saldo insuficiente",
                    desc: "Verifique se há saldo disponível no cartão",
                },
                {
                    title: "Limite excedido",
                    desc: "Confirme se o limite do cartão não foi atingido",
                },
                {
                    title: "Cartão bloqueado",
                    desc: "Entre em contato com seu banco para verificar",
                },
                {
                    title: "Restrição de segurança",
                    desc: "Algumas operadoras bloqueiam compras online por segurança",
                },
            ],
        },
        invalid_card: {
            title: "Dados do Cartão Inválidos",
            subtitle: "Os dados do cartão fornecidos estão incorretos",
            icon: "error",
            tips: [
                {
                    title: "Número do cartão",
                    desc: "Verifique se digitou corretamente todos os números",
                },
                {
                    title: "Data de validade",
                    desc: "Confirme mês e ano de vencimento do cartão",
                },
                {
                    title: "CVV",
                    desc: "Digite os 3 dígitos no verso do cartão (4 para Amex)",
                },
                {
                    title: "Nome do titular",
                    desc: "Use exatamente como no cartão",
                },
            ],
        },
        sold_out: {
            title: "Ingressos Esgotados",
            subtitle:
                "Os ingressos se esgotaram durante o processo de pagamento",
            icon: "warning",
            tips: [
                {
                    title: "Escolha outro evento",
                    desc: "Veja outros eventos disponíveis",
                },
                {
                    title: "Lista de espera",
                    desc: "Entre em contato para entrar na lista de espera",
                },
                {
                    title: "Eventos futuros",
                    desc: "Cadastre-se para ser notificado de novos eventos",
                },
            ],
        },
        generic_error: {
            title: "Pagamento Não Aprovado",
            subtitle: "Não foi possível processar seu pagamento",
            icon: "error",
            tips: [
                {
                    title: "Dados incorretos",
                    desc: "Confirme o número do cartão, validade, CVV e CPF",
                },
                {
                    title: "Problema temporário",
                    desc: "Aguarde alguns minutos e tente novamente",
                },
                {
                    title: "Tente outro método",
                    desc: "Use outro cartão ou escolha PIX",
                },
                {
                    title: "Verifique sua conexão",
                    desc: "Certifique-se de que está conectado à internet",
                },
            ],
        },
    };

    return details[errorReason.value] || details.generic_error;
});
</script>

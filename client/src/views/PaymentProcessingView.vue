<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar :hide-search="true" :hide-actions="true" />

        <main class="max-w-[720px] mx-auto px-4 md:px-10 py-16">
            <div
                class="bg-surface-dark rounded-2xl border border-border-dark p-10 text-center"
            >
                <div class="flex justify-center mb-6">
                    <div class="relative inline-flex">
                        <div
                            class="size-20 rounded-full bg-primary/10 border border-primary/30 flex items-center justify-center"
                        >
                            <svg
                                class="w-10 h-10 text-primary"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                ></path>
                            </svg>
                        </div>
                    </div>
                </div>

                <h1 class="text-3xl font-bold text-white mb-3">
                    Estamos processando seu pagamento
                </h1>

                <p class="text-slate-300 text-lg mb-2">
                    Em instantes você receberá um e-mail com a confirmação.
                </p>

                <p class="text-slate-400 text-sm mb-8">
                    Pedido
                    <span class="font-mono text-slate-200">{{ reference }}</span>
                </p>

                <div
                    class="bg-surface-darker border border-border-dark rounded-xl p-5 text-left mb-8"
                >
                    <h2 class="font-semibold text-white text-sm uppercase tracking-wider mb-3">
                        O que acontece agora?
                    </h2>
                    <ul class="space-y-2 text-sm text-slate-300">
                        <li class="flex gap-3">
                            <span class="text-primary mt-0.5">●</span>
                            <span>
                                Você pode fechar esta página com segurança.
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-primary mt-0.5">●</span>
                            <span>
                                Se aprovado, enviaremos seus ingressos por
                                e-mail e WhatsApp.
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <span class="text-primary mt-0.5">●</span>
                            <span>
                                Se houver alguma falha, enviaremos um e-mail
                                com o link para tentar novamente.
                            </span>
                        </li>
                    </ul>
                </div>

                <div class="flex justify-center">
                    <router-link
                        :to="{ name: 'my-tickets' }"
                        class="px-6 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        Ver Meus Ingressos
                    </router-link>
                </div>
            </div>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";

defineProps({
    reference: { type: String, required: true },
});
</script>

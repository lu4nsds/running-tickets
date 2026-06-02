<template>
    <div class="min-h-screen bg-background-dark text-slate-100 relative">
        <!-- Background atmospheric glow -->
        <div
            class="pointer-events-none absolute inset-0 z-0 opacity-20 bg-[radial-gradient(ellipse_at_top,_rgba(245,158,11,0.25),transparent_60%)]"
        ></div>

        <div class="relative z-10">
            <Navbar :hide-search="true" :hide-actions="true" />

            <main class="max-w-[560px] mx-auto px-4 md:px-10 py-16 text-center">
                <!-- Status indicator -->
                <div
                    class="relative w-32 h-32 mx-auto mb-8 flex items-center justify-center"
                >
                    <div
                        class="absolute inset-0 rounded-full border border-amber-500/30 animate-ping"
                    ></div>
                    <div
                        class="absolute inset-2 rounded-full border border-amber-500/50 animate-ping"
                        style="animation-delay: 0.3s"
                    ></div>
                    <div
                        class="relative z-10 size-24 rounded-full bg-surface-dark border-2 border-amber-500 flex items-center justify-center shadow-[0_0_30px_rgba(245,158,11,0.2)]"
                    >
                        <svg
                            class="w-9 h-9 text-amber-500 animate-spin"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                    </div>
                </div>

                <h1 class="text-3xl font-bold text-amber-500 mb-3">
                    Processando seu pagamento...
                </h1>

                <p class="text-slate-400 text-base mb-10">
                    Sua transação está sendo verificada pela instituição
                    financeira. Isso pode levar alguns segundos.
                </p>

                <!-- Important card -->
                <div
                    class="bg-surface-dark/60 backdrop-blur-md border border-border-dark rounded-xl p-4 mb-4 text-left"
                >
                    <div class="flex items-center gap-2 mb-2 text-slate-400">
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                        <span
                            class="text-xs font-semibold uppercase tracking-wider"
                            >Importante</span
                        >
                    </div>
                    <p class="text-white">
                        Para ver o resultado, acompanhe o status em
                        <span class="text-primary font-bold"
                            >"Meus Ingressos"</span
                        >.
                    </p>
                </div>

                <!-- Order ID -->
                <div
                    class="flex justify-between items-center bg-surface-dark border border-border-dark rounded-lg px-4 py-3 mb-10"
                >
                    <span
                        class="text-xs font-semibold uppercase tracking-wider text-slate-400"
                        >ID do Pedido</span
                    >
                    <span class="font-mono text-white">{{ reference }}</span>
                </div>

                <!-- What happens now -->
                <div class="text-left mb-10">
                    <h2 class="text-lg font-bold text-white mb-4">
                        O que acontece agora?
                    </h2>
                    <ul class="space-y-4 text-sm text-slate-400">
                        <li class="flex gap-3">
                            <svg
                                class="w-5 h-5 text-primary shrink-0"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"
                                />
                                <path
                                    d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"
                                />
                            </svg>
                            <span>
                                Se aprovado, enviaremos seus ingressos por
                                e-mail e WhatsApp.
                            </span>
                        </li>
                        <li class="flex gap-3">
                            <svg
                                class="w-5 h-5 text-amber-500 shrink-0"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>
                                Se houver alguma falha, enviaremos um e-mail
                                com o link para tentar novamente.
                            </span>
                        </li>
                    </ul>
                </div>

                <router-link
                    :to="{ name: 'my-tickets' }"
                    class="group w-full inline-flex items-center justify-center gap-2 px-6 py-4 bg-primary text-background-dark font-bold rounded-xl hover:shadow-neon transition-all active:scale-95"
                >
                    Ir para Meus Ingressos
                    <svg
                        class="w-5 h-5 transition-transform group-hover:translate-x-1"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M14 5l7 7m0 0l-7 7m7-7H3"
                        />
                    </svg>
                </router-link>
            </main>

            <Footer />
        </div>
    </div>
</template>

<script setup>
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";

defineProps({
    reference: { type: String, required: true },
});
</script>

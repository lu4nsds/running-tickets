<template>
    <div class="min-h-screen flex flex-col bg-background-dark text-slate-100">
        <Navbar />

        <main class="flex-grow">
            <!-- Hero Section -->
            <section
                class="relative h-[500px] w-full overflow-hidden md:h-[600px]"
            >
                <!-- Background Image -->
                <div
                    class="absolute inset-0 bg-cover bg-center"
                    style="
                        background-image: url(&quot;https://images.unsplash.com/photo-1552674605-db6ffd4facb5?w=1920&q=80&quot;);
                    "
                ></div>

                <!-- Overlay Gradients -->
                <div
                    class="absolute inset-0 bg-gradient-to-t from-background-dark via-background-dark/80 to-transparent"
                ></div>
                <div
                    class="absolute inset-0 bg-gradient-to-r from-background-dark via-transparent to-transparent opacity-90"
                ></div>

                <!-- Content -->
                <div
                    class="relative flex h-full flex-col justify-center px-4 sm:px-6 lg:px-12 xl:px-20"
                >
                    <div class="max-w-2xl space-y-6">
                        <div
                            class="inline-flex items-center rounded-full bg-primary/20 px-3 py-1 text-sm font-medium text-primary backdrop-blur-sm border border-primary/20"
                        >
                            <span
                                class="mr-1 h-2 w-2 rounded-full bg-primary animate-pulse"
                            ></span>
                            Temporada 2026 Aberta
                        </div>

                        <h1
                            class="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl md:text-6xl lg:text-7xl"
                        >
                            Participe das <br />
                            <span
                                class="text-transparent bg-clip-text bg-gradient-to-r from-primary to-emerald-400"
                            >
                                Melhores Corridas
                            </span>
                        </h1>

                        <p
                            class="max-w-lg text-lg text-slate-300 md:text-xl leading-relaxed"
                        >
                            Desafie seus limites e encontre seu próximo
                            objetivo. Milhares de corredores já garantiram suas
                            vagas.
                        </p>

                        <div class="flex flex-col gap-4 sm:flex-row pt-4">
                            <button
                                @click="goToEvents"
                                class="inline-flex items-center justify-center rounded-xl bg-primary px-8 py-4 text-base font-bold text-background-dark transition-all hover:bg-primary-dark hover:scale-[1.02] shadow-neon"
                            >
                                Ver Eventos
                                <span
                                    class="material-symbols-outlined ml-2 text-xl"
                                    >arrow_forward</span
                                >
                            </button>
                            <button
                                class="inline-flex items-center justify-center rounded-xl border border-slate-600 bg-surface-dark/50 px-8 py-4 text-base font-bold text-white backdrop-blur-sm transition-all hover:bg-surface-dark hover:border-slate-500"
                            >
                                Saiba Mais
                            </button>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats / Features Strip -->
            <section class="border-y border-border-dark bg-surface-dark/50">
                <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                    <div
                        class="grid grid-cols-2 gap-8 sm:flex sm:justify-between"
                    >
                        <div
                            class="flex flex-col items-center justify-center text-center sm:items-start sm:text-left"
                        >
                            <p class="text-3xl font-black text-white">3 min</p>
                            <p class="text-sm font-medium text-slate-400">
                                Inscrição Rápida
                            </p>
                        </div>
                        <div
                            class="flex flex-col items-center justify-center text-center sm:items-start sm:text-left"
                        >
                            <p class="text-3xl font-black text-white">
                                Nordeste
                            </p>
                            <p class="text-sm font-medium text-slate-400">
                                Eventos Regionais
                            </p>
                        </div>
                        <div
                            class="flex flex-col items-center justify-center text-center sm:items-start sm:text-left"
                        >
                            <p class="text-3xl font-black text-white">100%</p>
                            <p class="text-sm font-medium text-slate-400">
                                Seguro e Verificado
                            </p>
                        </div>
                        <div
                            class="flex flex-col items-center justify-center text-center sm:items-start sm:text-left"
                        >
                            <p class="text-3xl font-black text-white">24/7</p>
                            <p class="text-sm font-medium text-slate-400">
                                Suporte ao Atleta
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Events Grid Section -->
            <section
                id="events"
                class="relative bg-background-dark py-16 sm:py-24"
            >
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <!-- Section Header -->
                    <div
                        class="mb-12 flex flex-col justify-center gap-4 md:flex-row md:items-end"
                    >
                        <div>
                            <h2
                                class="text-3xl font-bold tracking-tight text-white sm:text-4xl"
                            >
                                Próximos Eventos
                            </h2>
                            <p class="mt-2 text-slate-400">
                                Confira as corridas mais aguardadas do ano.
                            </p>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div
                        v-if="eventsStore.loading"
                        class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <div v-for="i in 6" :key="i" class="animate-pulse">
                            <div
                                class="aspect-[16/9] bg-surface-dark rounded-2xl mb-4"
                            ></div>
                            <div
                                class="h-4 bg-surface-dark rounded w-3/4 mb-2"
                            ></div>
                            <div
                                class="h-4 bg-surface-dark rounded w-1/2"
                            ></div>
                        </div>
                    </div>

                    <!-- Events Grid -->
                    <div
                        v-else-if="eventsStore.events.length > 0"
                        class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3"
                    >
                        <EventCard
                            v-for="event in eventsStore.events"
                            :key="event.id"
                            :event="event"
                        />
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-16">
                        <span
                            class="material-symbols-outlined text-6xl text-slate-600 mb-4"
                            >event_busy</span
                        >
                        <p class="text-slate-400">
                            Nenhum evento disponível no momento.
                        </p>
                    </div>

                    <!-- Ver Todos Eventos Button -->
                    <div class="mt-16 flex justify-center">
                        <button
                            @click="goToEvents"
                            class="flex items-center gap-2 rounded-xl border border-slate-700 bg-transparent px-8 py-3 text-sm font-semibold text-white transition-all hover:border-primary hover:text-primary hover:bg-primary/5"
                        >
                            Ver todos os eventos
                            <span class="material-symbols-outlined"
                                >arrow_forward</span
                            >
                        </button>
                    </div>
                </div>
            </section>

            <!-- Newsletter CTA -->
            <section class="border-y border-border-dark bg-surface-dark py-16">
                <div
                    class="flex flex-col items-center gap-8 px-4 text-center sm:px-6 lg:px-12 xl:px-20"
                >
                    <!-- Estado: inscrito -->
                    <template v-if="newsletterSubscribed">
                        <div class="relative">
                            <div
                                class="absolute inset-0 rounded-full border-2 border-primary/30 animate-ping"
                            ></div>
                            <div
                                class="relative rounded-full bg-primary/10 p-4 ring-1 ring-primary/40 shadow-[0_0_24px_rgba(16,185,129,0.25)]"
                            >
                                <svg
                                    class="h-10 w-10 text-primary"
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-2xl font-bold text-white sm:text-3xl">
                                Inscrição realizada!
                            </h2>
                            <p class="max-w-sm text-slate-400">
                                Obrigado por se juntar a nós. Em breve você receberá
                                nossas novidades no seu e-mail.
                            </p>
                        </div>
                    </template>

                    <!-- Estado: formulário -->
                    <template v-else>
                        <div
                            class="rounded-full bg-primary/10 p-4 ring-1 ring-primary/20"
                        >
                            <span
                                class="material-symbols-outlined text-4xl text-primary"
                                >mail</span
                            >
                        </div>

                        <div class="space-y-2">
                            <h2 class="text-2xl font-bold text-white sm:text-3xl">
                                Não perca nenhuma largada
                            </h2>
                            <p class="max-w-2xl text-slate-400">
                                Inscreva-se na nossa newsletter para receber alertas
                                de novas corridas, descontos exclusivos e dicas de
                                treino.
                            </p>
                        </div>

                        <form
                            @submit.prevent="handleNewsletter"
                            class="flex w-full max-w-md flex-col gap-3 sm:flex-row"
                        >
                            <input
                                v-model="newsletterEmail"
                                class="w-full rounded-xl border border-border-dark bg-background-dark px-4 py-3 text-white placeholder-slate-500 focus:border-primary focus:ring-1 focus:ring-primary"
                                placeholder="Seu melhor e-mail"
                                required
                                type="email"
                            />
                            <button
                                type="submit"
                                class="whitespace-nowrap rounded-xl bg-primary px-6 py-3 font-bold text-background-dark transition-all hover:bg-primary-dark"
                            >
                                Inscrever-se
                            </button>
                        </form>
                    </template>
                </div>
            </section>
        </main>

        <Footer />
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import { useEventsStore } from "../stores/events";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import EventCard from "../components/EventCard.vue";

const router = useRouter();
const eventsStore = useEventsStore();
const newsletterEmail = ref("");
const newsletterSubscribed = ref(false);

onMounted(async () => {
    try {
        // Busca apenas 6 eventos para preview na home
        await eventsStore.fetchEvents({ per_page: 6 });
    } catch (error) {
        console.error("Erro ao carregar eventos:", error);
    }
});

function goToEvents() {
    router.push({ name: "events" });
}

function scrollToEvents() {
    const eventsSection = document.getElementById("events");
    if (eventsSection) {
        eventsSection.scrollIntoView({ behavior: "smooth" });
    }
}

function handleNewsletter() {
    newsletterSubscribed.value = true;
    newsletterEmail.value = "";
}
</script>

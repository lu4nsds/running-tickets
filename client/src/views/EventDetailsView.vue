<template>
    <div class="min-h-screen bg-background-dark text-slate-100 font-display">
        <Navbar />

        <!-- Loading State -->
        <EventDetailsSkeleton v-if="loading" />

        <!-- Content -->
        <main v-else-if="event" class="flex-grow">
            <!-- Hero Section -->
            <div
                class="relative w-full h-[400px] md:h-[500px] bg-gray-900 overflow-hidden"
            >
                <div
                    class="absolute inset-0 bg-cover bg-center"
                    :style="{
                        backgroundImage: `url(${event.banner_url || 'https://placehold.co/1920x1080/1e212b/ffffff?text=Evento'})`,
                    }"
                ></div>
                <div
                    class="absolute inset-0 bg-gradient-to-t from-background-dark via-background-dark/80 to-transparent"
                ></div>
                <div
                    class="relative h-full max-w-[1440px] mx-auto px-4 md:px-10 pb-10 flex flex-col justify-end"
                >
                    <span
                        class="inline-flex items-center self-start px-3 py-1 rounded-full text-xs font-bold bg-primary text-background-dark mb-4 shadow-lg shadow-primary/20"
                    >
                        <svg
                            class="w-3.5 h-3.5 mr-1"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        {{ event.status.toUpperCase() }}
                    </span>
                    <h1
                        class="text-4xl md:text-6xl font-black text-white tracking-tight mb-2 uppercase drop-shadow-lg"
                    >
                        {{ event.title }}
                    </h1>
                    <div
                        class="flex flex-wrap items-center gap-6 text-slate-300 text-sm md:text-base mt-2"
                    >
                        <div class="flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-primary"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>{{ event.city }}, {{ event.state }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <svg
                                class="w-5 h-5 text-primary"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>{{ formatDate(event.date_start) }}</span>
                        </div>
                        <div
                            v-if="event.organizer"
                            class="flex items-center gap-2"
                        >
                            <svg
                                class="w-5 h-5 text-primary"
                                fill="currentColor"
                                viewBox="0 0 20 20"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span>Organizador: {{ event.organizer.name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div
                class="layout-container max-w-[1440px] mx-auto px-4 md:px-10 py-8 md:py-12"
            >
                <div
                    class="grid grid-cols-1 lg:grid-cols-12 gap-8 relative items-start"
                >
                    <!-- Left Column -->
                    <div class="lg:col-span-8 flex flex-col gap-10">
                        <!-- Sobre o Evento -->
                        <section>
                            <h2
                                class="text-2xl font-bold text-white mb-4 flex items-center gap-2"
                            >
                                <span
                                    class="w-1.5 h-6 bg-primary rounded-full"
                                ></span>
                                Sobre o Evento
                            </h2>
                            <div
                                class="text-slate-300 leading-relaxed space-y-4 text-base"
                            >
                                <p>{{ event.description }}</p>
                                <div
                                    v-if="event.venue"
                                    class="flex items-center gap-2 text-sm text-slate-400"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span>Local: {{ event.venue }}</span>
                                </div>
                            </div>
                        </section>

                        <!-- Inscrições -->
                        <section id="inscricoes">
                            <div class="flex items-center justify-between mb-6">
                                <h2
                                    class="text-2xl font-bold text-white flex items-center gap-2"
                                >
                                    <span
                                        class="w-1.5 h-6 bg-primary rounded-full"
                                    ></span>
                                    Inscrições
                                </h2>
                            </div>

                            <!-- Lista de Tickets -->
                            <div
                                class="flex flex-col gap-3 max-h-[700px] overflow-y-auto pr-2"
                            >
                                <div
                                    v-for="ticket in filteredTickets"
                                    :key="ticket.id"
                                    :class="[
                                        'bg-surface-dark rounded-xl border border-border-dark p-4 transition-all duration-300',
                                        ticket.is_sold_out
                                            ? 'opacity-50 grayscale'
                                            : 'hover:border-primary/50 group',
                                    ]"
                                >
                                    <div
                                        class="flex items-center gap-4 flex-wrap"
                                    >
                                        <div class="flex-grow min-w-[200px]">
                                            <div
                                                class="flex items-center gap-2 mb-1 flex-wrap"
                                            >
                                                <h3
                                                    class="text-base font-bold text-white"
                                                >
                                                    {{ ticket.name }}
                                                </h3>
                                                <span
                                                    v-if="ticket.is_sold_out"
                                                    class="bg-red-500/20 text-[10px] text-red-500 border border-red-500/30 px-1.5 py-0.5 rounded font-bold uppercase"
                                                >
                                                    Esgotado
                                                </span>
                                            </div>
                                            <p
                                                class="text-xs text-slate-400 mb-2"
                                            >
                                                {{ ticket.description }}
                                            </p>

                                            <!-- Barra de Progresso -->
                                            <div
                                                v-if="ticket.quota"
                                                class="space-y-1"
                                            >
                                                <div
                                                    class="flex justify-between text-xs text-slate-400"
                                                >
                                                    <span
                                                        >{{ ticket.available }}
                                                        de
                                                        {{ ticket.quota }}
                                                        disponíveis</span
                                                    >
                                                    <span
                                                        >{{
                                                            ticket.sold_percentage
                                                        }}% vendido</span
                                                    >
                                                </div>
                                                <div
                                                    class="w-full bg-surface-darker rounded-full h-1.5 overflow-hidden"
                                                >
                                                    <div
                                                        class="h-full rounded-full transition-all duration-500"
                                                        :class="
                                                            ticket.sold_percentage >=
                                                            90
                                                                ? 'bg-red-500'
                                                                : ticket.sold_percentage >=
                                                                    70
                                                                  ? 'bg-yellow-500'
                                                                  : 'bg-primary'
                                                        "
                                                        :style="{
                                                            width: `${ticket.sold_percentage}%`,
                                                        }"
                                                    ></div>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            class="text-right flex flex-col items-end gap-1"
                                        >
                                            <span
                                                class="text-lg font-black text-primary"
                                            >
                                                {{
                                                    formatPrice(
                                                        ticket.price_cents,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            :class="[
                                                'flex items-center rounded-lg border p-1 h-9 ml-4',
                                                ticket.is_sold_out
                                                    ? 'bg-surface-darker/50 border-border-dark'
                                                    : 'bg-surface-darker border-border-dark',
                                            ]"
                                        >
                                            <button
                                                @click="
                                                    decreaseQuantity(ticket.id)
                                                "
                                                :disabled="
                                                    ticket.is_sold_out ||
                                                    (quantities[ticket.id] ||
                                                        0) === 0
                                                "
                                                class="size-7 flex items-center justify-center rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                :class="
                                                    ticket.is_sold_out
                                                        ? 'text-slate-600'
                                                        : 'hover:bg-white/5 text-slate-400'
                                                "
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M3 10a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                            <input
                                                :value="
                                                    quantities[ticket.id] || 0
                                                "
                                                class="w-7 bg-transparent text-center text-sm font-bold border-none p-0 focus:ring-0"
                                                :class="
                                                    ticket.is_sold_out
                                                        ? 'text-slate-600'
                                                        : 'text-white'
                                                "
                                                readonly
                                                type="text"
                                            />
                                            <button
                                                @click="
                                                    increaseQuantity(ticket.id)
                                                "
                                                :disabled="
                                                    ticket.is_sold_out ||
                                                    (quantities[ticket.id] ||
                                                        0) >=
                                                        (ticket.available ||
                                                            999)
                                                "
                                                class="size-7 flex items-center justify-center rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                                :class="
                                                    ticket.is_sold_out
                                                        ? 'text-slate-600'
                                                        : 'bg-primary text-background-dark hover:bg-primary/90'
                                                "
                                            >
                                                <svg
                                                    class="w-4 h-4"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-if="filteredTickets.length === 0"
                                    class="text-center py-12 text-slate-400"
                                >
                                    <svg
                                        class="w-16 h-16 mx-auto mb-4 opacity-50"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <p>
                                        Nenhum ingresso disponível para esta
                                        categoria.
                                    </p>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- Right Column - Sidebar Resumo -->
                    <div class="lg:col-span-4 lg:sticky lg:top-24">
                        <div class="flex flex-col gap-6">
                            <div
                                class="bg-surface-dark rounded-xl border border-border-dark p-6 shadow-2xl"
                            >
                                <h3
                                    class="text-lg font-bold text-white mb-6 border-b border-border-dark/50 pb-4"
                                >
                                    Resumo da Compra
                                </h3>

                                <div
                                    v-if="selectedTickets.length > 0"
                                    class="flex flex-col gap-4 mb-6 max-h-[250px] overflow-y-auto pr-2"
                                >
                                    <div
                                        v-for="item in selectedTickets"
                                        :key="item.id"
                                        class="flex justify-between items-start gap-3"
                                    >
                                        <div class="flex flex-col">
                                            <span
                                                class="text-white font-medium text-sm"
                                            >
                                                {{ item.quantity }}x
                                                {{ item.name }}
                                            </span>
                                        </div>
                                        <span
                                            class="text-white font-bold text-sm"
                                        >
                                            {{
                                                formatPrice(
                                                    item.price_cents *
                                                        item.quantity,
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="text-center py-8 text-slate-400 text-sm"
                                >
                                    <svg
                                        class="w-12 h-12 mx-auto mb-3 opacity-50"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"
                                        />
                                    </svg>
                                    <p>Adicione ingressos ao carrinho</p>
                                </div>

                                <div v-if="selectedTickets.length > 0">
                                    <div
                                        class="border-t border-dashed border-slate-600 mb-4"
                                    ></div>
                                    <div
                                        class="flex justify-between items-end mb-6"
                                    >
                                        <span class="text-slate-300 font-medium"
                                            >Total</span
                                        >
                                        <div class="text-right">
                                            <span
                                                class="text-3xl font-black text-primary tracking-tight"
                                            >
                                                {{ formatPrice(totalAmount) }}
                                            </span>
                                        </div>
                                    </div>
                                    <button
                                        @click="addToCart"
                                        class="w-full group relative flex items-center justify-center bg-primary hover:bg-primary/90 text-background-dark font-bold text-lg h-14 rounded-lg transition-all shadow-[0_0_20px_rgba(0,230,118,0.2)] overflow-hidden"
                                    >
                                        <span
                                            class="relative z-10 flex items-center gap-2"
                                        >
                                            Informar participantes
                                            <svg
                                                class="w-5 h-5 group-hover:translate-x-1 transition-transform"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </span>
                                    </button>
                                    <p
                                        class="text-center text-xs text-slate-500 mt-4 flex items-center justify-center gap-1"
                                    >
                                        <svg
                                            class="w-3.5 h-3.5"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        Pagamento Seguro
                                    </p>
                                </div>
                            </div>

                            <div
                                class="bg-surface-darker/50 rounded-xl border border-border-dark/50 p-4"
                            >
                                <div class="flex items-start gap-3">
                                    <div
                                        class="bg-surface-darker p-2 rounded-lg text-primary border border-border-dark"
                                    >
                                        <svg
                                            class="w-6 h-6"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M18 10c0 3.866-3.582 7-8 7a8.841 8.841 0 01-4.083-.98L2 17l1.338-3.123C2.493 12.767 2 11.434 2 10c0-3.866 3.582-7 8-7s8 3.134 8 7zM7 9H5v2h2V9zm8 0h-2v2h2V9zM9 9h2v2H9V9z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h4
                                            class="text-white font-bold text-sm"
                                        >
                                            Dúvidas?
                                        </h4>
                                        <p
                                            class="text-slate-400 text-xs mt-1 leading-relaxed"
                                        >
                                            Fale conosco pelo WhatsApp para
                                            ajuda com sua inscrição.
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
                    </div>
                </div>
            </div>
        </main>

        <!-- Error State -->
        <div v-else class="flex items-center justify-center min-h-screen px-4">
            <div class="text-center max-w-md">
                <svg
                    class="w-16 h-16 text-red-500 mx-auto mb-4"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <h2 class="text-2xl font-bold text-white mb-3">
                    Evento não encontrado
                </h2>
                <p class="text-slate-400 mb-2">
                    O evento que você está procurando não existe ou foi
                    removido.
                </p>
                <p class="text-sm text-slate-500 mb-6">
                    Slug:
                    <code class="bg-surface-dark px-2 py-1 rounded">{{
                        route.params.slug
                    }}</code>
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <router-link
                        to="/eventos"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors"
                    >
                        <svg
                            class="w-5 h-5"
                            fill="currentColor"
                            viewBox="0 0 20 20"
                        >
                            <path
                                fill-rule="evenodd"
                                d="M9.707 14.707a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 1.414L7.414 9H15a1 1 0 110 2H7.414l2.293 2.293a1 1 0 010 1.414z"
                                clip-rule="evenodd"
                            />
                        </svg>
                        Ver todos os eventos
                    </router-link>
                    <button
                        @click="$router.go(-1)"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3 border border-slate-600 text-white font-bold rounded-lg hover:border-primary hover:text-primary transition-colors"
                    >
                        Voltar
                    </button>
                </div>
            </div>
        </div>

        <Footer />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import axios from "@/api/axios";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import EventDetailsSkeleton from "../components/EventDetailsSkeleton.vue";

const route = useRoute();
const router = useRouter();

const event = ref(null);
const loading = ref(true);
const quantities = ref({});

// Computed
const filteredTickets = computed(() => {
    if (!event.value?.ticket_types) return [];
    return event.value.ticket_types;
});

const selectedTickets = computed(() => {
    if (!event.value?.ticket_types) return [];

    return event.value.ticket_types
        .filter((ticket) => (quantities.value[ticket.id] || 0) > 0)
        .map((ticket) => ({
            id: ticket.id,
            name: ticket.name,
            price_cents: ticket.price_cents,
            quantity: quantities.value[ticket.id] || 0,
        }));
});

const totalAmount = computed(() => {
    return selectedTickets.value.reduce(
        (sum, item) => sum + item.price_cents * item.quantity,
        0,
    );
});

// Methods
function formatDate(dateString) {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
}

function formatPrice(cents) {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(cents / 100);
}

function increaseQuantity(ticketId) {
    const ticket = event.value.ticket_types.find((t) => t.id === ticketId);
    if (!ticket) return;

    // Não permitir se esgotado
    if (ticket.is_sold_out) return;

    const currentQty = quantities.value[ticketId] || 0;
    // Use ?? ao invés de || para tratar corretamente available === 0
    const maxQty = ticket.available ?? 999;

    if (currentQty < maxQty) {
        quantities.value[ticketId] = currentQty + 1;
    }
}

function decreaseQuantity(ticketId) {
    const currentQty = quantities.value[ticketId] || 0;
    if (currentQty > 0) {
        quantities.value[ticketId] = currentQty - 1;
    }
}

async function addToCart() {
    if (selectedTickets.value.length === 0) {
        alert("Adicione pelo menos 1 ingresso ao carrinho");
        return;
    }

    // Salvar dados no localStorage para usar no checkout
    const checkoutData = {
        event: {
            id: event.value.id,
            title: event.value.title,
            slug: event.value.slug,
        },
        tickets: selectedTickets.value,
    };

    localStorage.setItem("checkout_data", JSON.stringify(checkoutData));

    // Redirecionar para o checkout
    router.push({ name: "checkout" });
}

async function fetchEvent() {
    const targetSlug = route.params.slug;

    try {
        loading.value = true;
        event.value = null; // Limpar evento anterior
        quantities.value = {}; // Limpar quantidades

        const response = await axios.get(
            `/events/${targetSlug}`,
        );
        event.value = response.data.data;
    } catch (error) {
        console.error("Erro ao carregar evento:", error);
        event.value = null;
    } finally {
        loading.value = false;
    }
}

onMounted(() => {
    fetchEvent();
});

// Watch para recarregar quando o slug mudar (navegação entre eventos)
watch(
    () => route.params.slug,
    (newSlug, oldSlug) => {
        if (newSlug && newSlug !== oldSlug) {
            fetchEvent();
        }
    },
);
</script>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>

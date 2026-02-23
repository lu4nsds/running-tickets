<template>
    <div
        class="group relative flex flex-col overflow-hidden rounded-2xl bg-surface-dark shadow-lg ring-1 ring-white/5 transition-all hover:-translate-y-1 hover:shadow-xl hover:ring-primary/50"
    >
        <!-- Image -->
        <div class="relative aspect-[16/9] w-full overflow-hidden bg-slate-800">
            <img
                :src="eventImage"
                :alt="event.title"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
            />
        </div>

        <!-- Content -->
        <div class="flex flex-1 flex-col p-5">
            <div class="flex items-center justify-between">
                <span
                    class="text-xs font-semibold uppercase tracking-wider text-primary"
                >
                    {{ participantsInfo }}
                </span>
                <div class="flex items-center text-xs text-slate-400">
                    <span class="material-symbols-outlined mr-1 text-[16px]"
                        >schedule</span
                    >
                    {{ formattedDate }} • {{ formattedTime }}
                </div>
            </div>

            <h3 class="mt-2 text-xl font-bold text-white line-clamp-1">
                {{ event.title }}
            </h3>

            <div class="mt-2 flex items-center text-sm text-slate-400">
                <span
                    class="material-symbols-outlined mr-1 text-[18px] text-primary"
                    >location_on</span
                >
                {{ eventLocation }}
            </div>

            <div class="my-4 h-px w-full bg-border-dark"></div>

            <div class="mt-auto flex items-center justify-between">
                <div>
                    <p class="text-xs text-slate-400">A partir de</p>
                    <p class="text-lg font-bold text-white">
                        {{ formattedPrice }}
                    </p>
                </div>
                <router-link
                    :to="{
                        name: 'event-details',
                        params: { slug: event.slug },
                    }"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-bold text-background-dark transition-colors hover:bg-primary-dark"
                >
                    Ver Detalhes
                </router-link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed } from "vue";
import { format } from "date-fns";
import { ptBR } from "date-fns/locale";

const props = defineProps({
    event: {
        type: Object,
        required: true,
    },
});

const placeholderImage =
    "https://via.placeholder.com/800x450/1E212B/00e677?text=Evento";

// Imagem do evento (EventResource retorna banner_url via accessor banner_full_url)
const eventImage = computed(() => {
    return props.event.banner_url || placeholderImage;
});

// Localização combinada (city + venue)
const eventLocation = computed(() => {
    const parts = [props.event.city, props.event.venue].filter(Boolean);
    return parts.join(" - ") || "Local a definir";
});

// Informação de vagas do evento
const participantsInfo = computed(() => {
    const max = props.event.max_participants;
    if (max && max > 0) {
        return `${max} vagas`;
    }
    return "Inscrições abertas";
});

const formattedDate = computed(() => {
    if (!props.event.date_start) return "";
    try {
        const date = new Date(props.event.date_start);
        return format(date, "dd MMM", { locale: ptBR }).toUpperCase();
    } catch {
        return "";
    }
});

const formattedTime = computed(() => {
    if (!props.event.date_start) return "";
    try {
        const date = new Date(props.event.date_start);
        return format(date, "HH:mm", { locale: ptBR });
    } catch {
        return "";
    }
});

// Calcula menor preço dos ticket_types
const minPriceCents = computed(() => {
    if (!props.event.ticket_types || props.event.ticket_types.length === 0) {
        return 0;
    }
    const prices = props.event.ticket_types
        .filter((type) => type.active && type.price_cents > 0)
        .map((type) => type.price_cents);

    return prices.length > 0 ? Math.min(...prices) : 0;
});

const formattedPrice = computed(() => {
    const price = minPriceCents.value;
    if (price === 0) return "Gratuito";
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(price / 100);
});
</script>

<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="space-y-6">
            <SkeletonCard type="info-section" />
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <SkeletonCard type="info-section" />
                <SkeletonCard type="info-section" />
            </div>
            <SkeletonCard type="table-full" />
        </div>

        <!-- Error State -->
        <ErrorState
            v-else-if="error"
            title="Erro ao carregar evento"
            :message="error"
            @retry="fetchEventDetails"
        />

        <!-- Event Content -->
        <div v-else-if="event" class="space-y-6">
            <!-- Header Actions -->
            <div class="flex items-center justify-between">
                <button
                    @click="$router.back()"
                    class="flex items-center gap-2 text-text-muted hover:text-white transition-colors"
                >
                    <span class="material-symbols-outlined">arrow_back</span>
                    <span>Voltar</span>
                </button>

                <div class="flex items-center gap-3">
                    <button
                        @click="viewDashboard"
                        class="px-4 py-2 bg-primary text-background-dark font-semibold rounded-lg hover:brightness-110 transition-all flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-[20px]">
                            dashboard
                        </span>
                        <span>Ver Dashboard</span>
                    </button>
                </div>
            </div>

            <!-- Event Card -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden"
            >
                <!-- Banner -->
                <div
                    class="relative h-48 bg-gradient-to-br from-surface to-surface-elevated"
                >
                    <img
                        v-if="event.banner_url"
                        :src="event.banner_url"
                        :alt="event.title"
                        class="w-full h-full object-cover"
                    />
                    <div
                        v-else
                        class="w-full h-full flex items-center justify-center"
                    >
                        <span
                            class="material-symbols-outlined text-text-muted text-7xl"
                        >
                            directions_run
                        </span>
                    </div>

                    <!-- Status Badge -->
                    <div class="absolute top-4 right-4">
                        <span
                            :class="[
                                'px-4 py-2 rounded-lg text-sm font-semibold uppercase tracking-wide',
                                getStatusClass(getVisualStatus(event)),
                            ]"
                        >
                            {{ getVisualStatus(event) }}
                        </span>
                    </div>
                </div>

                <!-- Event Info -->
                <div class="p-6">
                    <div class="flex items-start justify-between mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-white mb-2">
                                {{ event.title }}
                            </h1>
                            <p class="text-text-muted text-sm">
                                ID: {{ event.id }} • {{ event.slug }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <!-- Date Start -->
                        <div>
                            <p class="text-text-muted text-sm mb-1">
                                Data de Início
                            </p>
                            <p class="text-white font-medium">
                                {{ formatDate(event.date_start) }}
                            </p>
                        </div>

                        <!-- Date End -->
                        <div>
                            <p class="text-text-muted text-sm mb-1">
                                Data de Término
                            </p>
                            <p class="text-white font-medium">
                                {{ formatDate(event.date_end) }}
                            </p>
                        </div>

                        <!-- Localização -->
                        <div>
                            <p class="text-text-muted text-sm mb-1">
                                Localização
                            </p>
                            <p class="text-white font-medium">
                                {{ event.city }}
                            </p>
                        </div>

                        <!-- Endereço -->
                        <div>
                            <p class="text-text-muted text-sm mb-1">Endereço</p>
                            <p class="text-white font-medium">
                                {{ event.venue || "-" }}
                            </p>
                        </div>

                        <!-- Max Participants -->
                        <div v-if="event.max_participants">
                            <p class="text-text-muted text-sm mb-1">
                                Máx. Participantes
                            </p>
                            <p class="text-white font-medium">
                                {{ formatNumber(event.max_participants) }}
                            </p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div
                        v-if="event.description"
                        class="mt-6 pt-6 border-t border-surface-elevated"
                    >
                        <h3 class="text-lg font-semibold text-white mb-2">
                            Descrição
                        </h3>
                        <p class="text-text-secondary leading-relaxed">
                            {{ event.description }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Organizer Info -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h2
                    class="text-xl font-bold text-white mb-6 flex items-center gap-2"
                >
                    <span
                        class="material-symbols-outlined text-primary text-2xl"
                    >
                        business
                    </span>
                    Organizador
                </h2>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
                >
                    <div>
                        <p class="text-text-muted text-sm mb-1">Nome</p>
                        <p class="text-white font-medium">
                            {{ event.organizer?.name || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-text-muted text-sm mb-1">Email</p>
                        <p class="text-white font-medium">
                            {{ event.organizer?.email || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-text-muted text-sm mb-1">Documento</p>
                        <p class="text-white font-medium">
                            {{ event.organizer?.document || "-" }}
                        </p>
                    </div>
                    <div>
                        <p class="text-text-muted text-sm mb-1">Telefone</p>
                        <p class="text-white font-medium">
                            {{ event.organizer?.phone || "-" }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Categories Table -->
            <div
                v-if="event.categories && event.categories.length > 0"
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h2
                    class="text-xl font-bold text-white mb-6 flex items-center gap-2"
                >
                    <span
                        class="material-symbols-outlined text-primary text-2xl"
                    >
                        category
                    </span>
                    Categorias
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-surface-elevated">
                                <th
                                    class="py-3 px-4 text-left text-text-muted font-medium text-sm"
                                >
                                    Nome
                                </th>
                                <th
                                    class="py-3 px-4 text-left text-text-muted font-medium text-sm"
                                >
                                    Faixa Etária
                                </th>
                                <th
                                    class="py-3 px-4 text-left text-text-muted font-medium text-sm"
                                >
                                    Gênero
                                </th>
                                <th
                                    class="py-3 px-4 text-center text-text-muted font-medium text-sm"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="category in event.categories"
                                :key="category.id"
                                class="border-b border-surface-elevated/50 hover:bg-surface/30 transition-colors"
                            >
                                <td class="py-4 px-4">
                                    <p class="text-white font-medium">
                                        {{ category.name }}
                                    </p>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-text-secondary">
                                        <span
                                            v-if="
                                                category.min_age &&
                                                category.max_age
                                            "
                                        >
                                            {{ category.min_age }} -
                                            {{ category.max_age }} anos
                                        </span>
                                        <span v-else class="text-text-muted"
                                            >-</span
                                        >
                                    </p>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="material-symbols-outlined text-lg"
                                            :class="
                                                getGenderColor(category.gender)
                                            "
                                        >
                                            {{ getGenderIcon(category.gender) }}
                                        </span>
                                        <span class="text-text-secondary">
                                            {{
                                                getGenderLabel(category.gender)
                                            }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-flex px-3 py-1 rounded-full text-xs font-semibold',
                                            category.active
                                                ? 'bg-primary/10 text-primary'
                                                : 'bg-surface text-text-muted',
                                        ]"
                                    >
                                        {{
                                            category.active
                                                ? "Ativo"
                                                : "Inativo"
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Ticket Types Table -->
            <div
                v-if="event.ticket_types && event.ticket_types.length > 0"
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h2
                    class="text-xl font-bold text-white mb-6 flex items-center gap-2"
                >
                    <span
                        class="material-symbols-outlined text-primary text-2xl"
                    >
                        confirmation_number
                    </span>
                    Tipos de Ingresso
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-surface-elevated">
                                <th
                                    class="py-3 px-4 text-left text-text-muted font-medium text-sm"
                                >
                                    Nome do Ticket
                                </th>
                                <th
                                    class="py-3 px-4 text-left text-text-muted font-medium text-sm"
                                >
                                    Preço
                                </th>
                                <th
                                    class="py-3 px-4 text-left text-text-muted font-medium text-sm"
                                >
                                    Período de Venda
                                </th>
                                <th
                                    class="py-3 px-4 text-center text-text-muted font-medium text-sm"
                                >
                                    Quota
                                </th>
                                <th
                                    class="py-3 px-4 text-center text-text-muted font-medium text-sm"
                                >
                                    Status
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="ticket in event.ticket_types"
                                :key="ticket.id"
                                class="border-b border-surface-elevated/50 hover:bg-surface/30 transition-colors"
                            >
                                <td class="py-4 px-4">
                                    <p class="text-white font-medium">
                                        {{ ticket.name }}
                                    </p>
                                    <p
                                        v-if="ticket.description"
                                        class="text-text-muted text-sm mt-1"
                                    >
                                        {{ ticket.description }}
                                    </p>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-white font-semibold">
                                        {{ formatCurrency(ticket.price_cents) }}
                                    </p>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-text-secondary text-sm">
                                        {{ formatDate(ticket.start_sale) }}
                                    </p>
                                    <p class="text-text-muted text-xs">
                                        até {{ formatDate(ticket.end_sale) }}
                                    </p>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <p class="text-white font-medium">
                                        {{ ticket.quota || "Ilimitado" }}
                                    </p>
                                </td>
                                <td class="py-4 px-4 text-center">
                                    <span
                                        :class="[
                                            'inline-flex px-3 py-1 rounded-full text-xs font-semibold',
                                            ticket.active
                                                ? 'bg-primary/10 text-primary'
                                                : 'bg-surface text-text-muted',
                                        ]"
                                    >
                                        {{
                                            ticket.active ? "Ativo" : "Inativo"
                                        }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Metadata -->
            <div
                v-if="event.meta"
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2
                        class="text-xl font-bold text-white flex items-center gap-2"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-2xl"
                        >
                            code
                        </span>
                        Metadados
                    </h2>
                    <button
                        @click="copyMetadata"
                        class="px-3 py-1.5 bg-surface hover:bg-surface-elevated border border-surface-elevated rounded-lg text-text-secondary hover:text-white text-sm transition-colors flex items-center gap-2"
                    >
                        <span class="material-symbols-outlined text-[18px]">
                            content_copy
                        </span>
                        Copiar
                    </button>
                </div>

                <div class="bg-background-dark rounded-lg p-4 overflow-x-auto">
                    <pre class="text-sm text-text-secondary font-mono">{{
                        formatMetadata(event.meta)
                    }}</pre>
                </div>
            </div>

            <!-- Payout Settings -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <div class="flex items-center justify-between mb-6">
                    <h2
                        class="text-xl font-bold text-white flex items-center gap-2"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-2xl"
                        >
                            payments
                        </span>
                        Configurações de Pagamento
                    </h2>
                    <router-link
                        v-if="
                            payoutSettings &&
                            payoutSettings.payout_mode !== 'platform' &&
                            authStore.canEditPaymentSettings
                        "
                        :to="`/organizer/events/${event.id}/payout/config`"
                        class="text-primary hover:underline text-sm flex items-center gap-1"
                    >
                        <span class="material-symbols-outlined text-sm"
                            >edit</span
                        >
                        Editar
                    </router-link>
                </div>

                <div v-if="payoutSettings" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-surface/30 rounded-lg p-4">
                            <p
                                class="text-text-muted text-sm mb-1 flex items-center gap-1"
                            >
                                <span class="material-symbols-outlined text-xs"
                                    >category</span
                                >
                                Modo de Pagamento
                            </p>
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold',
                                        payoutSettings.payout_mode ===
                                        'platform'
                                            ? 'bg-blue-500/10 text-blue-400 border border-blue-500/20'
                                            : 'bg-primary/10 text-primary border border-primary/20',
                                    ]"
                                >
                                    <span
                                        class="material-symbols-outlined text-xs"
                                    >
                                        {{
                                            payoutSettings.payout_mode ===
                                            "platform"
                                                ? "business"
                                                : "person"
                                        }}
                                    </span>
                                    {{
                                        payoutSettings.payout_mode ===
                                        "platform"
                                            ? "Plataforma"
                                            : "Direto"
                                    }}
                                </span>
                            </div>
                        </div>

                        <div class="bg-surface/30 rounded-lg p-4">
                            <p
                                class="text-text-muted text-sm mb-1 flex items-center gap-1"
                            >
                                <span class="material-symbols-outlined text-xs"
                                    >payment</span
                                >
                                Provedor
                            </p>
                            <p class="text-white font-medium">
                                {{ payoutSettings.provider || "Mercado Pago" }}
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="
                            payoutSettings.payout_mode === 'direct' &&
                            payoutSettings.details?.public_key
                        "
                        class="bg-surface/30 rounded-lg p-4"
                    >
                        <p
                            class="text-text-muted text-sm mb-2 flex items-center gap-1"
                        >
                            <span class="material-symbols-outlined text-xs"
                                >vpn_key</span
                            >
                            Credenciais Configuradas
                        </p>
                        <div class="space-y-2">
                            <div class="flex items-center gap-2">
                                <span
                                    class="material-symbols-outlined text-primary text-sm"
                                    >check_circle</span
                                >
                                <span class="text-text-secondary text-sm"
                                    >Public Key:
                                    {{
                                        payoutSettings.details.public_key
                                    }}</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="material-symbols-outlined text-primary text-sm"
                                    >check_circle</span
                                >
                                <span class="text-text-secondary text-sm"
                                    >Access Token: ****{{
                                        payoutSettings.details
                                            .access_token_masked || "****"
                                    }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="payoutSettings.payout_mode === 'platform'"
                        class="bg-blue-500/5 border border-blue-500/20 rounded-lg p-4"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="material-symbols-outlined text-blue-400 text-xl"
                                >info</span
                            >
                            <p class="text-blue-400 text-sm">
                                Este evento está configurado para receber
                                pagamentos através da conta Mercado Pago da
                                plataforma Running Tickets. Os valores serão
                                repassados conforme acordo estabelecido.
                            </p>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8">
                    <span
                        class="material-symbols-outlined text-text-muted text-6xl mb-4 block"
                    >
                        payment
                    </span>
                    <p class="text-text-muted mb-4">
                        Nenhuma configuração de pagamento cadastrada.
                    </p>
                    <router-link
                        v-if="authStore.canEditPaymentSettings"
                        :to="`/organizer/events/${event.id}/payout/config`"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-primary hover:brightness-110 text-background-dark font-semibold rounded-lg transition-all"
                    >
                        <span class="material-symbols-outlined text-sm"
                            >add</span
                        >
                        Configurar Pagamento
                    </router-link>
                    <p v-else class="text-text-muted text-sm">
                        Entre em contato com o administrador do organizador para
                        configurar.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import api from "@/api/axios";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";
import { useEventStatus } from "@/composables/useEventStatus";
import { useLoading } from "@/composables/useLoading";
import { useCurrency } from "@/composables/useCurrency";
import ErrorState from "@/components/ui/ErrorState.vue";
import SkeletonCard from "@/components/ui/SkeletonCard.vue";

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();
const { getVisualStatus, getStatusClass } = useEventStatus();
const { isLoading, error, withLoading } = useLoading(true);
const { formatNumber, formatCurrency } = useCurrency();

const event = ref(null);
const payoutSettings = ref(null);

const fetchEventDetails = async () => {
    console.log("Starting fetchEventDetails...");
    await withLoading(async () => {
        const eventId = route.params.id;

        // Buscar detalhes do evento
        const eventResponse = await api.get(
            API_ENDPOINTS.ORGANIZER.EVENT.DETAIL(eventId),
        );
        console.log("Event data received:", eventResponse.data);
        event.value = eventResponse.data.data || eventResponse.data;
        console.log("event.value after assignment:", event.value);

        // Buscar configurações de pagamento
        try {
            const payoutResponse = await api.get(
                API_ENDPOINTS.ORGANIZER.EVENT.PAYOUT(eventId),
            );
            payoutSettings.value = payoutResponse.data;
        } catch (err) {
            // Se não houver configuração, ignora o erro
            if (err.response?.status !== 404) {
                console.error(
                    "Erro ao buscar configurações de pagamento:",
                    err,
                );
            }
        }
    });
    console.log(
        "After withLoading - isLoading:",
        isLoading.value,
        "error:",
        error.value,
        "event:",
        event.value,
    );
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};

const getGenderIcon = (gender) => {
    switch (gender) {
        case "M":
            return "male";
        case "F":
            return "female";
        case "U":
            return "transgender";
        default:
            return "person";
    }
};

const getGenderLabel = (gender) => {
    switch (gender) {
        case "M":
            return "Masculino";
        case "F":
            return "Feminino";
        case "U":
            return "Unissex";
        default:
            return "Não especificado";
    }
};

const getGenderColor = (gender) => {
    switch (gender) {
        case "M":
            return "text-blue-400";
        case "F":
            return "text-pink-400";
        case "U":
            return "text-purple-400";
        default:
            return "text-text-muted";
    }
};

const formatMetadata = (meta) => {
    if (!meta) return "{}";
    try {
        // Se meta já é string, parse e re-stringify com indentação
        const parsed = typeof meta === "string" ? JSON.parse(meta) : meta;
        return JSON.stringify(parsed, null, 2);
    } catch (e) {
        return JSON.stringify(meta, null, 2);
    }
};

const copyMetadata = async () => {
    try {
        const formatted = formatMetadata(event.value.meta);
        await navigator.clipboard.writeText(formatted);
        // TODO: Adicionar toast de sucesso
        alert("Metadados copiados!");
    } catch (err) {
        console.error("Erro ao copiar:", err);
        alert("Erro ao copiar metadados");
    }
};

const viewDashboard = () => {
    router.push(`/organizer/events/${route.params.id}/dashboard`);
};

onMounted(() => {
    fetchEventDetails();
});
</script>

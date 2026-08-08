<template>
    <div class="space-y-6">
        <!-- Breadcrumb -->
        <nav class="flex items-center gap-2 text-sm">
            <router-link
                to="/admin/events"
                class="text-primary hover:underline"
            >
                Eventos
            </router-link>
            <span class="text-text-muted">/</span>
            <span class="text-text-muted">Novo Evento</span>
        </nav>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white">Criar Novo Evento</h1>
                <p class="text-text-muted mt-1">
                    Preencha as informações abaixo para publicar sua corrida.
                </p>
            </div>
            <router-link
                to="/admin/events"
                class="flex items-center gap-2 px-4 py-2 border border-surface-elevated text-text-secondary rounded-lg hover:border-text-muted hover:text-white transition-colors"
            >
                <span class="material-symbols-outlined text-[18px]"
                    >arrow_back</span
                >
                Voltar
            </router-link>
        </div>

        <!-- Form -->
        <form @submit.prevent="handleSubmit" class="space-y-0">
            <!-- Informações Básicas -->
            <div
                class="bg-card-bg border-x border-t border-surface-elevated rounded-t-xl p-6"
            >
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-[18px]"
                            >info</span
                        >
                    </div>
                    <h3 class="text-white font-semibold">
                        Informações Básicas
                    </h3>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Organizador -->
                    <div>
                        <label
                            for="organizer_id"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Organizador *
                        </label>
                        <select
                            id="organizer_id"
                            v-model="form.organizer_id"
                            class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors appearance-none cursor-pointer"
                            :class="{
                                'border-red-500': errors.organizer_id,
                                'text-text-muted': !form.organizer_id,
                            }"
                        >
                            <option value="" disabled>
                                Selecione um organizador
                            </option>
                            <option
                                v-for="org in organizers"
                                :key="org.id"
                                :value="org.id"
                            >
                                {{ org.name }}
                            </option>
                        </select>
                        <p
                            v-if="errors.organizer_id"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.organizer_id }}
                        </p>
                    </div>

                    <!-- Título -->
                    <div>
                        <label
                            for="title"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Título do Evento *
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            placeholder="Ex: Maratona Noturna de Verão 2024"
                            class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                            :class="{ 'border-red-500': errors.title }"
                            @input="generateSlug"
                        />
                        <p
                            v-if="errors.title"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.title }}
                        </p>
                    </div>
                </div>

                <!-- Slug -->
                <div class="mt-4">
                    <label
                        for="slug"
                        class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                    >
                        Slug (URL Amigável) *
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted text-sm"
                        >
                            runningtickets.com/e/
                        </span>
                        <input
                            id="slug"
                            v-model="form.slug"
                            type="text"
                            placeholder="maratona-noturna-de-verao-2024"
                            class="w-full bg-surface border border-surface-elevated rounded-lg pl-[175px] pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                            :class="{ 'border-red-500': errors.slug }"
                        />
                    </div>
                    <p class="text-text-muted text-xs mt-1">
                        Gerado automaticamente a partir do título.
                    </p>
                    <p v-if="errors.slug" class="text-red-500 text-sm mt-1">
                        {{ errors.slug }}
                    </p>
                </div>

                <!-- Descrição -->
                <div class="mt-4">
                    <MarkdownEditor v-model="form.description" />
                </div>
            </div>

            <!-- Local e Data -->
            <div class="bg-card-bg border border-surface-elevated p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-[18px]"
                            >location_on</span
                        >
                    </div>
                    <h3 class="text-white font-semibold">Local e Data</h3>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <!-- Data de Início -->
                    <BaseDateInput
                        id="date_start"
                        v-model="form.date_start"
                        with-time
                        label="Data e Hora de Início *"
                        :error="errors.date_start"
                    />

                    <!-- Data de Término -->
                    <BaseDateInput
                        id="date_end"
                        v-model="form.date_end"
                        with-time
                        label="Data e Hora de Término"
                        :error="errors.date_end"
                    />
                    <!-- Estado -->
                    <div>
                        <label
                            for="state"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Estado *
                        </label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                            >
                                map
                            </span>
                            <select
                                id="state"
                                v-model="form.state"
                                class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white focus:outline-none focus:border-primary transition-colors appearance-none"
                                :class="{
                                    'border-red-500': errors.state,
                                    'text-text-muted': !form.state,
                                }"
                            >
                                <option value="" disabled>
                                    Selecione um estado
                                </option>
                                <option
                                    v-for="state in BRAZILIAN_STATES"
                                    :key="state.uf"
                                    :value="state.uf"
                                    class="text-white"
                                >
                                    {{ state.name }} ({{ state.uf }})
                                </option>
                            </select>
                        </div>
                        <p
                            v-if="errors.state"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.state }}
                        </p>
                    </div>

                    <!-- Cidade -->
                    <div>
                        <label
                            for="city"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Cidade *
                        </label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                            >
                                location_city
                            </span>
                            <input
                                id="city"
                                v-model="form.city"
                                type="text"
                                placeholder="Ex: Natal"
                                class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                :class="{ 'border-red-500': errors.city }"
                            />
                        </div>
                        <p v-if="errors.city" class="text-red-500 text-sm mt-1">
                            {{ errors.city }}
                        </p>
                    </div>

                    <!-- Local/Venue -->
                    <div>
                        <label
                            for="venue"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Local/Endereço (Venue) *
                        </label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                            >
                                home
                            </span>
                            <input
                                id="venue"
                                v-model="form.venue"
                                type="text"
                                placeholder="Ex: Parque Ibirapuera, Portão 7"
                                class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                :class="{ 'border-red-500': errors.venue }"
                            />
                        </div>
                        <p
                            v-if="errors.venue"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.venue }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Banner -->
            <div class="bg-card-bg border border-surface-elevated p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-[18px]"
                            >image</span
                        >
                    </div>
                    <h3 class="text-white font-semibold">Banner do Evento</h3>
                </div>

                <div class="flex gap-6">
                    <!-- Preview -->
                    <div
                        class="w-48 h-32 rounded-lg bg-surface-elevated overflow-hidden flex-shrink-0"
                    >
                        <img
                            v-if="bannerPreview"
                            :src="bannerPreview"
                            alt="Preview do banner"
                            class="w-full h-full object-cover"
                        />
                        <div
                            v-else
                            class="w-full h-full flex items-center justify-center"
                        >
                            <span
                                class="material-symbols-outlined text-text-muted text-4xl"
                            >
                                add_photo_alternate
                            </span>
                        </div>
                    </div>

                    <!-- Upload -->
                    <div class="flex-1">
                        <label
                            for="banner"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Imagem do Banner
                        </label>
                        <input
                            id="banner"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            @change="handleBannerChange"
                            class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary file:text-black hover:file:brightness-110 cursor-pointer"
                        />
                        <p class="text-text-muted text-xs mt-1">
                            Formatos aceitos: JPEG, PNG, WebP. Tamanho máximo:
                            2MB.
                        </p>
                        <p
                            v-if="errors.banner"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.banner }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Configurações -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-b-xl p-6"
            >
                <div class="flex items-center gap-3 mb-6">
                    <div
                        class="w-8 h-8 rounded-full bg-primary/10 flex items-center justify-center"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-[18px]"
                            >settings</span
                        >
                    </div>
                    <h3 class="text-white font-semibold">Configurações</h3>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <!-- Máximo de Participantes -->
                    <div>
                        <label
                            for="max_participants"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Máximo de Participantes
                        </label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                            >
                                group
                            </span>
                            <input
                                id="max_participants"
                                v-model.number="form.max_participants"
                                type="number"
                                min="0"
                                placeholder="Deixe vazio para ilimitado"
                                class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                            />
                        </div>
                        <p class="text-text-muted text-xs mt-1">
                            Deixe em branco para não ter limite de vagas.
                        </p>
                    </div>

                    <!-- Link dos Resultados -->
                    <div>
                        <label
                            for="results_url"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Link dos Resultados
                        </label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                            >
                                leaderboard
                            </span>
                            <input
                                id="results_url"
                                v-model="form.results_url"
                                type="url"
                                placeholder="https://resultados.exemplo.com/evento"
                                class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                            />
                        </div>
                        <p class="text-text-muted text-xs mt-1">
                            Exibido aos participantes quando o evento estiver
                            encerrado.
                        </p>
                        <p
                            v-if="errors.results_url"
                            class="text-red-500 text-sm mt-1"
                        >
                            {{ errors.results_url }}
                        </p>
                    </div>

                    <!-- Recebimento (modo + taxa) -->
                    <div class="lg:col-span-2">
                        <label
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Modo de Recebimento
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <!-- Centralizado -->
                            <button
                                type="button"
                                @click="form.payout_mode = 'platform'"
                                class="text-left p-4 rounded-lg border transition-colors"
                                :class="
                                    form.payout_mode === 'platform'
                                        ? 'border-primary bg-primary/5'
                                        : 'border-surface-elevated hover:border-text-muted'
                                "
                            >
                                <p class="text-white text-sm font-medium">
                                    Centralizado
                                </p>
                                <p class="text-text-muted text-xs mt-1">
                                    O dinheiro cai na plataforma; o repasse é feito
                                    depois.
                                </p>
                            </button>

                            <!-- Split -->
                            <button
                                type="button"
                                :disabled="!organizerConnected"
                                @click="form.payout_mode = 'split'"
                                class="text-left p-4 rounded-lg border transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                                :class="
                                    form.payout_mode === 'split'
                                        ? 'border-primary bg-primary/5'
                                        : 'border-surface-elevated hover:border-text-muted'
                                "
                            >
                                <p class="text-white text-sm font-medium">
                                    Split (Mercado Pago)
                                </p>
                                <p class="text-text-muted text-xs mt-1">
                                    O organizador recebe direto; a plataforma retém
                                    a comissão.
                                </p>
                            </button>
                        </div>

                        <p
                            v-if="form.organizer_id && !organizerConnected"
                            class="text-amber-400/80 text-xs mt-2 flex items-center gap-1"
                        >
                            <span class="material-symbols-outlined text-[16px]"
                                >info</span
                            >
                            Organizador precisa conectar o Mercado Pago para
                            habilitar o Split.
                        </p>
                        <p v-if="errors.payout_mode" class="text-red-500 text-sm mt-1">
                            {{ errors.payout_mode }}
                        </p>
                    </div>

                    <!-- Taxa da plataforma -->
                    <div>
                        <label
                            for="platform_fee_percent"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Taxa da Plataforma (%)
                        </label>
                        <input
                            id="platform_fee_percent"
                            v-model.number="form.platform_fee_percent"
                            type="number"
                            min="0"
                            max="100"
                            step="0.1"
                            class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors"
                        />
                        <p class="text-text-muted text-xs mt-1">
                            Comissão retida pela plataforma sobre o valor do pedido.
                        </p>
                    </div>

                    <!-- Reembolso fora do prazo -->
                    <div
                        class="lg:col-span-2 flex items-center justify-between p-4 bg-surface/50 rounded-lg border border-surface-elevated"
                    >
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">
                                event_repeat
                            </span>
                            <div>
                                <p class="text-sm font-bold text-white">
                                    Reembolso fora do prazo
                                </p>
                                <p class="text-xs text-text-muted">
                                    Permite que o comprador solicite cancelamento
                                    mesmo após os 7 dias do pagamento, até a data
                                    de início do evento.
                                </p>
                            </div>
                        </div>
                        <label
                            class="relative inline-flex items-center cursor-pointer"
                        >
                            <input
                                v-model="form.allows_late_refund_request"
                                type="checkbox"
                                class="sr-only peer"
                                :disabled="isSubmitting"
                            />
                            <div
                                class="w-11 h-6 bg-surface-elevated rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-text-muted after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary peer-checked:after:bg-background"
                            ></div>
                        </label>
                    </div>

                    <!-- Barra de progresso dos lotes -->
                    <div
                        class="lg:col-span-2 flex items-center justify-between p-4 bg-surface/50 rounded-lg border border-surface-elevated"
                    >
                        <div class="flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary">
                                bar_chart
                            </span>
                            <div>
                                <p class="text-sm font-bold text-white">
                                    Barra de progresso dos lotes
                                </p>
                                <p class="text-xs text-text-muted">
                                    Exibe no portal a barra de vendas e a
                                    contagem de vagas restantes de cada lote.
                                </p>
                            </div>
                        </div>
                        <label
                            class="relative inline-flex items-center cursor-pointer"
                        >
                            <input
                                v-model="form.shows_ticket_progress"
                                type="checkbox"
                                class="sr-only peer"
                                :disabled="isSubmitting"
                            />
                            <div
                                class="w-11 h-6 bg-surface-elevated rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-text-muted after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary peer-checked:after:bg-background"
                            ></div>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-end gap-4 pt-6">
                <router-link
                    to="/admin/events"
                    class="px-6 py-3 text-text-secondary hover:text-white transition-colors"
                >
                    Cancelar
                </router-link>
                <button
                    type="submit"
                    :disabled="isSubmitting"
                    class="flex items-center gap-2 px-6 py-3 bg-primary text-black rounded-lg font-semibold hover:brightness-110 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span
                        v-if="isSubmitting"
                        class="material-symbols-outlined text-[20px] animate-spin"
                    >
                        progress_activity
                    </span>
                    <span v-else class="material-symbols-outlined text-[20px]"
                        >save</span
                    >
                    {{ isSubmitting ? "Salvando..." : "Salvar Evento" }}
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { useEventsStore } from "@/stores/events";
import { useToast } from "@/composables/useToast";
import { BRAZILIAN_STATES } from "@/constants/brazilianStates";
import { organizerPaymentAccountApi } from "@/api/organizer/paymentAccount";
import MarkdownEditor from "@/components/MarkdownEditor.vue";
import BaseDateInput from "@/components/ui/BaseDateInput.vue";

const router = useRouter();
const store = useEventsStore();
const toast = useToast();

// State
const isSubmitting = ref(false);
const organizers = ref([]);
const bannerPreview = ref(null);
const bannerFile = ref(null);

const form = reactive({
    organizer_id: "",
    title: "",
    slug: "",
    description: "",
    state: "",
    city: "",
    venue: "",
    date_start: "",
    date_end: "",
    max_participants: null,
    results_url: "",
<<<<<<< HEAD
    payout_mode: "platform",
    platform_fee_percent: 10,
=======
    allows_late_refund_request: false,
    shows_ticket_progress: false,
>>>>>>> main
});

// Status de conexão MP do organizador selecionado (habilita o Split).
const organizerConnected = ref(false);

const errors = reactive({
    organizer_id: "",
    title: "",
    slug: "",
    state: "",
    city: "",
    venue: "",
    date_start: "",
    date_end: "",
    results_url: "",
    banner: "",
    payout_mode: "",
});

// Methods
const generateSlug = () => {
    form.slug = form.title
        .toLowerCase()
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .replace(/[^a-z0-9\s-]/g, "")
        .replace(/\s+/g, "-")
        .replace(/-+/g, "-")
        .trim();
};

const handleBannerChange = (e) => {
    const file = e.target.files?.[0];
    if (!file) return;

    // Validar tipo
    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type)) {
        errors.banner = "Formato inválido. Use JPEG, PNG ou WebP.";
        return;
    }

    // Validar tamanho (2MB)
    if (file.size > 2 * 1024 * 1024) {
        errors.banner = "Arquivo muito grande. Máximo 2MB.";
        return;
    }

    errors.banner = "";
    bannerFile.value = file;
    bannerPreview.value = URL.createObjectURL(file);
};

const loadOrganizers = async () => {
    const result = await store.fetchOrganizers();
    if (result.success) {
        organizers.value = result.data;
    }
};

// Ao trocar o organizador, verifica se ele tem o Mercado Pago conectado.
// Se não, o Split fica desabilitado e o modo volta para centralizado.
watch(
    () => form.organizer_id,
    async (organizerId) => {
        organizerConnected.value = false;
        if (!organizerId) return;

        try {
            const data =
                await organizerPaymentAccountApi.statusForOrganizer(organizerId);
            organizerConnected.value = data.account?.connected === true;
        } catch {
            organizerConnected.value = false;
        }

        if (!organizerConnected.value && form.payout_mode === "split") {
            form.payout_mode = "platform";
        }
    },
);

// Validation
const validate = () => {
    let isValid = true;

    // Reset errors
    Object.keys(errors).forEach((key) => (errors[key] = ""));

    if (!form.organizer_id) {
        errors.organizer_id = "Selecione um organizador";
        isValid = false;
    }

    if (!form.title.trim()) {
        errors.title = "Título é obrigatório";
        isValid = false;
    }

    if (!form.slug.trim()) {
        errors.slug = "Slug é obrigatório";
        isValid = false;
    }

    if (!form.state || !form.state.trim()) {
        errors.state = "Estado é obrigatório";
        isValid = false;
    }

    if (!form.city.trim()) {
        errors.city = "Cidade é obrigatória";
        isValid = false;
    }

    if (!form.venue.trim()) {
        errors.venue = "Local é obrigatório";
        isValid = false;
    }

    if (!form.date_start) {
        errors.date_start = "Data de início é obrigatória";
        isValid = false;
    }

    if (form.date_end && form.date_start && form.date_end < form.date_start) {
        errors.date_end = "Data de término deve ser após a data de início";
        isValid = false;
    }

    return isValid;
};

// Submit
const handleSubmit = async () => {
    if (!validate()) {
        toast.error("Por favor, corrija os erros no formulário");
        return;
    }

    isSubmitting.value = true;

    try {
        // Criar FormData para enviar arquivo
        const formData = new FormData();
        formData.append("organizer_id", form.organizer_id);
        formData.append("title", form.title);
        formData.append("slug", form.slug);
        formData.append("description", form.description || "");
        formData.append("state", form.state);
        formData.append("city", form.city);
        formData.append("venue", form.venue);
        formData.append("date_start", form.date_start);
        if (form.date_end) {
            formData.append("date_end", form.date_end);
        }
        if (form.max_participants) {
            formData.append("max_participants", form.max_participants);
        }
        if (form.results_url) {
            formData.append("results_url", form.results_url);
        }
        // "1"/"0": em FormData um booleano vira string, e "false" é truthy no PHP.
        formData.append(
            "allows_late_refund_request",
            form.allows_late_refund_request ? "1" : "0",
        );
        formData.append(
            "shows_ticket_progress",
            form.shows_ticket_progress ? "1" : "0",
        );

        // Recebimento: modo + taxa da plataforma (converte % → fração).
        formData.append("payout_mode", form.payout_mode);
        if (
            form.platform_fee_percent !== null &&
            form.platform_fee_percent !== ""
        ) {
            formData.append(
                "platform_fee_rate",
                (Number(form.platform_fee_percent) / 100).toString(),
            );
        }

        if (bannerFile.value) {
            formData.append("banner", bannerFile.value);
        }

        const result = await store.createEvent(formData);

        if (result.success) {
            toast.success("Evento criado com sucesso!");
            router.push(`/admin/events/${result.data.id}`);
        } else {
            // Handle validation errors from API
            if (result.errors) {
                Object.keys(result.errors).forEach((key) => {
                    if (errors[key] !== undefined) {
                        errors[key] = result.errors[key][0];
                    }
                });
            }
            toast.error(result.error || "Erro ao criar evento");
        }
    } catch (err) {
        toast.error("Erro ao criar evento");
    } finally {
        isSubmitting.value = false;
    }
};

// Lifecycle
onMounted(() => {
    loadOrganizers();
});
</script>

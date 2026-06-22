<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar />

        <!-- Loading State -->
        <CheckoutFormSkeleton v-if="loading" />

        <div v-else class="max-w-7xl mx-auto px-4 py-8">

            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-white mb-2">
                    Identificação dos Participantes
                </h1>
                <p class="text-slate-400">
                    Preencha os dados de cada participante da corrida
                </p>
            </div>

            <!-- Steps Indicator -->
            <div class="mb-8 flex items-center justify-center gap-2">
                <div class="flex items-center">
                    <div
                        class="w-10 h-10 rounded-full bg-primary text-background-dark font-bold flex items-center justify-center"
                    >
                        1
                    </div>
                    <span class="ml-2 text-sm font-medium text-white"
                        >Participantes</span
                    >
                </div>
                <div class="w-16 h-0.5 bg-border-dark"></div>
                <div class="flex items-center">
                    <div
                        class="w-10 h-10 rounded-full bg-surface-dark border-2 border-border-dark text-slate-500 font-bold flex items-center justify-center"
                    >
                        2
                    </div>
                    <span class="ml-2 text-sm font-medium text-slate-500"
                        >Pagamento</span
                    >
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Formulários dos Participantes -->
                <div class="lg:col-span-2 space-y-3">
                    <div
                        v-for="(participant, index) in participants"
                        :key="index"
                        class="bg-surface-dark rounded-xl border border-border-dark overflow-hidden transition-all"
                        :class="
                            expandedParticipant === index
                                ? 'ring-2 ring-primary/30'
                                : ''
                        "
                    >
                        <!-- Header do Participante (sempre visível) -->
                        <button
                            @click="toggleParticipant(index)"
                            class="w-full px-6 py-4 flex items-center justify-between hover:bg-surface-darker/50 transition-colors"
                            type="button"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="inline-flex items-center justify-center w-10 h-10 rounded-full text-sm font-bold"
                                    :class="
                                        isParticipantComplete(participant)
                                            ? 'bg-primary/20 text-primary'
                                            : 'bg-surface-darker text-slate-400'
                                    "
                                >
                                    {{ String(index + 1).padStart(2, "0") }}
                                </span>
                                <div class="text-left">
                                    <h3 class="text-base font-bold text-white">
                                        {{
                                            participant.name ||
                                            `Participante ${String(index + 1).padStart(2, "0")}`
                                        }}
                                    </h3>
                                    <p class="text-xs text-slate-400">
                                        {{ getTicketName(index) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span
                                    v-if="expandedParticipant !== index"
                                    class="text-[10px] px-2 py-1 rounded-full border font-bold uppercase"
                                    :class="
                                        getParticipantStatus(participant).class
                                    "
                                >
                                    {{ getParticipantStatus(participant).text }}
                                </span>
                                <svg
                                    class="w-5 h-5 text-slate-400 transition-transform"
                                    :class="
                                        expandedParticipant === index
                                            ? 'rotate-180'
                                            : ''
                                    "
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                        </button>

                        <!-- Formulário (colapsável) -->
                        <div
                            v-show="expandedParticipant === index"
                            class="px-6 pb-6"
                        >
                            <div class="border-t border-border-dark mb-6"></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div class="md:col-span-2">
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        Categoria Esportiva
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        v-model="participant.category_id"
                                        required
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white focus:outline-none focus:border-primary transition-colors"
                                    >
                                        <option value="" disabled>
                                            Selecione a categoria
                                        </option>
                                        <option
                                            v-for="category in categories"
                                            :key="category.id"
                                            :value="category.id"
                                        >
                                            {{ category.name }}
                                            <span v-if="category.distance">
                                                - {{ category.distance }}K
                                            </span>
                                        </option>
                                    </select>
                                    <p
                                        v-if="
                                            errors[
                                                `participants.${index}.category_id`
                                            ]
                                        "
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{
                                            errors[
                                                `participants.${index}.category_id`
                                            ]
                                        }}
                                    </p>
                                </div>

                                <div class="md:col-span-2">
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        Nome Completo
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="participant.name"
                                        type="text"
                                        required
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                        placeholder="João Silva Santos"
                                    />
                                    <p
                                        v-if="
                                            errors[`participants.${index}.name`]
                                        "
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{
                                            errors[`participants.${index}.name`]
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        E-mail
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="participant.email"
                                        type="email"
                                        required
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                        placeholder="joao@exemplo.com"
                                    />
                                    <p
                                        v-if="errors[`participants.${index}.email`]"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ errors[`participants.${index}.email`] }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        Celular (WhatsApp)
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="participant.phone"
                                        @input="(e) => formatPhone(e, index)"
                                        type="tel"
                                        required
                                        maxlength="15"
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                        placeholder="(11) 99999-9999"
                                        inputmode="numeric"
                                    />
                                    <p
                                        v-if="errors[`participants.${index}.phone`]"
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{ errors[`participants.${index}.phone`] }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        CPF
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="participant.cpf"
                                        @input="(e) => formatCPF(e, index)"
                                        type="text"
                                        required
                                        maxlength="14"
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                        placeholder="000.000.000-00"
                                    />
                                    <p
                                        v-if="
                                            errors[`participants.${index}.cpf`]
                                        "
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{
                                            errors[`participants.${index}.cpf`]
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        Data de Nascimento
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="participant.birthdate"
                                        type="date"
                                        required
                                        :max="maxDate"
                                        :min="minDate"
                                        @change="validateBirthdateField(participant, index)"
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white placeholder-slate-500 focus:outline-none focus:border-primary transition-colors"
                                    />
                                    <p
                                        v-if="
                                            errors[
                                                `participants.${index}.birthdate`
                                            ]
                                        "
                                        class="mt-1 text-sm text-red-500"
                                    >
                                        {{
                                            errors[
                                                `participants.${index}.birthdate`
                                            ]
                                        }}
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        Tamanho da Camisa
                                    </label>
                                    <select
                                        v-model="participant.shirt_size"
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white focus:outline-none focus:border-primary transition-colors"
                                    >
                                        <option value="">Selecione</option>
                                        <option value="PP">PP</option>
                                        <option value="P">P</option>
                                        <option value="M">M</option>
                                        <option value="G">G</option>
                                        <option value="GG">GG</option>
                                        <option value="XG">XG</option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        Cidade
                                    </label>
                                    <CityAutocomplete v-model="participant.city" />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-300 mb-2"
                                    >
                                        Equipe / Assessoria
                                    </label>
                                    <input
                                        v-model="participant.team"
                                        type="text"
                                        placeholder="Ex: Run Team RN"
                                        class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white focus:outline-none focus:border-primary transition-colors placeholder:text-slate-500"
                                    />
                                </div>
                            </div>

                            <!-- Botão para próximo participante -->
                            <div
                                v-if="index < participants.length - 1"
                                class="mt-6 flex justify-end"
                            >
                                <button
                                    @click="toggleParticipant(index + 1)"
                                    type="button"
                                    class="px-6 py-2.5 bg-primary/10 hover:bg-primary/20 text-primary font-semibold rounded-lg transition-colors flex items-center gap-2"
                                >
                                    Próximo participante
                                    <svg
                                        class="w-4 h-4"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Resumo do Pedido (Sidebar) -->
                <div class="lg:col-span-1">
                    <div
                        class="bg-surface-dark rounded-xl border border-border-dark p-6 sticky top-24"
                    >
                        <h3
                            class="text-lg font-bold text-white mb-4 pb-4 border-b border-border-dark"
                        >
                            Resumo do Pedido
                        </h3>

                        <div class="space-y-3 mb-4">
                            <div
                                v-for="item in selectedTickets"
                                :key="item.id"
                                class="flex justify-between text-sm"
                            >
                                <span class="text-slate-300">
                                    {{ item.quantity }}x
                                    {{ item.name }}
                                </span>
                                <span class="text-white font-medium">
                                    {{
                                        formatPrice(
                                            item.price_cents * item.quantity,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>

                        <div
                            class="flex justify-between items-end pt-4 border-t border-border-dark"
                        >
                            <span class="text-slate-300 font-medium"
                                >Total</span
                            >
                            <div class="text-right">
                                <div class="text-2xl font-black text-primary">
                                    {{ formatPrice(totalAmount) }}
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-border-dark">
                            <div class="flex items-start gap-2 text-xs mb-4">
                                <svg
                                    class="w-4 h-4 text-primary flex-shrink-0 mt-0.5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <p class="text-slate-400">
                                    Pagamento seguro via Mercado Pago. Seus
                                    dados estão protegidos.
                                </p>
                            </div>

                            <BaseCheckbox
                                v-model="consentAccepted"
                                class="mb-4"
                            >
                                Li e aceito os
                                <router-link
                                    :to="{ name: 'terms-of-use' }"
                                    target="_blank"
                                    class="text-primary hover:underline"
                                    >Termos de Uso</router-link
                                >, a
                                <router-link
                                    :to="{ name: 'privacy-policy' }"
                                    target="_blank"
                                    class="text-primary hover:underline"
                                    >Política de Privacidade</router-link
                                >
                                e a
                                <router-link
                                    :to="{ name: 'refund-policy' }"
                                    target="_blank"
                                    class="text-primary hover:underline"
                                    >Política de Reembolso</router-link
                                >.
                            </BaseCheckbox>

                            <button
                                @click="proceedToPayment"
                                :disabled="!isFormValid || !consentAccepted || isSubmitting"
                                class="w-full px-6 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-all disabled:opacity-50 disabled:cursor-not-allowed shadow-lg shadow-primary/20"
                            >
                                <span
                                    v-if="!isSubmitting"
                                    class="flex items-center justify-center gap-2"
                                >
                                    Prosseguir para o Pagamento
                                    <svg
                                        class="w-5 h-5"
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
                                <span v-else>
                                    Processando<span class="animate-pulse"
                                        >...</span
                                    >
                                </span>
                            </button>

                            <button
                                @click="goBack"
                                class="w-full mt-3 px-6 py-2 bg-surface-darker border border-border-dark text-slate-300 rounded-lg hover:border-primary transition-colors text-sm"
                            >
                                Voltar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <Footer />
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import api from "../api/axios";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";
import CheckoutFormSkeleton from "../components/CheckoutFormSkeleton.vue";
import CityAutocomplete from "../components/CityAutocomplete.vue";
import BaseCheckbox from "../components/base/BaseCheckbox.vue";
import { useAuthStore } from "../stores/auth";
import { useCheckoutStore } from "../stores/checkout";
import { useToast } from "../composables/useToast";

const router = useRouter();
const authStore = useAuthStore();
const checkoutStore = useCheckoutStore();
const toast = useToast();

const loading = ref(true);
const participants = ref([]);
const categories = ref([]);
const selectedTickets = ref([]);
const eventData = ref(null);
const errors = ref({});
const isSubmitting = ref(false);
const expandedParticipant = ref(0); // Primeiro participante expandido por padrão
const consentAccepted = ref(false);

const maxDate = computed(() => {
    const today = new Date();
    return today.toISOString().split("T")[0];
});

const minDate = computed(() => {
    const d = new Date();
    d.setFullYear(d.getFullYear() - 120);
    return d.toISOString().split("T")[0];
});

function isValidBirthdate(dateStr) {
    if (!dateStr) return false;
    const year = new Date(dateStr).getFullYear();
    const currentYear = new Date().getFullYear();
    return year >= currentYear - 120 && year <= currentYear;
}

function validateBirthdateField(participant, index) {
    const key = `participants.${index}.birthdate`;
    if (!participant.birthdate) {
        errors.value[key] = "Data de nascimento é obrigatória";
    } else if (!isValidBirthdate(participant.birthdate)) {
        errors.value[key] = "Ano inválido. Informe uma data de nascimento real";
    } else {
        delete errors.value[key];
    }
}

const totalAmount = computed(() => {
    return selectedTickets.value.reduce(
        (sum, item) => sum + item.price_cents * item.quantity,
        0,
    );
});

const isFormValid = computed(() => {
    return participants.value.every(
        (p) => p.name && p.email && p.phone && p.cpf && isValidBirthdate(p.birthdate) && p.category_id,
    );
});

function isParticipantComplete(participant) {
    return (
        participant.name &&
        participant.email &&
        participant.phone &&
        participant.cpf &&
        isValidBirthdate(participant.birthdate) &&
        participant.category_id
    );
}

function toggleParticipant(index) {
    expandedParticipant.value =
        expandedParticipant.value === index ? -1 : index;
}

function getParticipantStatus(participant) {
    if (!participant.name && !participant.email && !participant.cpf) {
        return {
            text: "Pendente",
            class: "bg-slate-500/20 text-slate-400 border-slate-500/30",
        };
    }
    if (isParticipantComplete(participant)) {
        return {
            text: "Completo",
            class: "bg-green-500/20 text-green-400 border-green-500/30",
        };
    }
    return {
        text: "Incompleto",
        class: "bg-yellow-500/20 text-yellow-400 border-yellow-500/30",
    };
}

function formatPrice(cents) {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(cents / 100);
}

function formatPhone(event, index) {
    let value = event.target.value.replace(/\D/g, "");

    if (value.length > 11) value = value.slice(0, 11);

    if (value.length > 10) {
        value = value.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
    } else if (value.length > 6) {
        value = value.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
    } else if (value.length > 2) {
        value = value.replace(/(\d{2})(\d{0,5})/, "($1) $2");
    }

    participants.value[index].phone = value;
}

function formatCPF(event, index) {
    let value = event.target.value.replace(/\D/g, "");

    if (value.length > 11) {
        value = value.slice(0, 11);
    }

    if (value.length > 9) {
        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    } else if (value.length > 6) {
        value = value.replace(/(\d{3})(\d{3})(\d{1,3})/, "$1.$2.$3");
    } else if (value.length > 3) {
        value = value.replace(/(\d{3})(\d{1,3})/, "$1.$2");
    }

    participants.value[index].cpf = value;
}

function getTicketName(index) {
    let currentIndex = 0;
    for (const item of selectedTickets.value) {
        if (index < currentIndex + item.quantity) {
            return item.name;
        }
        currentIndex += item.quantity;
    }
    return "";
}

function initializeParticipants() {
    // Criar um participante para cada ticket selecionado
    participants.value = [];

    selectedTickets.value.forEach((ticket) => {
        for (let i = 0; i < ticket.quantity; i++) {
            participants.value.push({
                ticket_type_id: ticket.id,
                category_id: "",
                name: "",
                email: "",
                phone: "",
                cpf: "",
                birthdate: "",
                shirt_size: "",
                city: "",
                team: "",
                emergency_contact: "",
                rg: "",
            });
        }
    });
}

function validateForm() {
    errors.value = {};
    let hasErrors = false;

    participants.value.forEach((participant, index) => {
        // Categoria
        if (!participant.category_id) {
            errors.value[`participants.${index}.category_id`] =
                "Selecione uma categoria";
            hasErrors = true;
        }

        // Nome
        if (!participant.name || participant.name.trim().length < 3) {
            errors.value[`participants.${index}.name`] =
                "Nome deve ter no mínimo 3 caracteres";
            hasErrors = true;
        }

        // Email
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!participant.email || !emailRegex.test(participant.email)) {
            errors.value[`participants.${index}.email`] = "Email inválido";
            hasErrors = true;
        }

        // Celular
        const phoneClean = participant.phone.replace(/\D/g, "");
        if (phoneClean.length < 10 || phoneClean.length > 11) {
            errors.value[`participants.${index}.phone`] = "Celular inválido";
            hasErrors = true;
        }

        // CPF
        const cpfClean = participant.cpf.replace(/\D/g, "");
        if (cpfClean.length !== 11) {
            errors.value[`participants.${index}.cpf`] = "CPF inválido";
            hasErrors = true;
        }

        // Verificar CPFs duplicados
        const cpfDuplicates = participants.value.filter(
            (p) => p.cpf === participant.cpf,
        );
        if (cpfDuplicates.length > 1) {
            errors.value[`participants.${index}.cpf`] =
                "CPF duplicado neste pedido";
            hasErrors = true;
        }

        // Data de nascimento
        if (!isValidBirthdate(participant.birthdate)) {
            errors.value[`participants.${index}.birthdate`] = !participant.birthdate
                ? "Data de nascimento é obrigatória"
                : "Ano inválido. Informe uma data de nascimento real";
            hasErrors = true;
        }
    });

    return !hasErrors;
}

function goBack() {
    router.go(-1);
}

async function proceedToPayment() {
    if (!validateForm()) {
        toast.error("Por favor, corrija os erros no formulário");
        return;
    }

    if (!consentAccepted.value) {
        toast.error(
            "Você precisa aceitar os Termos de Uso, a Política de Privacidade e a Política de Reembolso",
        );
        return;
    }

    isSubmitting.value = true;

    try {
        // Preparar dados para criar o pedido
        const orderData = {
            event_id: eventData.value.id,
            items: participants.value.map((p) => ({
                ticket_type_id: p.ticket_type_id,
                category_id: p.category_id,
                participant_data: {
                    name: p.name,
                    email: p.email,
                    phone: p.phone.replace(/\D/g, ""),
                    cpf: p.cpf.replace(/\D/g, ""),
                    birthdate: p.birthdate,
                    shirt_size: p.shirt_size || null,
                    city: p.city || null,
                    team: p.team || null,
                },
            })),
        };

        // Criar o pedido no backend
        const response = await api.post("/orders", orderData);

        const { order } = response.data;

        checkoutStore.setParticipants(participants.value);

        // Limpar flag de guest checkout (não é mais necessária após criar o pedido)
        sessionStorage.removeItem("checkoutGuest");

        // Navegar para a tela de pagamento — Order é carregado da API pelo reference,
        // não mais via sessionStorage.
        router.push({ name: "payment", params: { reference: order.reference } });
    } catch (error) {
        console.error("Erro ao criar pedido:", error);

        let errorMessage = "Erro ao processar pedido. Tente novamente.";

        if (error.response?.data?.message) {
            errorMessage = error.response.data.message;
        } else if (error.response?.data?.errors) {
            const errors = Object.values(error.response.data.errors).flat();
            errorMessage = errors.join("\n");
        }

        toast.error(errorMessage);
    } finally {
        isSubmitting.value = false;
    }
}

async function fetchCategories(eventSlugOrId) {
    if (!eventSlugOrId) return;

    try {
        const response = await api.get(`/events/${eventSlugOrId}/categories`);
        categories.value = response.data.data || [];
    } catch (error) {
        console.error("Erro ao buscar categorias:", error);
        categories.value = [];
    }
}

onMounted(async () => {
    loading.value = true;

    if (!checkoutStore.hasCheckoutData) {
        toast.warning("Nenhum ingresso selecionado");
        router.push({ name: "events" });
        return;
    }

    try {
        const data = checkoutStore.checkoutData;
        selectedTickets.value = data.tickets || [];
        eventData.value = data.event || null;

        if (selectedTickets.value.length === 0) {
            toast.warning("Nenhum ingresso selecionado");
            router.push({ name: "events" });
            return;
        }

        await fetchCategories(eventData.value?.slug || eventData.value?.id);

        initializeParticipants();

        const saved = checkoutStore.participants;
        if (saved.length) {
            const validIds = new Set(selectedTickets.value.map((t) => t.id));
            const compatible =
                saved.length === participants.value.length &&
                saved.every((p) => validIds.has(p.ticket_type_id));
            if (compatible) {
                participants.value = saved;
            }
        } else if (authStore.isAuthenticated && authStore.user && participants.value.length > 0) {
            participants.value[0].name = authStore.user.name || "";
            participants.value[0].email = authStore.user.email || "";
        }
    } catch (error) {
        console.error("Erro ao carregar dados do checkout:", error);
        toast.error("Erro ao carregar dados");
        router.push({ name: "events" });
    } finally {
        loading.value = false;
    }
});
</script>

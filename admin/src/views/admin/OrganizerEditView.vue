<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="space-y-6">
            <div class="flex items-center justify-between">
                <div
                    class="h-8 w-48 bg-surface-elevated rounded animate-pulse"
                ></div>
                <div
                    class="h-10 w-24 bg-surface-elevated rounded animate-pulse"
                ></div>
            </div>

            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <div class="space-y-2">
                        <div
                            class="h-5 w-32 bg-surface-elevated rounded animate-pulse"
                        ></div>
                        <div
                            class="h-4 w-48 bg-surface-elevated rounded animate-pulse"
                        ></div>
                    </div>
                    <div class="lg:col-span-2 space-y-4">
                        <div
                            class="h-12 w-full bg-surface-elevated rounded animate-pulse"
                        ></div>
                        <div
                            class="h-12 w-full bg-surface-elevated rounded animate-pulse"
                        ></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <ErrorState
            v-else-if="loadError"
            title="Erro ao carregar organizador"
            :message="loadError"
            @retry="fetchOrganizer"
        />

        <!-- Form Content -->
        <template v-else-if="organizer">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-white">
                    Editar Organizador
                </h1>
                <router-link
                    :to="`/admin/organizers/${organizer.id}`"
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
                    class="bg-card-bg border border-surface-elevated rounded-t-xl p-6 grid grid-cols-1 lg:grid-cols-3 gap-6"
                >
                    <div>
                        <h3 class="text-white font-semibold mb-1">
                            Informações Básicas
                        </h3>
                        <p class="text-text-muted text-sm">
                            Dados principais para identificação do organizador
                            na plataforma.
                        </p>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <!-- Name -->
                        <div>
                            <label
                                for="name"
                                class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                            >
                                Nome do Organizador / Empresa *
                            </label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                placeholder="Ex: Marathon Events Ltda"
                                class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                :class="{ 'border-red-500': errors.name }"
                            />
                            <p
                                v-if="errors.name"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.name }}
                            </p>
                            <p v-else class="text-text-muted text-xs mt-1">
                                Este é o nome que aparecerá publicamente nos
                                eventos.
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label
                                for="email"
                                class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                            >
                                E-mail de Contato *
                            </label>
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="contato@organizador.com.br"
                                class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                :class="{ 'border-red-500': errors.email }"
                            />
                            <p
                                v-if="errors.email"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.email }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Documentação e Contato -->
                <div
                    class="bg-card-bg border-x border-surface-elevated p-6 grid grid-cols-1 lg:grid-cols-3 gap-6"
                >
                    <div>
                        <h3 class="text-white font-semibold mb-1">
                            Documentação e Contato
                        </h3>
                        <p class="text-text-muted text-sm">
                            Informações fiscais e meios de comunicação direta.
                        </p>
                    </div>

                    <div
                        class="lg:col-span-2 grid grid-cols-1 md:grid-cols-2 gap-4"
                    >
                        <!-- Document (CNPJ) -->
                        <div>
                            <label
                                for="document"
                                class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                            >
                                Documento / CNPJ *
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                                >
                                    description
                                </span>
                                <input
                                    id="document"
                                    v-model="form.document"
                                    type="text"
                                    placeholder="00.000.000/0001-00"
                                    maxlength="18"
                                    @input="formatCNPJ"
                                    class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                    :class="{
                                        'border-red-500': errors.document,
                                    }"
                                />
                            </div>
                            <p
                                v-if="errors.document"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.document }}
                            </p>
                        </div>

                        <!-- Phone -->
                        <div>
                            <label
                                for="phone"
                                class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                            >
                                Telefone *
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                                >
                                    call
                                </span>
                                <input
                                    id="phone"
                                    v-model="form.phone"
                                    type="text"
                                    placeholder="(11) 99000-9999"
                                    maxlength="15"
                                    @input="formatPhone"
                                    class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                    :class="{ 'border-red-500': errors.phone }"
                                />
                            </div>
                            <p
                                v-if="errors.phone"
                                class="text-red-500 text-sm mt-1"
                            >
                                {{ errors.phone }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div
                    class="bg-card-bg border-x border-surface-elevated p-6 grid grid-cols-1 lg:grid-cols-3 gap-6"
                >
                    <div>
                        <h3 class="text-white font-semibold mb-1">Endereço</h3>
                        <p class="text-text-muted text-sm">
                            Localização principal da sede administrativa ou do
                            organizador.
                        </p>
                    </div>

                    <div class="lg:col-span-2 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- CEP -->
                            <div>
                                <label
                                    for="zip_code"
                                    class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                                >
                                    CEP *
                                </label>
                                <div class="relative">
                                    <input
                                        id="zip_code"
                                        v-model="form.zip_code"
                                        type="text"
                                        placeholder="00000-000"
                                        maxlength="9"
                                        @input="formatCEP"
                                        @blur="fetchAddressByCEP"
                                        class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                        :class="{
                                            'border-red-500': errors.zip_code,
                                        }"
                                    />
                                    <span
                                        v-if="isFetchingCEP"
                                        class="absolute right-3 top-1/2 -translate-y-1/2"
                                    >
                                        <span
                                            class="material-symbols-outlined text-primary animate-spin text-[20px]"
                                            >progress_activity</span
                                        >
                                    </span>
                                </div>
                                <p
                                    v-if="errors.zip_code"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.zip_code }}
                                </p>
                            </div>

                            <!-- Logradouro -->
                            <div>
                                <label
                                    for="address"
                                    class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                                >
                                    Logradouro *
                                </label>
                                <input
                                    id="address"
                                    v-model="form.address"
                                    type="text"
                                    placeholder="Rua, Avenida, etc."
                                    class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                    :class="{
                                        'border-red-500': errors.address,
                                    }"
                                />
                                <p
                                    v-if="errors.address"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.address }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Complemento -->
                            <div>
                                <label
                                    for="address_complement"
                                    class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                                >
                                    Complemento
                                </label>
                                <input
                                    id="address_complement"
                                    v-model="form.address_complement"
                                    type="text"
                                    placeholder="Apto, Sala, Bloco (Opcional)"
                                    class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                />
                            </div>

                            <!-- Bairro -->
                            <div>
                                <label
                                    for="neighborhood"
                                    class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                                >
                                    Bairro *
                                </label>
                                <input
                                    id="neighborhood"
                                    v-model="form.neighborhood"
                                    type="text"
                                    placeholder="Seu bairro"
                                    class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                    :class="{
                                        'border-red-500': errors.neighborhood,
                                    }"
                                />
                                <p
                                    v-if="errors.neighborhood"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.neighborhood }}
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Cidade -->
                            <div>
                                <label
                                    for="city"
                                    class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                                >
                                    Cidade *
                                </label>
                                <input
                                    id="city"
                                    v-model="form.city"
                                    type="text"
                                    placeholder="Nome da cidade"
                                    class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                                    :class="{ 'border-red-500': errors.city }"
                                />
                                <p
                                    v-if="errors.city"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.city }}
                                </p>
                            </div>

                            <!-- Estado -->
                            <div>
                                <label
                                    for="state"
                                    class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                                >
                                    Estado *
                                </label>
                                <select
                                    id="state"
                                    v-model="form.state"
                                    class="w-full bg-surface border border-surface-elevated rounded-lg px-4 py-3 text-white focus:outline-none focus:border-primary transition-colors appearance-none cursor-pointer"
                                    :class="{
                                        'border-red-500': errors.state,
                                        'text-text-muted': !form.state,
                                    }"
                                >
                                    <option value="" disabled>UF</option>
                                    <option
                                        v-for="state in states"
                                        :key="state.uf"
                                        :value="state.uf"
                                    >
                                        {{ state.uf }}
                                    </option>
                                </select>
                                <p
                                    v-if="errors.state"
                                    class="text-red-500 text-sm mt-1"
                                >
                                    {{ errors.state }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status da Conta -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-b-xl p-6 grid grid-cols-1 lg:grid-cols-3 gap-6"
                >
                    <div>
                        <h3 class="text-white font-semibold mb-1">
                            Status da Conta
                        </h3>
                        <p class="text-text-muted text-sm">
                            Controle de acesso e visibilidade do organizador.
                        </p>
                    </div>

                    <div class="lg:col-span-2">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-white font-medium">
                                    Organizador Ativo
                                </p>
                                <p class="text-text-muted text-sm">
                                    Ao ativar, o organizador poderá acessar o
                                    painel e criar novos eventos.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="
                                    form.status =
                                        form.status === 'active'
                                            ? 'inactive'
                                            : 'active'
                                "
                                :class="[
                                    'relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none',
                                    form.status === 'active'
                                        ? 'bg-primary'
                                        : 'bg-surface-elevated',
                                ]"
                            >
                                <span
                                    :class="[
                                        'inline-block h-4 w-4 transform rounded-full bg-white transition-transform',
                                        form.status === 'active'
                                            ? 'translate-x-6'
                                            : 'translate-x-1',
                                    ]"
                                />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-4 pt-6">
                    <router-link
                        :to="`/admin/organizers/${organizer.id}`"
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
                        <span
                            v-else
                            class="material-symbols-outlined text-[20px]"
                            >save</span
                        >
                        {{ isSubmitting ? "Salvando..." : "Salvar Alterações" }}
                    </button>
                </div>
            </form>
        </template>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useOrganizersStore } from "@/stores/organizers";
import { useToast } from "@/composables/useToast";
import ErrorState from "@/components/ui/ErrorState.vue";

const route = useRoute();
const router = useRouter();
const store = useOrganizersStore();
const toast = useToast();

// State
const isLoading = ref(true);
const loadError = ref(null);
const isSubmitting = ref(false);
const isFetchingCEP = ref(false);
const organizer = ref(null);

const form = reactive({
    name: "",
    document: "",
    email: "",
    phone: "",
    status: "active",
    address: "",
    address_complement: "",
    neighborhood: "",
    city: "",
    state: "",
    zip_code: "",
});

const errors = reactive({
    name: "",
    document: "",
    email: "",
    phone: "",
    address: "",
    neighborhood: "",
    city: "",
    state: "",
    zip_code: "",
});

const states = [
    { uf: "AC", name: "Acre" },
    { uf: "AL", name: "Alagoas" },
    { uf: "AP", name: "Amapá" },
    { uf: "AM", name: "Amazonas" },
    { uf: "BA", name: "Bahia" },
    { uf: "CE", name: "Ceará" },
    { uf: "DF", name: "Distrito Federal" },
    { uf: "ES", name: "Espírito Santo" },
    { uf: "GO", name: "Goiás" },
    { uf: "MA", name: "Maranhão" },
    { uf: "MT", name: "Mato Grosso" },
    { uf: "MS", name: "Mato Grosso do Sul" },
    { uf: "MG", name: "Minas Gerais" },
    { uf: "PA", name: "Pará" },
    { uf: "PB", name: "Paraíba" },
    { uf: "PR", name: "Paraná" },
    { uf: "PE", name: "Pernambuco" },
    { uf: "PI", name: "Piauí" },
    { uf: "RJ", name: "Rio de Janeiro" },
    { uf: "RN", name: "Rio Grande do Norte" },
    { uf: "RS", name: "Rio Grande do Sul" },
    { uf: "RO", name: "Rondônia" },
    { uf: "RR", name: "Roraima" },
    { uf: "SC", name: "Santa Catarina" },
    { uf: "SP", name: "São Paulo" },
    { uf: "SE", name: "Sergipe" },
    { uf: "TO", name: "Tocantins" },
];

// Fetch organizer data
const fetchOrganizer = async () => {
    isLoading.value = true;
    loadError.value = null;

    try {
        const result = await store.fetchOrganizer(route.params.id);
        if (result.success) {
            organizer.value = result.data;
            populateForm(result.data);
        } else {
            loadError.value = result.error;
        }
    } catch (err) {
        loadError.value = "Erro ao carregar organizador";
    } finally {
        isLoading.value = false;
    }
};

const populateForm = (data) => {
    form.name = data.name || "";
    form.document = formatDocumentValue(data.document) || "";
    form.email = data.email || "";
    form.phone = formatPhoneValue(data.phone) || "";
    form.status = data.status || "active";
    form.address = data.address || "";
    form.address_complement = data.address_complement || "";
    form.neighborhood = data.neighborhood || "";
    form.city = data.city || "";
    form.state = data.state || "";
    form.zip_code = formatCEPValue(data.zip_code) || "";
};

// Format functions for display
const formatDocumentValue = (doc) => {
    if (!doc) return "";
    const cleaned = doc.replace(/\D/g, "");
    if (cleaned.length !== 14) return doc;
    return cleaned.replace(
        /^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
        "$1.$2.$3/$4-$5",
    );
};

const formatPhoneValue = (phone) => {
    if (!phone) return "";
    const cleaned = phone.replace(/\D/g, "");
    if (cleaned.length === 11) {
        return cleaned.replace(/^(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
    }
    if (cleaned.length === 10) {
        return cleaned.replace(/^(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
    }
    return phone;
};

const formatCEPValue = (zip) => {
    if (!zip) return "";
    const cleaned = zip.replace(/\D/g, "");
    if (cleaned.length === 8) {
        return cleaned.replace(/^(\d{5})(\d{3})/, "$1-$2");
    }
    return zip;
};

// Format functions for input
const formatCNPJ = (e) => {
    let value = e.target.value.replace(/\D/g, "");
    if (value.length > 14) value = value.slice(0, 14);

    value = value.replace(/^(\d{2})(\d)/, "$1.$2");
    value = value.replace(/^(\d{2})\.(\d{3})(\d)/, "$1.$2.$3");
    value = value.replace(/\.(\d{3})(\d)/, ".$1/$2");
    value = value.replace(/(\d{4})(\d)/, "$1-$2");

    form.document = value;
};

const formatPhone = (e) => {
    let value = e.target.value.replace(/\D/g, "");
    if (value.length > 11) value = value.slice(0, 11);

    if (value.length > 10) {
        value = value.replace(/^(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
    } else if (value.length > 6) {
        value = value.replace(/^(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
    } else if (value.length > 2) {
        value = value.replace(/^(\d{2})(\d{0,5})/, "($1) $2");
    }

    form.phone = value;
};

const formatCEP = (e) => {
    let value = e.target.value.replace(/\D/g, "");
    if (value.length > 8) value = value.slice(0, 8);

    if (value.length > 5) {
        value = value.replace(/^(\d{5})(\d)/, "$1-$2");
    }

    form.zip_code = value;
};

// CEP lookup
const fetchAddressByCEP = async () => {
    const cep = form.zip_code.replace(/\D/g, "");
    if (cep.length !== 8) return;

    isFetchingCEP.value = true;

    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();

        if (!data.erro) {
            form.address = data.logradouro || form.address;
            form.neighborhood = data.bairro || form.neighborhood;
            form.city = data.localidade || form.city;
            form.state = data.uf || form.state;
        }
    } catch (err) {
        console.error("Erro ao buscar CEP:", err);
    } finally {
        isFetchingCEP.value = false;
    }
};

// Validation
const validate = () => {
    let isValid = true;

    // Reset errors
    Object.keys(errors).forEach((key) => (errors[key] = ""));

    if (!form.name.trim()) {
        errors.name = "Nome é obrigatório";
        isValid = false;
    }

    if (!form.document.trim()) {
        errors.document = "CNPJ é obrigatório";
        isValid = false;
    } else if (form.document.replace(/\D/g, "").length !== 14) {
        errors.document = "CNPJ deve ter 14 dígitos";
        isValid = false;
    }

    if (!form.email.trim()) {
        errors.email = "E-mail é obrigatório";
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) {
        errors.email = "E-mail inválido";
        isValid = false;
    }

    if (!form.phone.trim()) {
        errors.phone = "Telefone é obrigatório";
        isValid = false;
    } else if (form.phone.replace(/\D/g, "").length < 10) {
        errors.phone = "Telefone inválido";
        isValid = false;
    }

    if (!form.address.trim()) {
        errors.address = "Endereço é obrigatório";
        isValid = false;
    }

    if (!form.neighborhood.trim()) {
        errors.neighborhood = "Bairro é obrigatório";
        isValid = false;
    }

    if (!form.city.trim()) {
        errors.city = "Cidade é obrigatória";
        isValid = false;
    }

    if (!form.state) {
        errors.state = "Estado é obrigatório";
        isValid = false;
    }

    if (!form.zip_code.trim()) {
        errors.zip_code = "CEP é obrigatório";
        isValid = false;
    } else if (form.zip_code.replace(/\D/g, "").length !== 8) {
        errors.zip_code = "CEP deve ter 8 dígitos";
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
        const result = await store.updateOrganizer(organizer.value.id, {
            name: form.name,
            document: form.document,
            email: form.email,
            phone: form.phone,
            status: form.status,
            address: form.address,
            address_complement: form.address_complement || null,
            neighborhood: form.neighborhood,
            city: form.city,
            state: form.state,
            zip_code: form.zip_code,
        });

        if (result.success) {
            toast.success("Organizador atualizado com sucesso!");
            router.push(`/admin/organizers/${organizer.value.id}`);
        } else {
            // Handle validation errors from API
            if (result.errors) {
                Object.keys(result.errors).forEach((key) => {
                    if (errors[key] !== undefined) {
                        errors[key] = result.errors[key][0];
                    }
                });
            }
            toast.error(result.error || "Erro ao atualizar organizador");
        }
    } catch (err) {
        toast.error("Erro ao atualizar organizador");
    } finally {
        isSubmitting.value = false;
    }
};

// Lifecycle
onMounted(() => {
    fetchOrganizer();
});
</script>

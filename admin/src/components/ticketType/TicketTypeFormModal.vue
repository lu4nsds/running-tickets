<template>
    <Modal
        :model-value="modelValue"
        @update:model-value="$emit('update:modelValue', $event)"
        :title="
            isEditMode ? 'Editar Tipo de Ingresso' : 'Criar Tipo de Ingresso'
        "
        :subtitle="
            isEditMode
                ? 'Atualize as informações do tipo de ingresso.'
                : 'Configure as especificações de um novo tipo de entrada.'
        "
        :close-on-overlay="!isSubmitting"
    >
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Nome do Ingresso -->
            <div>
                <label
                    for="ticket-name"
                    class="block text-sm font-medium text-white mb-2"
                >
                    Nome do Ingresso
                    <span class="text-red-500 ml-1">*</span>
                </label>
                <div class="relative">
                    <span
                        class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                    >
                        confirmation_number
                    </span>
                    <input
                        id="ticket-name"
                        v-model="form.name"
                        type="text"
                        placeholder="Ex: Lote Promocional"
                        class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors"
                        :disabled="isSubmitting"
                    />
                </div>
                <p v-if="errors.name" class="mt-1 text-sm text-red-500">
                    {{ errors.name[0] }}
                </p>
            </div>

            <!-- Preço e Limite de Vendas (lado a lado) -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Preço -->
                <div>
                    <label
                        for="ticket-price"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Preço
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            payments
                        </span>
                        <input
                            id="ticket-price"
                            v-model="priceInReais"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Ex: 50.00"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p
                        v-if="errors.price_cents"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ errors.price_cents[0] }}
                    </p>
                </div>

                <!-- Limite de Vendas (Cota) -->
                <div>
                    <label
                        for="ticket-quota"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Limite de Vendas
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            inventory
                        </span>
                        <input
                            id="ticket-quota"
                            v-model.number="form.quota"
                            type="number"
                            min="1"
                            placeholder="Ex: 100"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p v-if="errors.quota" class="mt-1 text-sm text-red-500">
                        {{ errors.quota[0] }}
                    </p>
                </div>
            </div>

            <!-- Data de Início e Fim das Vendas (lado a lado) -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Data Início -->
                <div>
                    <label
                        for="ticket-start-sale"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Início das Vendas
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            event
                        </span>
                        <input
                            id="ticket-start-sale"
                            v-model="form.start_sale"
                            type="datetime-local"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white rounded-lg focus:outline-none focus:border-primary transition-colors"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p
                        v-if="errors.start_sale"
                        class="mt-1 text-sm text-red-500"
                    >
                        {{ errors.start_sale[0] }}
                    </p>
                </div>

                <!-- Data Fim -->
                <div>
                    <label
                        for="ticket-end-sale"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Fim das Vendas
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            event_busy
                        </span>
                        <input
                            id="ticket-end-sale"
                            v-model="form.end_sale"
                            type="datetime-local"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white rounded-lg focus:outline-none focus:border-primary transition-colors"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p v-if="errors.end_sale" class="mt-1 text-sm text-red-500">
                        {{ errors.end_sale[0] }}
                    </p>
                </div>
            </div>

            <!-- Descrição -->
            <div>
                <label
                    for="ticket-description"
                    class="block text-sm font-medium text-white mb-2"
                >
                    Descrição
                </label>
                <textarea
                    id="ticket-description"
                    v-model="form.description"
                    rows="3"
                    placeholder="Detalhes do ingresso, regras específicas ou benefícios..."
                    class="w-full px-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors resize-none"
                    :disabled="isSubmitting"
                ></textarea>
                <p v-if="errors.description" class="mt-1 text-sm text-red-500">
                    {{ errors.description[0] }}
                </p>
            </div>

            <!-- Status -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="material-symbols-outlined text-[20px] text-primary"
                    >
                        toggle_on
                    </span>
                    <div>
                        <p class="text-sm font-medium text-white">Status</p>
                        <p class="text-xs text-text-muted">
                            Apenas se este tipo de ingresso está disponível para
                            venda.
                        </p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input
                        v-model="form.active"
                        type="checkbox"
                        class="sr-only peer"
                        :disabled="isSubmitting"
                    />
                    <div
                        class="w-11 h-6 bg-surface-elevated rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-text-muted after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary peer-checked:after:bg-background"
                    ></div>
                </label>
            </div>
        </form>

        <template #footer>
            <div class="flex items-center justify-end gap-3">
                <button
                    type="button"
                    @click="$emit('update:modelValue', false)"
                    class="px-4 py-2 text-text-muted hover:text-white transition-colors"
                    :disabled="isSubmitting"
                >
                    Cancelar
                </button>
                <button
                    @click="handleSubmit"
                    :disabled="isSubmitting"
                    class="px-6 py-2 bg-primary text-background font-semibold rounded-lg hover:bg-primary/90 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <span
                        v-if="isSubmitting"
                        class="material-symbols-outlined text-[20px] animate-spin"
                    >
                        progress_activity
                    </span>
                    <span v-else class="material-symbols-outlined text-[20px]">
                        {{ isEditMode ? "save" : "add" }}
                    </span>
                    {{ isEditMode ? "Salvar Ingresso" : "Salvar Ingresso" }}
                </button>
            </div>
        </template>
    </Modal>
</template>

<script setup>
import { ref, watch, computed } from "vue";
import Modal from "@/components/ui/Modal.vue";
import axios from "@/api/axios";
import { useToast } from "@/composables/useToast";

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    eventId: {
        type: Number,
        required: true,
    },
    ticketType: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const { showToast } = useToast();

const isEditMode = computed(() => !!props.ticketType);

const form = ref({
    name: "",
    description: "",
    price_cents: 0,
    quota: null,
    start_sale: "",
    end_sale: "",
    active: true,
});

const priceInReais = ref("");

const errors = ref({});
const isSubmitting = ref(false);

// Converter centavos para reais (display)
const centsToReais = (cents) => {
    return (cents / 100).toFixed(2);
};

// Converter reais para centavos (submit)
const reaisToCents = (reais) => {
    return Math.round(parseFloat(reais || 0) * 100);
};

const toUTCString = (localStr) => {
    if (!localStr) return undefined;
    return new Date(localStr).toISOString();
};

// Formatar datetime para datetime-local input
const formatDatetimeLocal = (datetime) => {
    if (!datetime) return "";
    const date = new Date(datetime);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    const hours = String(date.getHours()).padStart(2, "0");
    const minutes = String(date.getMinutes()).padStart(2, "0");
    return `${year}-${month}-${day}T${hours}:${minutes}`;
};

// Reset form helper (definido antes dos watches)
const resetForm = () => {
    form.value = {
        name: "",
        description: "",
        price_cents: 0,
        quota: null,
        start_sale: "",
        end_sale: "",
        active: true,
    };
    priceInReais.value = "";
};

// Preencher form quando ticketType mudar (modo edição)
watch(
    () => props.ticketType,
    (newTicketType) => {
        if (newTicketType) {
            form.value = {
                name: newTicketType.name || "",
                description: newTicketType.description || "",
                price_cents: newTicketType.price_cents || 0,
                quota: newTicketType.quota || null,
                start_sale: newTicketType.start_sale
                    ? formatDatetimeLocal(newTicketType.start_sale)
                    : "",
                end_sale: newTicketType.end_sale
                    ? formatDatetimeLocal(newTicketType.end_sale)
                    : "",
                active: newTicketType.active ?? true,
            };
            priceInReais.value = centsToReais(newTicketType.price_cents || 0);
        } else {
            resetForm();
        }
    },
    { immediate: true },
);

// Reset form quando modal fechar
watch(
    () => props.modelValue,
    (isOpen) => {
        if (!isOpen && !props.ticketType) {
            resetForm();
        }
        if (!isOpen) {
            errors.value = {};
        }
    },
);

const handleSubmit = async () => {
    errors.value = {};
    isSubmitting.value = true;

    try {
        const payload = {
            name: form.value.name,
            description: form.value.description || undefined,
            price_cents: reaisToCents(priceInReais.value),
            quota: form.value.quota || undefined,
            start_sale: toUTCString(form.value.start_sale),
            end_sale: toUTCString(form.value.end_sale),
            active: form.value.active,
        };

        if (isEditMode.value) {
            // Update
            await axios.put(
                `/admin/events/${props.eventId}/ticket-types/${props.ticketType.id}`,
                payload,
            );
            showToast("Tipo de ingresso atualizado com sucesso!", "success");
        } else {
            // Create
            await axios.post(
                `/admin/events/${props.eventId}/ticket-types`,
                payload,
            );
            showToast("Tipo de ingresso criado com sucesso!", "success");
        }

        emit("saved");
        emit("update:modelValue", false);
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            showToast(
                error.response?.data?.message ||
                    "Erro ao salvar tipo de ingresso. Tente novamente.",
                "error",
            );
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

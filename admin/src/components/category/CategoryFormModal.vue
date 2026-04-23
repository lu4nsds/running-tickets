<template>
    <Modal
        :model-value="modelValue"
        @update:model-value="$emit('update:modelValue', $event)"
        :title="isEditMode ? 'Editar Categoria' : 'Adicionar Nova Categoria'"
        :subtitle="
            isEditMode
                ? 'Atualize as informações da categoria.'
                : 'Configure as especificações de uma nova categoria para este evento.'
        "
        :close-on-overlay="!isSubmitting"
    >
        <form @submit.prevent="handleSubmit" class="space-y-6">
            <!-- Nome da Categoria -->
            <div>
                <label
                    for="category-name"
                    class="block text-sm font-medium text-white mb-2"
                >
                    Nome da Categoria
                    <span class="text-red-500 ml-1">*</span>
                </label>
                <div class="relative">
                    <span
                        class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                    >
                        label
                    </span>
                    <input
                        id="category-name"
                        v-model="form.name"
                        type="text"
                        placeholder="Ex: Masculino 5K"
                        class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors"
                        :disabled="isSubmitting"
                    />
                </div>
                <p v-if="errors.name" class="mt-1 text-sm text-red-500">
                    {{ errors.name[0] }}
                </p>
            </div>

            <!-- Distance e Gender (lado a lado) -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Distância -->
                <div>
                    <label
                        for="category-distance"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Distância (km)
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            straighten
                        </span>
                        <input
                            id="category-distance"
                            v-model.number="form.distance"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Ex: 5, 10, 21"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p v-if="errors.distance" class="mt-1 text-sm text-red-500">
                        {{ errors.distance[0] }}
                    </p>
                </div>

                <!-- Gênero -->
                <div>
                    <label
                        for="category-gender"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Gênero
                        <span class="text-red-500 ml-1">*</span>
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            wc
                        </span>
                        <select
                            id="category-gender"
                            v-model="form.gender"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white rounded-lg focus:outline-none focus:border-primary transition-colors appearance-none"
                            :disabled="isSubmitting"
                        >
                            <option value="" disabled>Selecione...</option>
                            <option value="M">Masculino</option>
                            <option value="F">Feminino</option>
                            <option value="X">Misto</option>
                        </select>
                        <span
                            class="absolute right-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted pointer-events-none"
                        >
                            expand_more
                        </span>
                    </div>
                    <p v-if="errors.gender" class="mt-1 text-sm text-red-500">
                        {{ errors.gender[0] }}
                    </p>
                </div>
            </div>

            <!-- Faixa Etária (lado a lado) -->
            <div class="grid grid-cols-2 gap-4">
                <!-- Idade Mínima -->
                <div>
                    <label
                        for="category-min-age"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Idade Mínima
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            person
                        </span>
                        <input
                            id="category-min-age"
                            v-model.number="form.min_age"
                            type="number"
                            min="0"
                            placeholder="Ex: 18"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p v-if="errors.min_age" class="mt-1 text-sm text-red-500">
                        {{ errors.min_age[0] }}
                    </p>
                </div>

                <!-- Idade Máxima -->
                <div>
                    <label
                        for="category-max-age"
                        class="block text-sm font-medium text-white mb-2"
                    >
                        Idade Máxima
                    </label>
                    <div class="relative">
                        <span
                            class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-[20px] text-text-muted"
                        >
                            person
                        </span>
                        <input
                            id="category-max-age"
                            v-model.number="form.max_age"
                            type="number"
                            min="0"
                            placeholder="Ex: 65"
                            class="w-full pl-11 pr-4 py-2.5 bg-surface-elevated border border-surface-elevated text-white placeholder-text-muted rounded-lg focus:outline-none focus:border-primary transition-colors"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p v-if="errors.max_age" class="mt-1 text-sm text-red-500">
                        {{ errors.max_age[0] }}
                    </p>
                </div>
            </div>

            <!-- Descrição -->
            <div>
                <label
                    for="category-description"
                    class="block text-sm font-medium text-white mb-2"
                >
                    Descrição
                </label>
                <textarea
                    id="category-description"
                    v-model="form.description"
                    rows="3"
                    placeholder="Detalhes da categoria, regras específicas ou benefícios..."
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
                    {{ isEditMode ? "Salvar" : "Adicionar Categoria" }}
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
    category: {
        type: Object,
        default: null,
    },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const { showToast } = useToast();

const isEditMode = computed(() => !!props.category);

const form = ref({
    name: "",
    distance: null,
    gender: "",
    min_age: null,
    max_age: null,
    description: "",
    active: true,
});

const errors = ref({});
const isSubmitting = ref(false);

// Reset form helper (definido antes dos watches)
const resetForm = () => {
    form.value = {
        name: "",
        distance: null,
        gender: "",
        min_age: null,
        max_age: null,
        description: "",
        active: true,
    };
};

// Preencher form quando category mudar (modo edição)
watch(
    () => props.category,
    (newCategory) => {
        if (newCategory) {
            form.value = {
                name: newCategory.name || "",
                distance: newCategory.distance || null,
                gender: newCategory.gender || "",
                min_age: newCategory.min_age || null,
                max_age: newCategory.max_age || null,
                description: newCategory.description || "",
                active: newCategory.active ?? true,
            };
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
        if (!isOpen && !props.category) {
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
            ...form.value,
            // Limpar valores null/empty para não enviar ao backend
            min_age: form.value.min_age || undefined,
            max_age: form.value.max_age || undefined,
            description: form.value.description || undefined,
        };

        if (isEditMode.value) {
            // Update
            await axios.put(
                `/admin/events/${props.eventId}/categories/${props.category.id}`,
                payload,
            );
            showToast("Categoria atualizada com sucesso!", "success");
        } else {
            // Create
            await axios.post(
                `/admin/events/${props.eventId}/categories`,
                payload,
            );
            showToast("Categoria criada com sucesso!", "success");
        }

        emit("saved");
        emit("update:modelValue", false);
    } catch (error) {
        if (error.response?.status === 422) {
            errors.value = error.response.data.errors || {};
        } else {
            showToast(
                error.response?.data?.message ||
                    "Erro ao salvar categoria. Tente novamente.",
                "error",
            );
        }
    } finally {
        isSubmitting.value = false;
    }
};
</script>

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
            <router-link
                :to="`/admin/events/${eventId}`"
                class="text-primary hover:underline"
            >
                {{ eventName }}
            </router-link>
            <span class="text-text-muted">/</span>
            <span class="text-text-muted">{{
                isEditMode ? "Editar Categoria" : "Nova Categoria"
            }}</span>
        </nav>

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-white mb-1">
                    {{
                        isEditMode ? "Editar Categoria" : "Criar Nova Categoria"
                    }}
                </h1>
                <p class="text-text-muted">
                    Configure as especificações de
                    {{ isEditMode ? "uma" : "uma nova" }}
                    categoria para este evento.
                </p>
            </div>
            <button
                @click="goBack"
                class="flex items-center gap-2 px-4 py-2 border border-surface-elevated text-text-muted rounded-lg hover:bg-surface hover:text-white transition-colors"
            >
                <span class="material-symbols-outlined text-[20px]"
                    >arrow_back</span
                >
                Voltar
            </button>
        </div>

        <!-- Form Card -->
        <div
            class="max-w-[800px] mx-auto bg-card-bg border border-surface-elevated rounded-xl overflow-hidden relative"
        >
            <!-- Top gradient line -->
            <div
                class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-transparent via-primary/50 to-transparent"
            ></div>

            <form @submit.prevent="handleSubmit" class="p-8 space-y-6">
                <!-- Nome da Categoria -->
                <div class="space-y-2">
                    <label
                        for="name"
                        class="block text-sm font-medium text-text-muted"
                    >
                        Nome da Categoria
                        <span class="text-primary ml-1">*</span>
                    </label>
                    <div class="relative">
                        <div
                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                        >
                            <span
                                class="material-symbols-outlined text-text-muted"
                                >label</span
                            >
                        </div>
                        <input
                            id="name"
                            v-model="form.name"
                            type="text"
                            placeholder="Ex: Masculino 5K"
                            class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                            :class="{ 'border-red-500': errors.name }"
                            :disabled="isSubmitting"
                        />
                    </div>
                    <p v-if="errors.name" class="text-xs text-red-500">
                        {{ errors.name[0] }}
                    </p>
                </div>

                <!-- Distância e Gênero -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Distância -->
                    <div class="space-y-2">
                        <label
                            for="distance"
                            class="block text-sm font-medium text-text-muted"
                        >
                            Distância (km)
                            <span class="text-primary ml-1">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-text-muted"
                                    >straighten</span
                                >
                            </div>
                            <input
                                id="distance"
                                v-model.number="form.distance"
                                type="number"
                                step="0.01"
                                min="0"
                                placeholder="Ex: 5, 10, 21"
                                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                                :class="{ 'border-red-500': errors.distance }"
                                :disabled="isSubmitting"
                            />
                        </div>
                        <p v-if="errors.distance" class="text-xs text-red-500">
                            {{ errors.distance[0] }}
                        </p>
                    </div>

                    <!-- Gênero -->
                    <div class="space-y-2">
                        <label
                            for="gender"
                            class="block text-sm font-medium text-text-muted"
                        >
                            Gênero
                            <span class="text-primary ml-1">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-text-muted"
                                    >wc</span
                                >
                            </div>
                            <select
                                id="gender"
                                v-model="form.gender"
                                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white focus:outline-none focus:border-primary transition-all appearance-none"
                                :class="{ 'border-red-500': errors.gender }"
                                :disabled="isSubmitting"
                            >
                                <option value="" disabled>Selecione...</option>
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                                <option value="X">Misto</option>
                            </select>
                            <div
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-text-muted"
                                    >expand_more</span
                                >
                            </div>
                        </div>
                        <p v-if="errors.gender" class="text-xs text-red-500">
                            {{ errors.gender[0] }}
                        </p>
                    </div>
                </div>

                <!-- Faixa Etária -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Idade Mínima -->
                    <div class="space-y-2">
                        <label
                            for="min_age"
                            class="block text-sm font-medium text-text-muted"
                        >
                            Idade Mínima
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-text-muted"
                                    >person</span
                                >
                            </div>
                            <input
                                id="min_age"
                                v-model.number="form.min_age"
                                type="number"
                                min="0"
                                placeholder="Ex: 18"
                                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                                :class="{ 'border-red-500': errors.min_age }"
                                :disabled="isSubmitting"
                            />
                        </div>
                        <p v-if="errors.min_age" class="text-xs text-red-500">
                            {{ errors.min_age[0] }}
                        </p>
                    </div>

                    <!-- Idade Máxima -->
                    <div class="space-y-2">
                        <label
                            for="max_age"
                            class="block text-sm font-medium text-text-muted"
                        >
                            Idade Máxima
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
                            >
                                <span
                                    class="material-symbols-outlined text-text-muted"
                                    >person</span
                                >
                            </div>
                            <input
                                id="max_age"
                                v-model.number="form.max_age"
                                type="number"
                                min="0"
                                placeholder="Ex: 65"
                                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                                :class="{ 'border-red-500': errors.max_age }"
                                :disabled="isSubmitting"
                            />
                        </div>
                        <p v-if="errors.max_age" class="text-xs text-red-500">
                            {{ errors.max_age[0] }}
                        </p>
                    </div>
                </div>

                <!-- Descrição -->
                <div class="space-y-2">
                    <label
                        for="description"
                        class="block text-sm font-medium text-text-muted"
                    >
                        Descrição
                    </label>
                    <textarea
                        id="description"
                        v-model="form.description"
                        rows="4"
                        placeholder="Detalhes da categoria, regras específicas ou benefícios..."
                        class="block w-full px-4 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all resize-none"
                        :class="{ 'border-red-500': errors.description }"
                        :disabled="isSubmitting"
                    ></textarea>
                    <p v-if="errors.description" class="text-xs text-red-500">
                        {{ errors.description[0] }}
                    </p>
                </div>

                <!-- Status -->
                <div
                    class="flex items-center justify-between p-4 bg-surface/50 rounded-lg border border-surface-elevated"
                >
                    <div class="flex items-center gap-3">
                        <span class="material-symbols-outlined text-primary">
                            toggle_on
                        </span>
                        <div>
                            <p class="text-sm font-bold text-white">Status</p>
                            <p class="text-xs text-text-muted">
                                Defina se esta categoria está ativa.
                            </p>
                        </div>
                    </div>
                    <label
                        class="relative inline-flex items-center cursor-pointer"
                    >
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

            <!-- Footer -->
            <div
                class="px-8 py-6 bg-surface/30 border-t border-surface-elevated flex justify-end items-center gap-4"
            >
                <button
                    @click="goBack"
                    type="button"
                    class="px-6 py-2.5 text-sm font-medium text-text-muted hover:text-white transition-colors"
                    :disabled="isSubmitting"
                >
                    Cancelar
                </button>
                <button
                    @click="handleSubmit"
                    type="button"
                    :disabled="isSubmitting"
                    class="px-6 py-2.5 bg-primary text-black first-letter:text-background font-bold rounded-lg hover:bg-opacity-90 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-[0_0_20px_rgba(0,230,118,0.4)] hover:shadow-[0_0_30px_rgba(0,230,118,0.6)]"
                >
                    <span
                        v-if="isSubmitting"
                        class="material-symbols-outlined text-lg animate-spin"
                        >progress_activity</span
                    >
                    <span
                        v-else
                        class="material-symbols-outlined font-bold text-lg"
                        >check_circle</span
                    >
                    {{
                        isSubmitting
                            ? "Salvando..."
                            : isEditMode
                              ? "Salvar Categoria"
                              : "Criar Categoria"
                    }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useToast } from "@/composables/useToast";
import axios from "@/api/axios";

const route = useRoute();
const router = useRouter();
const { showToast } = useToast();

const eventId = computed(() => route.params.eventId);
const categoryId = computed(() => route.params.categoryId);
const isEditMode = computed(() => !!categoryId.value);

const eventName = ref("Carregando...");
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

const goBack = () => {
    router.push(`/admin/events/${eventId.value}`);
};

const fetchEventName = async () => {
    try {
        const response = await axios.get(`/admin/events/${eventId.value}`);
        eventName.value = response.data.data.title;
    } catch (error) {
        console.error("Erro ao buscar evento:", error);
        eventName.value = "Evento";
    }
};

const fetchCategory = async () => {
    if (!isEditMode.value) return;

    try {
        const response = await axios.get(
            `/admin/events/${eventId.value}/categories/${categoryId.value}`,
        );
        const category = response.data.data;
        form.value = {
            name: category.name || "",
            distance: category.distance || null,
            gender: category.gender || "",
            min_age: category.min_age || null,
            max_age: category.max_age || null,
            description: category.description || "",
            active: category.active ?? true,
        };
    } catch (error) {
        showToast("Erro ao carregar categoria", "error");
        goBack();
    }
};

const handleSubmit = async () => {
    errors.value = {};
    isSubmitting.value = true;

    try {
        const payload = {
            name: form.value.name,
            distance: form.value.distance,
            gender: form.value.gender,
            min_age: form.value.min_age || undefined,
            max_age: form.value.max_age || undefined,
            description: form.value.description || undefined,
            active: form.value.active,
        };

        if (isEditMode.value) {
            await axios.put(
                `/admin/events/${eventId.value}/categories/${categoryId.value}`,
                payload,
            );
            showToast("Categoria atualizada com sucesso!", "success");
        } else {
            await axios.post(
                `/admin/events/${eventId.value}/categories`,
                payload,
            );
            showToast("Categoria criada com sucesso!", "success");
        }

        goBack();
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

onMounted(async () => {
    await fetchEventName();
    await fetchCategory();
});
</script>

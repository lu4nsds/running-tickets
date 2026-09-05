<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-white">Configurações</h1>
                <p class="text-text-secondary mt-1">
                    Ajustes globais que valem para toda a plataforma
                </p>
            </div>
        </div>

        <!-- Feedback Card -->
        <div class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden">
            <!-- Card Header -->
            <div class="flex items-center gap-4 px-6 py-5 border-b border-surface-elevated">
                <div
                    class="w-12 h-12 bg-surface rounded-xl flex items-center justify-center border border-surface-elevated"
                >
                    <span class="material-symbols-outlined text-primary text-2xl">reviews</span>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-white">Feedback do cliente</h2>
                    <p class="text-text-muted text-sm mt-0.5">
                        Formulário externo enviado ao comprador após o evento
                    </p>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-6">
                <div v-if="isLoading" class="flex items-center gap-3 text-text-muted">
                    <span class="material-symbols-outlined text-[20px] animate-spin">
                        progress_activity
                    </span>
                    Carregando configurações...
                </div>

                <form v-else class="space-y-6" @submit.prevent="handleSubmit">
                    <div
                        v-if="error"
                        class="flex items-start gap-3 p-4 bg-red-500/10 border border-red-500/20 rounded-xl"
                    >
                        <span class="material-symbols-outlined text-red-400 text-[20px] mt-0.5">
                            error
                        </span>
                        <p class="text-red-400 text-sm">{{ error }}</p>
                    </div>

                    <!-- Link do formulário de feedback -->
                    <div>
                        <label
                            for="feedback_form_url"
                            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
                        >
                            Link do formulário de feedback
                        </label>
                        <div class="relative">
                            <span
                                class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted text-[20px]"
                            >
                                link
                            </span>
                            <input
                                id="feedback_form_url"
                                v-model="form.feedback_form_url"
                                type="url"
                                placeholder="https://forms.gle/exemplo"
                                class="w-full bg-surface border border-surface-elevated rounded-lg pl-10 pr-4 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                            />
                        </div>
                        <p class="text-text-muted text-xs mt-1">
                            Enviado por e-mail e WhatsApp ao comprador 24 horas após a validação
                            do ingresso. Deixe em branco para não enviar nada.
                        </p>
                        <p v-if="errors.feedback_form_url" class="text-red-500 text-sm mt-1">
                            {{ errors.feedback_form_url }}
                        </p>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end pt-2">
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
                            <span v-else class="material-symbols-outlined text-[20px]">save</span>
                            {{ isSubmitting ? "Salvando..." : "Salvar Configurações" }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref, onMounted } from "vue";
import { settingsApi } from "@/api/admin/settings";
import { useToast } from "@/composables/useToast";

const toast = useToast();

const form = reactive({
    feedback_form_url: "",
});

const errors = reactive({
    feedback_form_url: "",
});

const isLoading = ref(true);
const isSubmitting = ref(false);
const error = ref(null);

async function loadSettings() {
    isLoading.value = true;
    error.value = null;

    try {
        const data = await settingsApi.get();
        form.feedback_form_url = data.feedback_form_url ?? "";
    } catch (err) {
        error.value =
            err.response?.data?.message ?? "Erro ao carregar as configurações.";
    } finally {
        isLoading.value = false;
    }
}

async function handleSubmit() {
    isSubmitting.value = true;
    error.value = null;
    errors.feedback_form_url = "";

    try {
        const data = await settingsApi.update({
            feedback_form_url: form.feedback_form_url,
        });
        form.feedback_form_url = data.feedback_form_url ?? "";
        toast.success("Configurações salvas com sucesso!");
    } catch (err) {
        const validation = err.response?.data?.errors;

        if (validation) {
            errors.feedback_form_url = validation.feedback_form_url?.[0] ?? "";
        }

        error.value =
            err.response?.data?.message ?? "Erro ao salvar as configurações.";
        toast.error(error.value);
    } finally {
        isSubmitting.value = false;
    }
}

onMounted(loadSettings);
</script>

<template>
  <div class="space-y-6">
    <!-- Breadcrumb -->
    <nav class="flex items-center gap-2 text-sm">
      <router-link to="/admin/events" class="text-primary hover:underline">
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
        isEditMode ? "Editar Tipo de Ingresso" : "Novo Tipo de Ingresso"
      }}</span>
    </nav>

    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-white mb-1">
          {{
            isEditMode ? "Editar Tipo de Ingresso" : "Criar Tipo de Ingresso"
          }}
        </h1>
        <p class="text-text-muted">
          Configure as especificações de um
          {{ isEditMode ? "" : "novo" }} lote ou tipo de entrada.
        </p>
      </div>
      <button
        @click="goBack"
        class="flex items-center gap-2 px-4 py-2 border border-surface-elevated text-text-muted rounded-lg hover:bg-surface hover:text-white transition-colors"
      >
        <span class="material-symbols-outlined text-[20px]">arrow_back</span>
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

      <!-- Skeleton Loader -->
      <div v-if="isLoading" class="p-8 space-y-6 animate-pulse">
        <div class="space-y-2">
          <div class="h-4 bg-surface rounded w-32"></div>
          <div class="h-12 bg-surface rounded-lg"></div>
        </div>
        <div class="space-y-2">
          <div class="h-4 bg-surface rounded w-24"></div>
          <div class="h-32 bg-surface rounded-lg"></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <div class="h-4 bg-surface rounded w-20"></div>
            <div class="h-12 bg-surface rounded-lg"></div>
          </div>
          <div class="space-y-2">
            <div class="h-4 bg-surface rounded w-16"></div>
            <div class="h-12 bg-surface rounded-lg"></div>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <div class="h-4 bg-surface rounded w-36"></div>
            <div class="h-12 bg-surface rounded-lg"></div>
          </div>
          <div class="space-y-2">
            <div class="h-4 bg-surface rounded w-32"></div>
            <div class="h-12 bg-surface rounded-lg"></div>
          </div>
        </div>
        <div class="h-16 bg-surface/50 rounded-lg"></div>
      </div>

      <!-- Form -->
      <form v-else @submit.prevent="handleSubmit" class="p-8 space-y-6">
        <!-- Nome do Ingresso -->
        <div class="space-y-2">
          <label for="name" class="block text-sm font-medium text-text-muted">
            Nome do Ingresso
            <span class="text-primary ml-1">*</span>
          </label>
          <div class="relative">
            <div
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
            >
              <span class="material-symbols-outlined text-text-muted"
                >label</span
              >
            </div>
            <input
              id="name"
              v-model="form.name"
              type="text"
              placeholder="Ex: Lote Promocional"
              class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
              :class="{ 'border-red-500': errors.name }"
              :disabled="isSubmitting"
            />
          </div>
          <p v-if="errors.name" class="text-xs text-red-500">
            {{ errors.name[0] }}
          </p>
        </div>

        <!-- Preço e Limite de Vendas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Preço -->
          <div class="space-y-2">
            <label
              for="price"
              class="block text-sm font-medium text-text-muted"
            >
              Preço <span class="text-primary ml-1">*</span>
            </label>
            <div class="relative">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <span class="material-symbols-outlined text-text-muted"
                  >payments</span
                >
              </div>
              <input
                id="price"
                v-model="priceInReais"
                type="number"
                step="0.01"
                min="0"
                placeholder="Ex: 50.00"
                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                :class="{
                  'border-red-500': errors.price_cents,
                }"
                :disabled="isSubmitting"
              />
            </div>
            <p v-if="errors.price_cents" class="text-xs text-red-500">
              {{ errors.price_cents[0] }}
            </p>
          </div>

          <!-- Limite de Vendas (Cota) -->
          <div class="space-y-2">
            <label
              for="quota"
              class="block text-sm font-medium text-text-muted"
            >
              Limite de Vendas (Cota)
            </label>
            <div class="relative">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <span class="material-symbols-outlined text-text-muted"
                  >group</span
                >
              </div>
              <input
                id="quota"
                v-model.number="form.quota"
                type="number"
                min="1"
                placeholder="Ex: 100"
                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white placeholder-text-muted focus:outline-none focus:border-primary transition-all"
                :class="{ 'border-red-500': errors.quota }"
                :disabled="isSubmitting"
              />
            </div>
            <p v-if="errors.quota" class="text-xs text-red-500">
              {{ errors.quota[0] }}
            </p>
          </div>
        </div>

        <!-- Data de Início e Fim das Vendas -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Data Início -->
          <div class="space-y-2">
            <label
              for="start_sale"
              class="block text-sm font-medium text-text-muted"
            >
              Início das Vendas
            </label>
            <div class="relative">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <span class="material-symbols-outlined text-text-muted"
                  >event</span
                >
              </div>
              <input
                id="start_sale"
                v-model="form.start_sale"
                type="datetime-local"
                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white focus:outline-none focus:border-primary transition-all"
                :class="{
                  'border-red-500': errors.start_sale,
                }"
                :disabled="isSubmitting"
              />
            </div>
            <p v-if="errors.start_sale" class="text-xs text-red-500">
              {{ errors.start_sale[0] }}
            </p>
          </div>

          <!-- Data Fim -->
          <div class="space-y-2">
            <label
              for="end_sale"
              class="block text-sm font-medium text-text-muted"
            >
              Fim das Vendas
            </label>
            <div class="relative">
              <div
                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
              >
                <span class="material-symbols-outlined text-text-muted"
                  >event_busy</span
                >
              </div>
              <input
                id="end_sale"
                v-model="form.end_sale"
                type="datetime-local"
                class="block w-full pl-10 pr-3 py-3 bg-surface border border-surface-elevated rounded-lg text-white focus:outline-none focus:border-primary transition-all"
                :class="{ 'border-red-500': errors.end_sale }"
                :disabled="isSubmitting"
              />
            </div>
            <p v-if="errors.end_sale" class="text-xs text-red-500">
              {{ errors.end_sale[0] }}
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
            placeholder="Detalhes do ingresso, regras específicas ou benefícios..."
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
                Defina se este ingresso está disponível para venda.
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

      <!-- Footer -->
      <div
        v-if="!isLoading"
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
          class="px-6 py-2.5 bg-primary text-black text-background font-bold rounded-lg hover:bg-opacity-90 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed shadow-[0_0_20px_rgba(0,230,118,0.4)] hover:shadow-[0_0_30px_rgba(0,230,118,0.6)]"
        >
          <span
            v-if="isSubmitting"
            class="material-symbols-outlined text-lg animate-spin"
            >progress_activity</span
          >
          <span v-else class="material-symbols-outlined font-bold text-lg"
            >check_circle</span
          >
          {{ isSubmitting ? "Salvando..." : "Salvar Ingresso" }}
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
const toast = useToast();

const eventId = computed(() => route.params.eventId);
const ticketTypeId = computed(() => route.params.ticketTypeId);
const isEditMode = computed(() => !!ticketTypeId.value);

const eventName = ref("Carregando...");
const priceInReais = ref("");
const form = ref({
  name: "",
  description: "",
  price_cents: 0,
  quota: null,
  start_sale: "",
  end_sale: "",
  active: true,
});

const errors = ref({});
const isSubmitting = ref(false);
const isLoading = ref(isEditMode.value);

const goBack = () => {
  router.push(`/admin/events/${eventId.value}`);
};

// Converter centavos para reais
const centsToReais = (cents) => {
  return (cents / 100).toFixed(2);
};

// Converter reais para centavos
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

const fetchEventName = async () => {
  try {
    const response = await axios.get(`/admin/events/${eventId.value}`);
    eventName.value = response.data.data.title;
  } catch (error) {
    console.error("Erro ao buscar evento:", error);
    eventName.value = "Evento";
  }
};

const fetchTicketType = async () => {
  if (!isEditMode.value) {
    isLoading.value = false;
    return;
  }

  try {
    const response = await axios.get(
      `/admin/events/${eventId.value}/ticket-types/${ticketTypeId.value}`,
    );
    const ticketType = response.data.data;
    form.value = {
      name: ticketType.name || "",
      description: ticketType.description || "",
      price_cents: ticketType.price_cents || 0,
      quota: ticketType.quota || null,
      start_sale: ticketType.start_sale
        ? formatDatetimeLocal(ticketType.start_sale)
        : "",
      end_sale: ticketType.end_sale
        ? formatDatetimeLocal(ticketType.end_sale)
        : "",
      active: ticketType.active ?? true,
    };
    priceInReais.value = centsToReais(ticketType.price_cents || 0);
  } catch (error) {
    toast.error("Erro ao carregar tipo de ingresso");
    goBack();
  } finally {
    isLoading.value = false;
  }
};

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
      await axios.put(
        `/admin/events/${eventId.value}/ticket-types/${ticketTypeId.value}`,
        payload,
      );
      toast.success("Tipo de ingresso atualizado com sucesso!");
    } else {
      await axios.post(`/admin/events/${eventId.value}/ticket-types`, payload);
      toast.success("Tipo de ingresso criado com sucesso!");
    }

    goBack();
  } catch (error) {
    if (error.response?.status === 422) {
      errors.value = error.response.data.errors || {};
    } else {
      toast.error(
        error.response?.data?.message ||
          "Erro ao salvar tipo de ingresso. Tente novamente.",
      );
    }
  } finally {
    isSubmitting.value = false;
  }
};

onMounted(async () => {
  await fetchEventName();
  await fetchTicketType();
});
</script>

<template>
    <Teleport to="body">
        <Transition name="fade">
            <div v-if="modelValue" class="fixed top-20 inset-x-0 bottom-0 z-40">
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"
                    @click="closeOverlay"
                ></div>

                <!-- Content Container -->
                <div
                    class="relative flex justify-center pt-4 px-4 overflow-y-auto max-h-screen"
                    @click="closeOverlay"
                    @keydown.esc="closeOverlay"
                    tabindex="-1"
                >
                    <div class="w-full max-w-4xl" @click.stop>
                        <!-- Search Panel -->
                        <div
                            class="bg-[rgba(26,29,35,0.95)] border border-border-dark rounded-3xl overflow-hidden shadow-2xl backdrop-blur-xl"
                        >
                            <div class="p-6 md:p-8 space-y-8 md:space-y-10">
                                <!-- Sugestões de Busca -->
                                <section>
                                    <h3
                                        class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500 mb-4"
                                    >
                                        Sugestões de Busca
                                    </h3>
                                    <div class="flex flex-wrap gap-3">
                                        <button
                                            v-for="suggestion in suggestions"
                                            :key="suggestion"
                                            @click="
                                                selectSuggestion(suggestion)
                                            "
                                            class="px-5 py-2 rounded-full bg-surface-dark border border-border-dark text-sm font-medium text-slate-300 hover:border-primary hover:text-primary transition-colors"
                                        >
                                            {{ suggestion }}
                                        </button>
                                    </div>
                                </section>

                                <!-- Eventos Encontrados -->
                                <section v-if="searchQuery">
                                    <div
                                        class="flex items-center justify-between mb-4"
                                    >
                                        <h3
                                            class="text-xs font-bold uppercase tracking-[0.2em] text-slate-500"
                                        >
                                            Eventos Encontrados
                                        </h3>
                                        <span
                                            v-if="!loading && totalResults > 0"
                                            class="text-xs font-medium text-primary"
                                        >
                                            {{ totalResults }} resultado{{
                                                totalResults !== 1 ? "s" : ""
                                            }}
                                        </span>
                                    </div>

                                    <!-- Loading Skeleton -->
                                    <div v-if="loading" class="space-y-2">
                                        <div
                                            v-for="i in 3"
                                            :key="`skeleton-${i}`"
                                            class="flex items-center gap-4 p-3 rounded-2xl bg-white/5 animate-pulse"
                                        >
                                            <div
                                                class="h-16 w-24 flex-shrink-0 rounded-xl bg-slate-700"
                                            ></div>
                                            <div class="flex-1 space-y-2">
                                                <div
                                                    class="h-4 bg-slate-700 rounded w-3/4"
                                                ></div>
                                                <div
                                                    class="h-3 bg-slate-700 rounded w-1/2"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Results -->
                                    <div
                                        v-else-if="results.length > 0"
                                        class="space-y-2"
                                    >
                                        <div
                                            v-for="event in results"
                                            :key="event.id"
                                            @click="selectEvent(event)"
                                            class="group flex items-center gap-4 p-3 rounded-2xl hover:bg-white/5 transition-all cursor-pointer"
                                        >
                                            <div
                                                class="h-16 w-24 flex-shrink-0 overflow-hidden rounded-xl bg-slate-800"
                                            >
                                                <img
                                                    :src="
                                                        event.banner_url ||
                                                        placeholderImage
                                                    "
                                                    :alt="event.title"
                                                    class="h-full w-full object-cover"
                                                />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4
                                                    class="text-base font-bold text-white group-hover:text-primary transition-colors truncate"
                                                >
                                                    {{ event.title }}
                                                </h4>
                                                <div
                                                    class="flex items-center gap-3 mt-1"
                                                >
                                                    <span
                                                        class="flex items-center text-xs text-slate-400"
                                                    >
                                                        <span
                                                            class="material-symbols-outlined text-[14px] mr-1"
                                                            >calendar_month</span
                                                        >
                                                        {{
                                                            formatDate(
                                                                event.date_start,
                                                            )
                                                        }}
                                                    </span>
                                                    <span
                                                        class="flex items-center text-xs text-slate-400"
                                                    >
                                                        <span
                                                            class="material-symbols-outlined text-[14px] mr-1 text-primary"
                                                            >location_on</span
                                                        >
                                                        {{ event.city }}
                                                    </span>
                                                </div>
                                            </div>
                                            <span
                                                class="material-symbols-outlined text-slate-600 group-hover:text-primary transition-colors"
                                            >
                                                chevron_right
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Empty State -->
                                    <div v-else class="text-center py-12">
                                        <span
                                            class="material-symbols-outlined text-6xl text-slate-700 mb-4"
                                            >search_off</span
                                        >
                                        <p class="text-slate-400 text-sm">
                                            Nenhum evento encontrado
                                        </p>
                                    </div>
                                </section>

                                <!-- Empty State (no search query) -->
                                <section v-else class="text-center py-8">
                                    <span
                                        class="material-symbols-outlined text-5xl text-slate-600 mb-3"
                                        >search</span
                                    >
                                    <p class="text-slate-400 text-sm">
                                        Digite algo para buscar eventos
                                    </p>
                                </section>
                            </div>

                            <!-- Footer - Ver Todos -->
                            <div
                                v-if="
                                    !loading && searchQuery && totalResults > 3
                                "
                                class="bg-white/5 p-4 text-center border-t border-border-dark"
                            >
                                <button
                                    @click="viewAllResults"
                                    class="text-sm font-bold text-primary hover:underline transition-all"
                                >
                                    Ver todos os {{ totalResults }} resultados
                                    para "{{ searchQuery }}"
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<script setup>
import { format } from "date-fns";
import { ptBR } from "date-fns/locale";

const props = defineProps({
    modelValue: {
        type: Boolean,
        required: true,
    },
    searchQuery: {
        type: String,
        default: "",
    },
    results: {
        type: Array,
        default: () => [],
    },
    loading: {
        type: Boolean,
        default: false,
    },
    totalResults: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits([
    "update:modelValue",
    "search",
    "view-all",
    "select-event",
]);

const suggestions = ["Maratona", "Meia Maratona", "Caminhada", "Corrida"];

const placeholderImage =
    "https://via.placeholder.com/800x450/1E212B/00e677?text=Evento";

function closeOverlay() {
    emit("update:modelValue", false);
}

function selectSuggestion(term) {
    emit("search", term);
}

function selectEvent(event) {
    emit("select-event", event);
}

function viewAllResults() {
    emit("view-all");
}

function formatDate(dateString) {
    if (!dateString) return "";
    try {
        const date = new Date(dateString);
        return format(date, "dd MMM", { locale: ptBR }).replace(".", "");
    } catch {
        return "";
    }
}
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>

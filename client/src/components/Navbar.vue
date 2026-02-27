<template>
    <nav
        class="sticky top-0 z-50 w-full border-b border-border-dark bg-[#0F1114]/90 backdrop-blur-md transition-all duration-300"
    >
        <div
            class="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8"
        >
            <!-- Logo -->
            <router-link
                to="/"
                class="flex items-center gap-2 group cursor-pointer"
            >
                <div
                    class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary transition-colors group-hover:bg-primary group-hover:text-background-dark"
                >
                    <span class="material-symbols-outlined text-3xl">bolt</span>
                </div>
                <span
                    class="hidden text-xl font-black tracking-tight text-white md:block"
                >
                    RUNNING <span class="text-primary">TICKETS</span>
                </span>
            </router-link>

            <!-- Search Bar (Hidden on mobile, visible on tablet+) -->
            <div v-if="!hideSearch" class="hidden flex-1 px-8 md:flex max-w-lg">
                <div class="relative w-full">
                    <div
                        class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                    >
                        <span class="material-symbols-outlined text-slate-400"
                            >search</span
                        >
                    </div>
                    <input
                        v-model="searchQuery"
                        @focus="showSearchOverlay = true"
                        class="block w-full rounded-xl border-none bg-surface-dark py-2.5 pl-10 pr-3 text-sm text-white placeholder-slate-400 focus:ring-2 focus:ring-primary focus:bg-[#252B3A] transition-all"
                        placeholder="Buscar eventos, maratonas..."
                        type="text"
                    />
                </div>
            </div>

            <!-- Right Actions -->
            <div v-if="!hideActions" class="flex items-center gap-4">
                <router-link
                    v-if="!authStore.isAuthenticated"
                    to="/entrar"
                    class="hidden rounded-lg bg-surface-dark px-5 py-2.5 text-sm font-semibold text-white transition-all hover:bg-[#2A2F3D] sm:block"
                >
                    Entrar
                </router-link>

                <div v-else class="hidden sm:flex items-center gap-3">
                    <router-link
                        to="/meus-ingressos"
                        class="flex items-center gap-2 rounded-lg border border-primary/40 bg-primary/10 px-4 py-2 text-sm font-semibold text-primary transition-all hover:bg-primary/20"
                    >
                        <span class="material-symbols-outlined text-base">confirmation_number</span>
                        Meus Ingressos
                    </router-link>

                    <!-- Saudação + dropdown -->
                    <div class="relative" ref="userMenuRef">
                        <button
                            @click="showUserMenu = !showUserMenu"
                            class="flex items-center gap-2 rounded-lg border border-border-dark bg-surface-dark px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-[#2A2F3D]"
                        >
                            <div class="flex flex-col items-start leading-none">
                                <span class="text-[10px] text-slate-400 font-normal">Bem-vindo,</span>
                                <span>{{ firstName }}</span>
                            </div>
                            <span class="material-symbols-outlined text-slate-400 text-base">expand_more</span>
                        </button>

                        <!-- Dropdown -->
                        <div
                            v-if="showUserMenu"
                            class="absolute right-0 mt-2 w-44 rounded-xl border border-border-dark bg-surface-dark shadow-xl overflow-hidden z-50"
                        >
                            <button
                                @click="handleLogout"
                                class="flex items-center gap-2 w-full px-4 py-3 text-sm text-slate-300 hover:bg-[#2A2F3D] hover:text-white transition-colors"
                            >
                                <span class="material-symbols-outlined text-base">logout</span>
                                Sair
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mobile Menu Button (always visible) -->
            <div v-if="!hideActions" class="flex items-center">
                <button @click="toggleMobileMenu" class="sm:hidden text-white">
                    <span class="material-symbols-outlined">menu</span>
                </button>
            </div>
        </div>

        <!-- Mobile Search (Visible only on mobile) -->
        <div v-if="!hideSearch" class="md:hidden px-4 pb-4">
            <div class="relative w-full">
                <div
                    class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3"
                >
                    <span class="material-symbols-outlined text-slate-400"
                        >search</span
                    >
                </div>
                <input
                    v-model="searchQuery"
                    @focus="showSearchOverlay = true"
                    class="block w-full rounded-lg border-none bg-surface-dark py-2 pl-10 pr-3 text-sm text-white placeholder-slate-400 focus:ring-1 focus:ring-primary"
                    placeholder="Buscar eventos..."
                    type="text"
                />
            </div>
        </div>

        <!-- Mobile Menu -->
        <div
            v-if="showMobileMenu"
            class="sm:hidden border-t border-border-dark bg-surface-dark"
        >
            <div class="px-4 py-4 space-y-2">
                <router-link
                    v-if="!authStore.isAuthenticated"
                    to="/entrar"
                    class="block w-full text-center rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-background-dark"
                    @click="showMobileMenu = false"
                >
                    Entrar
                </router-link>
                <template v-else>
                    <router-link
                        to="/meus-ingressos"
                        class="block w-full text-center rounded-lg bg-surface-dark px-4 py-2 text-sm font-semibold text-white"
                        @click="showMobileMenu = false"
                    >
                        Meus Ingressos
                    </router-link>
                    <router-link
                        to="/meus-pedidos"
                        class="block w-full text-center rounded-lg bg-surface-dark px-4 py-2 text-sm font-semibold text-white"
                        @click="showMobileMenu = false"
                    >
                        Meus Pedidos
                    </router-link>
                    <button
                        @click="handleLogout"
                        class="block w-full rounded-lg border border-border-dark px-4 py-2 text-sm font-semibold text-slate-300"
                    >
                        Sair
                    </button>
                </template>
            </div>
        </div>

        <!-- Search Overlay -->
        <SearchOverlay
            v-model="showSearchOverlay"
            :search-query="searchQuery"
            :results="searchResults"
            :loading="searchLoading"
            :total-results="totalResults"
            @search="handleSuggestionSearch"
            @view-all="handleViewAll"
            @select-event="handleSelectEvent"
        />
    </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { useRouter } from "vue-router";
import { watchDebounced } from "@vueuse/core";
import { useAuthStore } from "../stores/auth";
import { useEventsStore } from "../stores/events";
import SearchOverlay from "./SearchOverlay.vue";

const props = defineProps({
    hideSearch: {
        type: Boolean,
        default: false,
    },
    hideActions: {
        type: Boolean,
        default: false,
    },
});

const router = useRouter();
const authStore = useAuthStore();
const eventsStore = useEventsStore();

const searchQuery = ref("");
const showMobileMenu = ref(false);
const showSearchOverlay = ref(false);
const searchResults = ref([]);
const searchLoading = ref(false);
const totalResults = ref(0);
const showUserMenu = ref(false);
const userMenuRef = ref(null);

const firstName = computed(() => {
    const name = authStore.user?.name || "";
    return name.split(" ")[0];
});

function handleClickOutside(event) {
    if (userMenuRef.value && !userMenuRef.value.contains(event.target)) {
        showUserMenu.value = false;
    }
}

onMounted(() => document.addEventListener("click", handleClickOutside));
onUnmounted(() => document.removeEventListener("click", handleClickOutside));

async function performSearch(query) {
    if (!query || !query.trim()) {
        searchResults.value = [];
        totalResults.value = 0;
        return;
    }

    searchLoading.value = true;
    try {
        await eventsStore.fetchEvents({
            search: query,
            per_page: 3,
        });
        searchResults.value = eventsStore.events;
        totalResults.value = eventsStore.pagination.total;
    } catch (error) {
        console.error("Erro ao buscar eventos:", error);
        searchResults.value = [];
        totalResults.value = 0;
    } finally {
        searchLoading.value = false;
    }
}

function handleSuggestionSearch(term) {
    searchQuery.value = term;
    performSearch(term);
}

function handleViewAll() {
    router.push({
        name: "events",
        query: { search: searchQuery.value },
    });
    showSearchOverlay.value = false;
    searchQuery.value = "";
}

function handleSelectEvent(event) {
    router.push({
        name: "event-details",
        params: { slug: event.slug },
    });
    showSearchOverlay.value = false;
    searchQuery.value = "";
}

// Watch search query with debounce
watchDebounced(
    searchQuery,
    (newVal) => {
        if (showSearchOverlay.value) {
            performSearch(newVal);
        }
    },
    { debounce: 500 },
);

function toggleMobileMenu() {
    showMobileMenu.value = !showMobileMenu.value;
}

async function handleLogout() {
    await authStore.logout();
    showMobileMenu.value = false;
    showUserMenu.value = false;
    router.push({ name: "home" });
}
</script>

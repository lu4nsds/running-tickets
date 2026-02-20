<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-white">Eventos</h1>
                <p class="text-text-secondary mt-1">
                    Gerencie todos os eventos da plataforma de forma
                    centralizada.
                </p>
            </div>
            <button
                @click="openCreatePage"
                class="flex items-center gap-2 px-6 py-3 bg-primary text-black rounded-xl font-semibold shadow-[0_0_20px_rgba(0,230,118,0.4)] hover:shadow-[0_0_30px_rgba(0,230,118,0.6)] transition-all"
            >
                <span class="material-symbols-outlined text-[20px]">add</span>
                Criar Evento
            </button>
        </div>

        <!-- Filters -->
        <div
            class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between"
        >
            <!-- Search -->
            <div class="relative w-full md:w-[20rem]">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]"
                >
                    search
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar por título ou cidade..."
                    class="w-full bg-surface-elevated border border-surface-elevated rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                    @input="debouncedSearch"
                />
            </div>

            <div class="flex items-center gap-4">
                <!-- Organizer Filter -->
                <select
                    v-model="selectedOrganizer"
                    @change="filterByOrganizer"
                    class="bg-surface-elevated border border-surface-elevated rounded-lg px-4 py-2.5 text-white focus:outline-none focus:border-primary transition-colors appearance-none cursor-pointer min-w-[200px]"
                >
                    <option value="">Todos Organizadores</option>
                    <option
                        v-for="org in organizers"
                        :key="org.id"
                        :value="org.id"
                    >
                        {{ org.name }}
                    </option>
                </select>

                <!-- Status Tabs -->
                <div class="flex items-center gap-1 bg-surface rounded-lg p-1">
                    <button
                        v-for="tab in statusTabs"
                        :key="tab.value"
                        @click="filterByStatus(tab.value)"
                        class="px-4 py-2 rounded-md text-sm font-medium transition-colors"
                        :class="
                            currentStatus === tab.value
                                ? 'bg-primary text-black'
                                : 'text-text-secondary hover:text-white'
                        "
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Initial Loading Skeleton -->
        <div
            v-if="!hasLoadedOnce && store.isLoading"
            class="bg-card-bg rounded-xl border border-surface-elevated overflow-hidden"
        >
            <table class="w-full">
                <thead>
                    <tr class="border-b border-surface-elevated">
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Banner
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Título / Organizador
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Cidade
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Data Início
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Participantes
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Status
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-surface-elevated">
                    <tr v-for="n in 6" :key="'skeleton-' + n" class="h-[77px]">
                        <td class="px-6 py-4">
                            <div
                                class="w-14 h-10 rounded-lg bg-surface-elevated animate-pulse"
                            ></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-2">
                                <div
                                    class="h-4 w-40 bg-surface-elevated rounded animate-pulse"
                                ></div>
                                <div
                                    class="h-3 w-28 bg-surface-elevated rounded animate-pulse"
                                ></div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div
                                class="h-4 w-28 bg-surface-elevated rounded animate-pulse"
                            ></div>
                        </td>
                        <td class="px-6 py-4">
                            <div
                                class="h-4 w-32 bg-surface-elevated rounded animate-pulse"
                            ></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div
                                class="h-4 w-16 bg-surface-elevated rounded animate-pulse mx-auto"
                            ></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div
                                class="h-6 w-16 bg-surface-elevated rounded-full animate-pulse mx-auto"
                            ></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center justify-center gap-1">
                                <div
                                    class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                                ></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Empty State -->
        <div
            v-else-if="hasLoadedOnce && store.isEmpty && !store.isLoading"
            class="bg-card-bg rounded-xl border border-surface-elevated p-12 text-center"
        >
            <span
                class="material-symbols-outlined text-[48px] text-text-muted mb-4"
            >
                directions_run
            </span>
            <h3 class="text-lg font-semibold text-white mb-2">
                Nenhum evento encontrado
            </h3>
            <p class="text-text-secondary mb-6">
                {{
                    searchQuery || currentStatus || selectedOrganizer
                        ? "Tente ajustar os filtros de busca."
                        : "Comece criando o primeiro evento na plataforma."
                }}
            </p>
            <button
                v-if="!searchQuery && !currentStatus && !selectedOrganizer"
                @click="openCreatePage"
                class="btn-primary inline-flex items-center gap-2"
            >
                <span class="material-symbols-outlined text-[20px]">add</span>
                Criar Evento
            </button>
        </div>

        <!-- Table Container -->
        <div
            v-if="hasLoadedOnce && localPagination.total > 0"
            class="bg-card-bg rounded-xl border border-surface-elevated overflow-hidden"
        >
            <table class="w-full">
                <thead>
                    <tr class="border-b border-surface-elevated">
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Banner
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Título / Organizador
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Cidade
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Data Início
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Participantes
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Status
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Ações
                        </th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y divide-surface-elevated"
                    style="min-height: 770px"
                >
                    <!-- Skeleton rows during loading -->
                    <template v-if="store.isLoading">
                        <tr
                            v-for="n in 6"
                            :key="'skeleton-row-' + n"
                            class="h-[77px]"
                        >
                            <td class="px-6 py-4">
                                <div
                                    class="w-14 h-10 rounded-lg bg-surface-elevated animate-pulse"
                                ></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-40 bg-surface-elevated rounded animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 w-28 bg-surface-elevated rounded animate-pulse"
                                    ></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="h-4 w-28 bg-surface-elevated rounded animate-pulse"
                                ></div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="h-4 w-32 bg-surface-elevated rounded animate-pulse"
                                ></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div
                                    class="h-4 w-16 bg-surface-elevated rounded animate-pulse mx-auto"
                                ></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div
                                    class="h-6 w-16 bg-surface-elevated rounded-full animate-pulse mx-auto"
                                ></div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <div
                                        class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                                    ></div>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <!-- Data rows when not loading -->
                    <template v-else>
                        <tr
                            v-for="event in store.events"
                            :key="event.id"
                            class="hover:bg-white/[0.02] transition-colors h-[77px]"
                        >
                            <!-- Banner -->
                            <td class="px-6 py-4">
                                <div
                                    class="w-14 h-10 rounded-lg bg-surface-elevated overflow-hidden flex-shrink-0"
                                >
                                    <img
                                        v-if="event.banner_url"
                                        :src="event.banner_url"
                                        :alt="event.title"
                                        class="w-full h-full object-cover"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full flex items-center justify-center"
                                    >
                                        <span
                                            class="material-symbols-outlined text-text-muted text-[20px]"
                                        >
                                            image
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <!-- Título + Organizador -->
                            <td class="px-6 py-4">
                                <div>
                                    <p class="text-white font-medium">
                                        {{ event.title }}
                                    </p>
                                    <p
                                        class="text-text-muted text-sm flex items-center gap-1"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[14px]"
                                            >business</span
                                        >
                                        {{ event.organizer?.name || "-" }}
                                    </p>
                                </div>
                            </td>

                            <!-- Cidade -->
                            <td class="px-6 py-4">
                                <span class="text-text-secondary">
                                    {{ event.city }}
                                </span>
                            </td>

                            <!-- Data Início -->
                            <td class="px-6 py-4">
                                <span class="text-text-secondary">
                                    {{ formatDateTime(event.date_start) }}
                                </span>
                            </td>

                            <!-- Participantes -->
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center gap-1 text-sm"
                                    :class="
                                        event.max_participants
                                            ? 'text-primary'
                                            : 'text-text-muted'
                                    "
                                >
                                    <span
                                        class="material-symbols-outlined text-[16px]"
                                        >{{
                                            event.max_participants
                                                ? "group"
                                                : "all_inclusive"
                                        }}</span
                                    >
                                    {{
                                        event.max_participants
                                            ? event.max_participants
                                            : "Ilimitado"
                                    }}
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                    :class="getStatusClass(event.status)"
                                >
                                    {{ getStatusLabel(event.status) }}
                                </span>
                            </td>

                            <!-- Ações -->
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <button
                                        @click="viewEvent(event)"
                                        class="p-2 text-text-muted hover:text-white hover:bg-surface-elevated rounded-lg transition-colors"
                                        title="Ver detalhes"
                                    >
                                        <span
                                            class="material-symbols-outlined text-[20px]"
                                        >
                                            visibility
                                        </span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <!-- Empty rows to maintain fixed height -->
                        <tr
                            v-for="n in emptyRowsCount"
                            :key="'empty-' + n"
                            class="h-[77px]"
                        >
                            <td colspan="7"></td>
                        </tr>
                    </template>
                </tbody>
            </table>

            <!-- Pagination -->
            <div
                v-if="localPagination.total > 0"
                class="flex items-center justify-between px-6 py-4 border-t border-surface-elevated"
            >
                <p class="text-text-muted text-sm">
                    Mostrando {{ paginationInfo.from }} a
                    {{ paginationInfo.to }} de
                    {{ localPagination.total }} resultados
                </p>
                <div class="flex items-center gap-1">
                    <!-- Prev -->
                    <button
                        @click="goToPage(localPagination.currentPage - 1)"
                        :disabled="
                            localPagination.currentPage === 1 || store.isLoading
                        "
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-text-muted hover:text-white hover:bg-surface-elevated disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-lg"
                    >
                        &lt;
                    </button>

                    <!-- Page Numbers -->
                    <template v-for="page in visiblePages" :key="page">
                        <button
                            v-if="page !== '...'"
                            @click="goToPage(page)"
                            :disabled="store.isLoading"
                            class="w-9 h-9 rounded-lg text-sm font-medium transition-colors"
                            :class="
                                page === localPagination.currentPage
                                    ? 'bg-primary text-black'
                                    : 'text-text-secondary hover:text-white hover:bg-surface-elevated disabled:opacity-50'
                            "
                        >
                            {{ page }}
                        </button>
                        <span v-else class="px-2 text-text-muted">...</span>
                    </template>

                    <!-- Next -->
                    <button
                        @click="goToPage(localPagination.currentPage + 1)"
                        :disabled="
                            localPagination.currentPage ===
                                localPagination.lastPage || store.isLoading
                        "
                        class="w-9 h-9 flex items-center justify-center rounded-lg text-text-muted hover:text-white hover:bg-surface-elevated disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-lg"
                    >
                        &gt;
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRouter } from "vue-router";
import { useEventsStore } from "@/stores/events";
import { useToast } from "@/composables/useToast";

const router = useRouter();
const store = useEventsStore();
const toast = useToast();

// State
const searchQuery = ref("");
const currentStatus = ref("");
const selectedOrganizer = ref("");
const hasLoadedOnce = ref(false);
const organizers = ref([]);

// Local pagination state that persists during loading
const localPagination = ref({
    currentPage: 1,
    lastPage: 1,
    perPage: 6,
    total: 0,
});

// Watch store pagination and update local only when not loading
watch(
    () => store.pagination,
    (newPagination) => {
        if (!store.isLoading) {
            localPagination.value = { ...newPagination };
        }
    },
    { deep: true },
);

// Debounce timer
let searchTimeout = null;

// Status tabs
const statusTabs = [
    { label: "Todos", value: "" },
    { label: "Ativos", value: "ativo" },
    { label: "Inativos", value: "inativo" },
];

// Computed
const emptyRowsCount = computed(() => {
    const itemsPerPage = 6;
    const currentItems = store.events.length;
    return Math.max(0, itemsPerPage - currentItems);
});

const paginationInfo = computed(() => {
    const page = localPagination.value.currentPage;
    const perPage = localPagination.value.perPage;
    const total = localPagination.value.total;
    const from = total > 0 ? (page - 1) * perPage + 1 : 0;
    const to = Math.min(page * perPage, total);
    return { from, to };
});

const visiblePages = computed(() => {
    const current = localPagination.value.currentPage;
    const last = localPagination.value.lastPage;
    const pages = [];

    if (last <= 7) {
        for (let i = 1; i <= last; i++) pages.push(i);
    } else {
        pages.push(1);
        if (current > 3) pages.push("...");

        const start = Math.max(2, current - 1);
        const end = Math.min(last - 1, current + 1);

        for (let i = start; i <= end; i++) pages.push(i);

        if (current < last - 2) pages.push("...");
        pages.push(last);
    }

    return pages;
});

// Methods
const formatDateTime = (date) => {
    if (!date) return "-";
    const d = new Date(date);
    return (
        d.toLocaleDateString("pt-BR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
        }) +
        " " +
        d.toLocaleTimeString("pt-BR", {
            hour: "2-digit",
            minute: "2-digit",
        })
    );
};

const getStatusClass = (status) => {
    const classes = {
        ativo: "bg-primary/10 text-primary",
        inativo: "bg-yellow-500/10 text-yellow-400",
    };
    return classes[status] || "bg-surface-elevated text-text-muted";
};

const getStatusLabel = (status) => {
    const labels = {
        ativo: "Ativo",
        inativo: "Inativo",
    };
    return labels[status] || status;
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        store.setFilters({ search: searchQuery.value });
        store.fetchEvents(1);
    }, 300);
};

const filterByStatus = (status) => {
    currentStatus.value = status;
    store.setFilters({ status });
    store.fetchEvents(1);
};

const filterByOrganizer = () => {
    store.setFilters({ organizer_id: selectedOrganizer.value });
    store.fetchEvents(1);
};

const goToPage = (page) => {
    if (
        page >= 1 &&
        page <= localPagination.value.lastPage &&
        !store.isLoading
    ) {
        store.fetchEvents(page);
    }
};

const viewEvent = (event) => {
    router.push(`/admin/events/${event.id}`);
};

const openCreatePage = () => {
    router.push("/admin/events/create");
};

const loadOrganizers = async () => {
    const result = await store.fetchOrganizers();
    if (result.success) {
        organizers.value = result.data;
    }
};

// Lifecycle
onMounted(async () => {
    await Promise.all([store.fetchEvents(), loadOrganizers()]);
    localPagination.value = { ...store.pagination };
    hasLoadedOnce.value = true;
});
</script>

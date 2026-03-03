<template>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-extrabold text-white">
                    Organizadores
                </h1>
                <p class="text-text-secondary mt-1">
                    Gerencie todos os organizadores da plataforma
                </p>
            </div>
            <button
                @click="openCreateModal"
                class="flex items-center gap-2 px-6 py-3 bg-primary text-black rounded-xl font-semibold shadow-[0_0_20px_rgba(0,230,118,0.4)] hover:shadow-[0_0_30px_rgba(0,230,118,0.6)] transition-all"
            >
                <span class="material-symbols-outlined text-[20px]">add</span>
                Criar Organizador
            </button>
        </div>

        <!-- Filters -->
        <div
            class="flex flex-col md:flex-row gap-4 items-start md:items-center justify-between"
        >
            <!-- Search -->
            <div class="relative w-full md:w-[30rem]">
                <span
                    class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-text-muted text-[20px]"
                >
                    search
                </span>
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Buscar por nome, e-mail ou CNPJ"
                    class="w-full bg-surface-elevated border border-surface-elevated rounded-lg pl-10 pr-4 py-2.5 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors"
                    @input="debouncedSearch"
                />
            </div>

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

        <!-- Initial Loading Skeleton (before first load) -->
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
                            Organizador
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            CNPJ
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Telefone
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Eventos
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
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-full bg-surface-elevated animate-pulse"
                                ></div>
                                <div class="space-y-2">
                                    <div
                                        class="h-4 w-32 bg-surface-elevated rounded animate-pulse"
                                    ></div>
                                    <div
                                        class="h-3 w-40 bg-surface-elevated rounded animate-pulse"
                                    ></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div
                                class="h-4 w-36 bg-surface-elevated rounded animate-pulse"
                            ></div>
                        </td>
                        <td class="px-6 py-4">
                            <div
                                class="h-4 w-28 bg-surface-elevated rounded animate-pulse"
                            ></div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div
                                class="w-8 h-8 rounded-full bg-surface-elevated animate-pulse mx-auto"
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
                                <div
                                    class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                                ></div>
                                <div
                                    class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                                ></div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
            <div
                class="flex items-center justify-between px-6 py-4 border-t border-surface-elevated"
            >
                <div
                    class="h-4 w-48 bg-surface-elevated rounded animate-pulse"
                ></div>
                <div class="flex items-center gap-1">
                    <div
                        class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                    <div
                        class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                    <div
                        class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                    <div
                        class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div
            v-else-if="hasLoadedOnce && store.isEmpty && !store.isLoading"
            class="bg-card-bg rounded-xl border border-surface-elevated p-12 text-center"
        >
            <span
                class="material-symbols-outlined text-[48px] text-text-muted mb-4"
            >
                business
            </span>
            <h3 class="text-lg font-semibold text-white mb-2">
                Nenhum organizador encontrado
            </h3>
            <p class="text-text-secondary mb-6">
                {{
                    searchQuery || currentStatus
                        ? "Tente ajustar os filtros de busca."
                        : "Comece adicionando o primeiro organizador à plataforma."
                }}
            </p>
            <button
                v-if="!searchQuery && !currentStatus"
                @click="openCreateModal"
                class="btn-primary inline-flex items-center gap-2"
            >
                <span class="material-symbols-outlined text-[20px]">add</span>
                Criar Organizador
            </button>
        </div>

        <!-- Table Container (always visible after first load when there's data) -->
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
                            Organizador
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            CNPJ
                        </th>
                        <th
                            class="text-left text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Telefone
                        </th>
                        <th
                            class="text-center text-text-muted text-xs font-medium uppercase tracking-wider px-6 py-4"
                        >
                            Eventos
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
                    style="min-height: 462px"
                >
                    <!-- Skeleton rows during loading -->
                    <template v-if="store.isLoading">
                        <tr
                            v-for="n in 6"
                            :key="'skeleton-row-' + n"
                            class="h-[77px]"
                        >
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full bg-surface-elevated animate-pulse"
                                    ></div>
                                    <div class="space-y-2">
                                        <div
                                            class="h-4 w-32 bg-surface-elevated rounded animate-pulse"
                                        ></div>
                                        <div
                                            class="h-3 w-40 bg-surface-elevated rounded animate-pulse"
                                        ></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="h-4 w-36 bg-surface-elevated rounded animate-pulse"
                                ></div>
                            </td>
                            <td class="px-6 py-4">
                                <div
                                    class="h-4 w-28 bg-surface-elevated rounded animate-pulse"
                                ></div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div
                                    class="w-8 h-8 rounded-full bg-surface-elevated animate-pulse mx-auto"
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
                                    <div
                                        class="w-9 h-9 bg-surface-elevated rounded-lg animate-pulse"
                                    ></div>
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
                            v-for="organizer in store.organizers"
                            :key="organizer.id"
                            class="hover:bg-white/[0.02] transition-colors h-[77px]"
                        >
                            <!-- Nome + Email -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-semibold"
                                        :class="getAvatarColor(organizer.name)"
                                    >
                                        {{ getInitials(organizer.name) }}
                                    </div>
                                    <div>
                                        <p class="text-white font-medium">
                                            {{ organizer.name }}
                                        </p>
                                        <p class="text-text-muted text-sm">
                                            {{ organizer.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>

                            <!-- Documento -->
                            <td class="px-6 py-4">
                                <span
                                    class="text-text-secondary font-mono text-sm"
                                >
                                    {{ formatDocument(organizer.document) }}
                                </span>
                            </td>

                            <!-- Telefone -->
                            <td class="px-6 py-4">
                                <span class="text-text-secondary">
                                    {{ formatPhone(organizer.phone) }}
                                </span>
                            </td>

                            <!-- Eventos Count -->
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full text-sm font-medium"
                                    :class="
                                        organizer.events_count > 0
                                            ? 'bg-primary/10 text-primary'
                                            : 'bg-surface-elevated text-text-muted'
                                    "
                                >
                                    {{ organizer.events_count || 0 }}
                                </span>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-6 py-4 text-center">
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium"
                                    :class="getStatusClass(organizer.status)"
                                >
                                    {{ getStatusLabel(organizer.status) }}
                                </span>
                            </td>

                            <!-- Ações -->
                            <td class="px-6 py-4">
                                <div
                                    class="flex items-center justify-center gap-1"
                                >
                                    <button
                                        @click="viewOrganizer(organizer)"
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
                            <td colspan="6"></td>
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
import { useOrganizersStore } from "@/stores/organizers";
import { useToast } from "@/composables/useToast";

const router = useRouter();
const store = useOrganizersStore();
const toast = useToast();

// State
const searchQuery = ref("");
const currentStatus = ref("");
const hasLoadedOnce = ref(false);

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
    { label: "Ativos", value: "active" },
    { label: "Inativos", value: "inactive" },
];

// Computed
const emptyRowsCount = computed(() => {
    const itemsPerPage = 6;
    const currentItems = store.organizers.length;
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
const getInitials = (name) => {
    if (!name) return "?";
    return name
        .split(" ")
        .map((n) => n[0])
        .join("")
        .toUpperCase()
        .slice(0, 2);
};

const getAvatarColor = (name) => {
    const colors = [
        "bg-primary/20 text-primary",
        "bg-blue-500/20 text-blue-400",
        "bg-purple-500/20 text-purple-400",
        "bg-yellow-500/20 text-yellow-400",
        "bg-pink-500/20 text-pink-400",
    ];
    const index = name ? name.charCodeAt(0) % colors.length : 0;
    return colors[index];
};

const formatDocument = (doc) => {
    if (!doc) return "-";
    const cleaned = doc.replace(/\D/g, "");
    if (cleaned.length === 11) {
        // CPF: 000.000.000-00
        return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, "$1.$2.$3-$4");
    } else if (cleaned.length === 14) {
        // CNPJ: 00.000.000/0000-00
        return cleaned.replace(
            /(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/,
            "$1.$2.$3/$4-$5",
        );
    }
    return doc;
};

const formatPhone = (phone) => {
    if (!phone) return "-";
    const cleaned = phone.replace(/\D/g, "");
    if (cleaned.length === 11) {
        return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
    } else if (cleaned.length === 10) {
        return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, "($1) $2-$3");
    }
    return phone;
};

const getStatusClass = (status) => {
    const classes = {
        active: "bg-primary/10 text-primary",
        blocked: "bg-red-500/10 text-red-400",
    };
    return classes[status] || "bg-surface-elevated text-text-muted";
};

const getStatusLabel = (status) => {
    const labels = {
        active: "Ativo",
        blocked: "Inativo",
    };
    return labels[status] || status;
};

const debouncedSearch = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        store.setFilters({ search: searchQuery.value });
        store.fetchOrganizers(1);
    }, 300);
};

const filterByStatus = (status) => {
    currentStatus.value = status;
    store.setFilters({ status });
    store.fetchOrganizers(1);
};

const goToPage = (page) => {
    if (
        page >= 1 &&
        page <= localPagination.value.lastPage &&
        !store.isLoading
    ) {
        store.fetchOrganizers(page);
    }
};

const viewOrganizer = (organizer) => {
    router.push(`/admin/organizers/${organizer.id}`);
};

const openCreateModal = () => {
    router.push("/admin/organizers/create");
};

// Lifecycle
onMounted(async () => {
    await store.fetchOrganizers();
    localPagination.value = { ...store.pagination };
    hasLoadedOnce.value = true;
});
</script>

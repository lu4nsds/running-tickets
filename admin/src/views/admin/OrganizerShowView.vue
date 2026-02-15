<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="space-y-6">
            <!-- Skeleton Header -->
            <div class="flex items-center gap-2 text-text-muted">
                <div
                    class="h-4 w-24 bg-surface-elevated rounded animate-pulse"
                ></div>
                <span>/</span>
                <div
                    class="h-4 w-32 bg-surface-elevated rounded animate-pulse"
                ></div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="w-14 h-14 bg-surface-elevated rounded-xl animate-pulse"
                    ></div>
                    <div class="space-y-2">
                        <div
                            class="h-8 w-48 bg-surface-elevated rounded animate-pulse"
                        ></div>
                        <div
                            class="h-4 w-32 bg-surface-elevated rounded animate-pulse"
                        ></div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div
                        class="h-10 w-36 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                    <div
                        class="h-10 w-36 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                </div>
            </div>

            <!-- Skeleton Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6 animate-pulse"
                >
                    <div
                        class="h-6 w-40 bg-surface-elevated rounded mb-6"
                    ></div>
                    <div class="space-y-4">
                        <div
                            class="h-4 w-full bg-surface-elevated rounded"
                        ></div>
                        <div
                            class="h-4 w-3/4 bg-surface-elevated rounded"
                        ></div>
                        <div
                            class="h-4 w-1/2 bg-surface-elevated rounded"
                        ></div>
                    </div>
                </div>
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6 animate-pulse"
                >
                    <div
                        class="h-4 w-20 bg-surface-elevated rounded mb-2"
                    ></div>
                    <div class="h-10 w-16 bg-surface-elevated rounded"></div>
                </div>
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6 animate-pulse"
                >
                    <div
                        class="h-4 w-24 bg-surface-elevated rounded mb-2"
                    ></div>
                    <div class="h-10 w-24 bg-surface-elevated rounded"></div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <ErrorState
            v-else-if="error"
            title="Erro ao carregar organizador"
            :message="error"
            @retry="fetchOrganizer"
        />

        <!-- Content -->
        <div v-else-if="organizer" class="space-y-6">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm">
                <router-link
                    to="/admin/organizers"
                    class="text-primary hover:underline"
                >
                    Organizadores
                </router-link>
                <span class="text-text-muted">/</span>
                <span class="text-text-muted">{{ organizer.name }}</span>
            </nav>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <!-- Icon -->
                    <div
                        class="w-14 h-14 bg-surface rounded-xl flex items-center justify-center border border-surface-elevated"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-2xl"
                        >
                            calendar_month
                        </span>
                    </div>

                    <!-- Info -->
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-white">
                                {{ organizer.name }}
                            </h1>
                            <span
                                :class="getStatusBadgeClass(organizer.status)"
                            >
                                {{ getStatusLabel(organizer.status) }}
                            </span>
                        </div>
                        <p class="text-text-muted text-sm">
                            Desde {{ formatMemberSince(organizer.created_at) }}
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    <button
                        @click="editOrganizer"
                        class="flex items-center gap-2 px-4 py-2.5 border border-primary text-primary rounded-lg font-medium hover:bg-primary/10 transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]"
                            >edit</span
                        >
                        Editar Organizador
                    </button>
                    <button
                        @click="confirmDelete"
                        class="flex items-center gap-2 px-4 py-2.5 border border-red-500 text-red-500 rounded-lg font-medium hover:bg-red-500/10 transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]"
                            >delete</span
                        >
                        Deletar Organizador
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                <!-- Administrative Data -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <h3
                        class="flex items-center gap-2 text-white font-semibold mb-6"
                    >
                        <span
                            class="material-symbols-outlined text-primary text-[20px]"
                            >settings</span
                        >
                        Dados Administrativos
                    </h3>

                    <div class="space-y-4">
                        <div>
                            <p
                                class="text-text-muted text-xs uppercase tracking-wider mb-1"
                            >
                                E-mail de Contato
                            </p>
                            <p class="text-white">{{ organizer.email }}</p>
                        </div>

                        <div>
                            <p
                                class="text-text-muted text-xs uppercase tracking-wider mb-1"
                            >
                                Documento (CNPJ)
                            </p>
                            <p class="text-white">
                                {{ formatDocument(organizer.document) }}
                            </p>
                        </div>

                        <div>
                            <p
                                class="text-text-muted text-xs uppercase tracking-wider mb-1"
                            >
                                Telefone
                            </p>
                            <p class="text-white">
                                {{ formatPhone(organizer.phone) }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Events Count -->
                <div
                    class="bg-primary/10 border border-primary/20 rounded-xl p-6 flex flex-col items-center justify-center hover:bg-primary/20 transition-all cursor-default"
                >
                    <span
                        class="material-symbols-outlined text-primary text-3xl mb-2"
                    >
                        event
                    </span>
                    <p
                        class="text-text-muted text-sm uppercase tracking-wider mb-1"
                    >
                        Eventos
                    </p>
                    <p
                        class="text-4xl font-bold text-primary"
                        style="text-shadow: 0 0 10px rgba(0, 230, 119, 0.5)"
                    >
                        {{ organizer.events_count || 0 }}
                    </p>
                </div>

                <!-- Total Sales -->
                <div
                    class="bg-primary rounded-xl p-6 flex flex-col items-center justify-center"
                >
                    <span
                        class="material-symbols-outlined text-black/70 text-3xl mb-2"
                    >
                        payments
                    </span>
                    <p
                        class="text-black/70 text-sm uppercase tracking-wider mb-1"
                    >
                        Total de Vendas
                    </p>
                    <p class="text-3xl font-bold text-black">
                        {{ formatCurrency(stats.totalSales || 0) }}
                    </p>
                </div>

                <!-- Users Count -->
                <div
                    class="bg-primary/10 border border-primary/20 rounded-xl p-6 flex flex-col items-center justify-center hover:bg-primary/20 transition-all cursor-default"
                >
                    <span
                        class="material-symbols-outlined text-primary text-3xl mb-2"
                    >
                        group
                    </span>
                    <p
                        class="text-text-muted text-sm uppercase tracking-wider mb-1"
                    >
                        Usuários
                    </p>
                    <p
                        class="text-4xl font-bold text-primary"
                        style="text-shadow: 0 0 10px rgba(0, 230, 119, 0.5)"
                    >
                        {{ organizer.users?.length || 0 }}
                    </p>
                </div>
            </div>

            <!-- Bottom Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Users Table -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center justify-between mb-6">
                        <h3
                            class="flex items-center gap-2 text-white font-semibold"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-[20px]"
                                >group</span
                            >
                            Usuários Vinculados
                        </h3>
                        <button
                            @click="openAddUserModal"
                            class="flex items-center gap-1 px-3 py-1.5 bg-primary text-black rounded-lg text-sm font-semibold hover:brightness-110 transition-all"
                        >
                            <span class="material-symbols-outlined text-[18px]"
                                >add</span
                            >
                            Adicionar
                        </button>
                    </div>

                    <!-- Users List -->
                    <div v-if="organizer.users?.length" class="space-y-0">
                        <div
                            class="grid grid-cols-3 gap-4 text-xs text-text-muted uppercase tracking-wider pb-3 border-b border-surface-elevated"
                        >
                            <span>Nome</span>
                            <span>Cargo</span>
                            <span class="text-right">Ações</span>
                        </div>

                        <div
                            v-for="user in organizer.users"
                            :key="user.id"
                            class="grid grid-cols-3 gap-4 items-center py-4 border-b border-surface-elevated last:border-0"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-full bg-surface-elevated flex items-center justify-center text-text-muted text-sm font-medium"
                                >
                                    {{ getInitials(user.name) }}
                                </div>
                                <span class="text-white">{{ user.name }}</span>
                            </div>
                            <div>
                                <span
                                    :class="
                                        getRoleBadgeClass(
                                            user.pivot?.role || 'staff',
                                        )
                                    "
                                >
                                    {{
                                        (
                                            user.pivot?.role || "staff"
                                        ).toUpperCase()
                                    }}
                                </span>
                            </div>
                            <div class="flex justify-end">
                                <button
                                    @click="confirmUnlinkUser(user)"
                                    class="p-1.5 text-text-muted hover:text-red-500 hover:bg-red-500/10 rounded transition-colors"
                                    title="Desvincular usuário"
                                >
                                    <span
                                        class="material-symbols-outlined text-[20px]"
                                        >link_off</span
                                    >
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-8">
                        <span
                            class="material-symbols-outlined text-text-muted text-4xl mb-2"
                        >
                            person_off
                        </span>
                        <p class="text-text-muted">Nenhum usuário vinculado</p>
                    </div>
                </div>

                <!-- Recent Events -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center justify-between mb-6">
                        <h3
                            class="flex items-center gap-2 text-white font-semibold"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-[20px]"
                                >calendar_month</span
                            >
                            Eventos Recentes
                        </h3>
                        <button
                            @click="viewAllEvents"
                            class="text-primary text-sm font-medium hover:underline"
                        >
                            Ver todos
                        </button>
                    </div>

                    <!-- Events List -->
                    <div v-if="recentEvents.length" class="space-y-4">
                        <div
                            v-for="event in recentEvents"
                            :key="event.id"
                            class="flex items-center gap-4"
                        >
                            <!-- Event Image -->
                            <div
                                class="w-16 h-16 rounded-lg bg-surface-elevated flex-shrink-0 overflow-hidden"
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
                                        class="material-symbols-outlined text-text-muted"
                                    >
                                        directions_run
                                    </span>
                                </div>
                            </div>

                            <!-- Event Info -->
                            <div class="flex-1 min-w-0">
                                <h4
                                    class="text-white font-medium truncate uppercase text-sm"
                                >
                                    {{ event.title }}
                                </h4>
                                <p
                                    class="text-text-muted text-xs flex items-center gap-1"
                                >
                                    <span
                                        class="material-symbols-outlined text-[14px]"
                                        >calendar_today</span
                                    >
                                    {{ formatEventDate(event.date_start) }} •
                                    {{ event.city }}
                                </p>
                            </div>

                            <!-- Status -->
                            <span :class="getEventStatusClass(event)">
                                {{ getEventStatusLabel(event) }}
                            </span>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="text-center py-8">
                        <span
                            class="material-symbols-outlined text-text-muted text-4xl mb-2"
                        >
                            event_busy
                        </span>
                        <p class="text-text-muted">Nenhum evento encontrado</p>
                    </div>
                </div>
            </div>

            <!-- Address Section -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h3
                    class="flex items-center gap-2 text-white font-semibold mb-6"
                >
                    <span
                        class="material-symbols-outlined text-primary text-[20px]"
                        >location_on</span
                    >
                    Endereço
                </h3>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider mb-1"
                        >
                            Endereço
                        </p>
                        <p class="text-white">{{ organizer.address || "-" }}</p>
                    </div>

                    <div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider mb-1"
                        >
                            Complemento
                        </p>
                        <p class="text-white">
                            {{ organizer.address_complement || "-" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider mb-1"
                        >
                            Bairro
                        </p>
                        <p class="text-white">
                            {{ organizer.neighborhood || "-" }}
                        </p>
                    </div>

                    <div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider mb-1"
                        >
                            Cidade
                        </p>
                        <p class="text-white">{{ organizer.city || "-" }}</p>
                    </div>

                    <div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider mb-1"
                        >
                            Estado
                        </p>
                        <p class="text-white">{{ organizer.state || "-" }}</p>
                    </div>

                    <div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider mb-1"
                        >
                            CEP
                        </p>
                        <p class="text-white">
                            {{ formatZipCode(organizer.zip_code) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Desvinculação -->
        <Modal
            v-model="showUnlinkModal"
            title="Desvincular Usuário"
            :subtitle="userToUnlink?.name"
        >
            <p class="text-text-secondary">
                Tem certeza que deseja desvincular
                <strong class="text-white">{{ userToUnlink?.name }}</strong>
                deste organizador?
            </p>
            <p class="text-sm text-text-muted mt-2">
                O usuário perderá acesso a este organizador e seus eventos.
            </p>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showUnlinkModal = false"
                        class="px-4 py-2 text-text-muted hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="unlinkUser"
                        :disabled="isUnlinking"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-colors flex items-center gap-2 disabled:opacity-50"
                    >
                        <span
                            v-if="isUnlinking"
                            class="material-symbols-outlined text-[18px] animate-spin"
                            >progress_activity</span
                        >
                        <span
                            v-else
                            class="material-symbols-outlined text-[18px]"
                            >link_off</span
                        >
                        {{ isUnlinking ? "Desvinculando..." : "Desvincular" }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Modal de Confirmação de Exclusão -->
        <Modal
            v-model="showDeleteModal"
            title="Deletar Organizador"
            :subtitle="organizer?.name"
        >
            <p class="text-text-secondary">
                Tem certeza que deseja excluir permanentemente
                <strong class="text-white">{{ organizer?.name }}</strong
                >?
            </p>
            <p class="text-sm text-red-400 mt-2">
                Esta ação não pode ser desfeita. Todos os dados, eventos e
                usuários vinculados serão removidos.
            </p>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-text-muted hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="deleteOrganizer"
                        :disabled="isDeleting"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-colors flex items-center gap-2 disabled:opacity-50"
                    >
                        <span
                            v-if="isDeleting"
                            class="material-symbols-outlined text-[18px] animate-spin"
                            >progress_activity</span
                        >
                        <span
                            v-else
                            class="material-symbols-outlined text-[18px]"
                            >delete</span
                        >
                        {{ isDeleting ? "Excluindo..." : "Excluir" }}
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useOrganizersStore } from "@/stores/organizers";
import { useToast } from "@/composables/useToast";
import api from "@/api/axios";
import ErrorState from "@/components/ui/ErrorState.vue";
import Modal from "@/components/ui/Modal.vue";

const route = useRoute();
const router = useRouter();
const store = useOrganizersStore();
const toast = useToast();

// State
const isLoading = ref(true);
const error = ref(null);
const organizer = ref(null);
const stats = ref({
    totalSales: 0,
});
const recentEvents = ref([]);
const showUnlinkModal = ref(false);
const userToUnlink = ref(null);
const isUnlinking = ref(false);
const showDeleteModal = ref(false);
const isDeleting = ref(false);

// Methods
const fetchOrganizer = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const result = await store.fetchOrganizer(route.params.id);
        if (result.success) {
            organizer.value = result.data;
            recentEvents.value = result.data.events?.slice(0, 3) || [];
            // Total de vendas vem do backend em centavos
            stats.value.totalSales = (result.data.total_sales || 0) / 100;
        } else {
            error.value = result.error;
        }
    } catch (err) {
        error.value = "Erro ao carregar organizador";
    } finally {
        isLoading.value = false;
    }
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const deleteOrganizer = async () => {
    isDeleting.value = true;
    try {
        const result = await store.deleteOrganizer(organizer.value.id);
        if (result.success) {
            toast.success("Organizador excluído com sucesso");
            router.push("/admin/organizers");
        } else {
            toast.error(result.error);
        }
    } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
    }
};

const editOrganizer = () => {
    router.push(`/admin/organizers/${organizer.value.id}/edit`);
};

const openAddUserModal = () => {
    router.push(`/admin/organizers/${organizer.value.id}/users/create`);
};

const confirmUnlinkUser = (user) => {
    userToUnlink.value = user;
    showUnlinkModal.value = true;
};

const unlinkUser = async () => {
    if (!userToUnlink.value) return;

    isUnlinking.value = true;
    try {
        await api.delete(
            `/admin/organizers/${organizer.value.id}/users/${userToUnlink.value.id}`,
        );

        // Remove o usuário da lista local
        organizer.value.users = organizer.value.users.filter(
            (u) => u.id !== userToUnlink.value.id,
        );

        toast.success(
            `${userToUnlink.value.name} foi desvinculado com sucesso`,
        );
        showUnlinkModal.value = false;
        userToUnlink.value = null;
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Erro ao desvincular usuário",
        );
    } finally {
        isUnlinking.value = false;
    }
};

const viewAllEvents = () => {
    // TODO: Navigate to events filtered by organizer
    toast.info("Navegação para eventos será implementada");
};

// Formatters
const formatMemberSince = (date) => {
    if (!date) return "";
    const d = new Date(date);
    const months = [
        "Janeiro",
        "Fevereiro",
        "Março",
        "Abril",
        "Maio",
        "Junho",
        "Julho",
        "Agosto",
        "Setembro",
        "Outubro",
        "Novembro",
        "Dezembro",
    ];
    return `${months[d.getMonth()]} de ${d.getFullYear()}`;
};

const formatDocument = (doc) => {
    if (!doc) return "-";
    const cleaned = doc.replace(/\D/g, "");
    if (cleaned.length === 14) {
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
        return cleaned.replace(/(\d{2})(\d{5})(\d{4})/, "+55 ($1) $2-$3");
    }
    if (cleaned.length === 10) {
        return cleaned.replace(/(\d{2})(\d{4})(\d{4})/, "+55 ($1) $2-$3");
    }
    return phone;
};

const formatZipCode = (zip) => {
    if (!zip) return "-";
    const cleaned = zip.replace(/\D/g, "");
    if (cleaned.length === 8) {
        return cleaned.replace(/(\d{5})(\d{3})/, "$1-$2");
    }
    return zip;
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value);
};

const formatEventDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    return d.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

const getInitials = (name) => {
    if (!name) return "??";
    const parts = name.split(" ");
    if (parts.length === 1) return parts[0].substring(0, 2).toUpperCase();
    return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
};

// Status helpers
const getStatusLabel = (status) => {
    const labels = {
        active: "Ativo",
        inactive: "Inativo",
        blocked: "Bloqueado",
        suspended: "Suspenso",
    };
    return labels[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        active: "px-3 py-1 rounded-full text-xs font-semibold bg-primary text-black",
        inactive:
            "px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-black",
        blocked:
            "px-3 py-1 rounded-full text-xs font-semibold bg-red-500 text-white",
        suspended:
            "px-3 py-1 rounded-full text-xs font-semibold bg-orange-500 text-black",
    };
    return classes[status] || classes.inactive;
};

const getRoleBadgeClass = (role) => {
    const classes = {
        admin: "px-2 py-0.5 rounded text-xs font-semibold bg-primary text-black",
        staff: "px-2 py-0.5 rounded text-xs font-semibold bg-surface-elevated text-text-muted",
    };
    return classes[role] || classes.staff;
};

const getEventStatusLabel = (event) => {
    const now = new Date();
    const start = new Date(event.date_start);
    const end = new Date(event.date_end);

    if (event.status !== "active") return "Inativo";
    if (now < start) return "Em breve";
    if (now >= start && now <= end) return "Em andamento";
    return "Encerrado";
};

const getEventStatusClass = (event) => {
    const now = new Date();
    const start = new Date(event.date_start);
    const end = new Date(event.date_end);

    if (event.status !== "active") {
        return "text-text-muted text-xs";
    }
    if (now < start) {
        return "text-text-muted text-xs";
    }
    if (now >= start && now <= end) {
        return "text-primary text-xs font-medium";
    }
    return "text-text-muted text-xs";
};

// Lifecycle
onMounted(() => {
    fetchOrganizer();
});
</script>

import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/api/axios";
import { USER_ROLE, ORGANIZER_ROLE } from "@/constants/roles";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";
import { STORAGE_KEYS } from "@/constants/storageKeys";

export const useAuthStore = defineStore("auth", () => {
    // State
    const user = ref(null);
    const token = ref(localStorage.getItem(STORAGE_KEYS.AUTH_TOKEN) || null);
    const isLoading = ref(false);
    const error = ref(null);

    // Getters
    const isAuthenticated = computed(() => !!token.value && !!user.value);

    const isSuperAdmin = computed(() => {
        if (!user.value?.roles) return false;
        return user.value.roles.some(
            (role) => role.slug === USER_ROLE.SUPER_ADMIN,
        );
    });

    const hasOrganizers = computed(() => {
        return user.value?.organizers && user.value.organizers.length > 0;
    });

    const userOrganizers = computed(() => user.value?.organizers || []);

    // Organizador atual (primeiro da lista)
    const currentOrganizer = computed(() => {
        return user.value?.organizers?.[0] || null;
    });

    const currentOrganizerId = computed(
        () => currentOrganizer.value?.id || null,
    );

    const currentOrganizerRole = computed(() => {
        return currentOrganizer.value?.pivot?.role || null;
    });

    // Verifica se o usuário é admin do organizador atual
    const isCurrentOrganizerAdmin = computed(() => {
        return currentOrganizerRole.value === ORGANIZER_ROLE.ADMIN;
    });

    // Verifica se o usuário é staff do organizador atual
    const isCurrentOrganizerStaff = computed(() => {
        return currentOrganizerRole.value === ORGANIZER_ROLE.STAFF;
    });

    // Permissão para editar configurações de pagamento
    const canEditPaymentSettings = computed(() => {
        return isSuperAdmin.value || isCurrentOrganizerAdmin.value;
    });

    // Métodos com organizerId específico
    const isOrganizerAdmin = (organizerId) => {
        if (!user.value?.organizers) return false;
        const organizer = user.value.organizers.find(
            (org) => org.id === organizerId,
        );
        return organizer?.pivot?.role === ORGANIZER_ROLE.ADMIN;
    };

    const isOrganizerStaff = (organizerId) => {
        if (!user.value?.organizers) return false;
        const organizer = user.value.organizers.find(
            (org) => org.id === organizerId,
        );
        return organizer?.pivot?.role === ORGANIZER_ROLE.STAFF;
    };

    // Actions
    const login = async (email, password) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post(API_ENDPOINTS.AUTH.LOGIN, {
                email,
                password,
            });
            const { access_token, user: userData } = response.data;

            token.value = access_token;
            user.value = userData;
            localStorage.setItem(STORAGE_KEYS.AUTH_TOKEN, access_token);

            return { success: true };
        } catch (err) {
            error.value = err.response?.data?.message || "Erro ao fazer login";
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    };

    const logout = async () => {
        try {
            await api.post(API_ENDPOINTS.AUTH.LOGOUT);
        } catch (err) {
            console.error("Erro ao fazer logout:", err);
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem(STORAGE_KEYS.AUTH_TOKEN);
        }
    };

    const fetchMe = async () => {
        if (!token.value) return false;

        try {
            const response = await api.get(API_ENDPOINTS.AUTH.ME);
            user.value = response.data;
            return true;
        } catch (err) {
            console.error("Erro ao buscar dados do usuário:", err);
            logout();
            return false;
        }
    };

    const initAuth = async () => {
        if (token.value) {
            await fetchMe();
        }
    };

    return {
        // State
        user,
        token,
        isLoading,
        error,
        // Getters
        isAuthenticated,
        isSuperAdmin,
        hasOrganizers,
        userOrganizers,
        currentOrganizer,
        currentOrganizerId,
        currentOrganizerRole,
        isCurrentOrganizerAdmin,
        isCurrentOrganizerStaff,
        canEditPaymentSettings,
        isOrganizerAdmin,
        isOrganizerStaff,
        // Actions
        login,
        logout,
        fetchMe,
        initAuth,
    };
});

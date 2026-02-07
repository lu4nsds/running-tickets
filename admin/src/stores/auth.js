import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/api/axios";

export const useAuthStore = defineStore("auth", () => {
    // State
    const user = ref(null);
    const token = ref(localStorage.getItem("auth_token") || null);
    const isLoading = ref(false);
    const error = ref(null);

    // Getters
    const isAuthenticated = computed(() => !!token.value && !!user.value);

    const isSuperAdmin = computed(() => {
        if (!user.value?.roles) return false;
        return user.value.roles.some((role) => role.slug === "super_admin");
    });

    const hasOrganizers = computed(() => {
        return user.value?.organizers && user.value.organizers.length > 0;
    });

    const userOrganizers = computed(() => user.value?.organizers || []);

    const isOrganizerAdmin = (organizerId) => {
        if (!user.value?.organizers) return false;
        const organizer = user.value.organizers.find(
            (org) => org.id === organizerId,
        );
        return organizer?.pivot?.role === "admin";
    };

    const isOrganizerStaff = (organizerId) => {
        if (!user.value?.organizers) return false;
        const organizer = user.value.organizers.find(
            (org) => org.id === organizerId,
        );
        return organizer?.pivot?.role === "staff";
    };

    // Actions
    const login = async (email, password) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.post("/auth/login", { email, password });
            const { access_token, user: userData } = response.data;

            token.value = access_token;
            user.value = userData;
            localStorage.setItem("auth_token", access_token);

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
            await api.post("/auth/logout");
        } catch (err) {
            console.error("Erro ao fazer logout:", err);
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem("auth_token");
        }
    };

    const fetchMe = async () => {
        if (!token.value) return false;

        try {
            const response = await api.get("/auth/me");
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
        isOrganizerAdmin,
        isOrganizerStaff,
        // Actions
        login,
        logout,
        fetchMe,
        initAuth,
    };
});

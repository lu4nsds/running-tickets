import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "../api/axios";

export const useAuthStore = defineStore("auth", () => {
    const user = ref(JSON.parse(localStorage.getItem("user")) || null);
    const token = ref(localStorage.getItem("auth_token") || null);
    const loading = ref(false);
    const error = ref(null);

    const isAuthenticated = computed(() => !!token.value);

    async function login(email, password) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post("/auth/login", { email, password, source: "client" });

            token.value = response.data.access_token;
            user.value = response.data.user;

            localStorage.setItem("auth_token", token.value);
            localStorage.setItem("user", JSON.stringify(user.value));

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || "Erro ao fazer login";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function register(data) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post("/auth/register", data);

            token.value = response.data.access_token;
            user.value = response.data.user;

            localStorage.setItem("auth_token", token.value);
            localStorage.setItem("user", JSON.stringify(user.value));

            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || "Erro ao cadastrar";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function logout() {
        try {
            await api.post("/auth/logout");
        } catch (err) {
            console.error("Erro ao fazer logout:", err);
        } finally {
            token.value = null;
            user.value = null;
            localStorage.removeItem("auth_token");
            localStorage.removeItem("user");
        }
    }

    async function fetchUser() {
        if (!token.value) return;

        try {
            const response = await api.get("/auth/me");
            user.value = response.data;
            localStorage.setItem("user", JSON.stringify(user.value));
        } catch (err) {
            console.error("Erro ao buscar usuário:", err);
            logout();
        }
    }

    async function loginWithToken(plainToken) {
        token.value = plainToken;
        localStorage.setItem("auth_token", plainToken);
        await fetchUser();
    }

    return {
        user,
        token,
        loading,
        error,
        isAuthenticated,
        login,
        register,
        logout,
        fetchUser,
        loginWithToken,
    };
});

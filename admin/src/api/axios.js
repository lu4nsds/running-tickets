import axios from "axios";
import { useAuthStore } from "@/stores/auth";
import router from "@/router";

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL || "http://localhost:8000/api",
    headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
    },
});

// Request interceptor - adiciona token
api.interceptors.request.use(
    (config) => {
        const { token } = useAuthStore();
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    },
);

// Response interceptor - trata erros
api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            const { status } = error.response;

            // 401 Unauthorized - token inválido/expirado
            if (status === 401) {
                const authStore = useAuthStore();
                if (authStore.token) {
                    authStore.logout();
                    // /auth/me → guard já trata o redirect com to.fullPath correto
                    // /auth/logout → best-effort, não deve gerar redirect loop
                    const skipRedirect = ['/auth/me', '/auth/logout'].includes(error.config?.url);
                    if (!skipRedirect) {
                        const redirect = router.currentRoute.value.fullPath;
                        router.push({ path: '/login', query: { redirect, reason: 'expired' } });
                    }
                }
            }

            // 403 Forbidden - sem permissão
            if (status === 403) {
                console.error("Acesso negado:", error.response.data);
            }
        }

        return Promise.reject(error);
    },
);

export default api;

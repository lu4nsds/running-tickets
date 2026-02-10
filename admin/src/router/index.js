import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import DashboardLayout from "@/layouts/DashboardLayout.vue";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: "/login",
            name: "login",
            component: () => import("@/views/auth/LoginView.vue"),
            meta: { requiresAuth: false, title: "Login" },
        },
        {
            path: "/",
            redirect: (to) => {
                // Redireciona baseado no tipo de usuário
                const authStore = useAuthStore();
                if (authStore.isSuperAdmin) {
                    return "/admin/dashboard";
                } else if (authStore.hasOrganizers) {
                    return "/organizer/dashboard";
                }
                return "/login";
            },
        },
        // Rotas Organizer
        {
            path: "/organizer",
            component: DashboardLayout,
            meta: { requiresAuth: true },
            children: [
                {
                    path: "dashboard",
                    name: "organizer-dashboard",
                    component: () =>
                        import("@/views/organizer/DashboardView.vue"),
                },
                {
                    path: "events",
                    name: "organizer-events",
                    component: () => import("@/views/organizer/EventsView.vue"),
                },
                {
                    path: "events/:id",
                    name: "organizer-event-detail",
                    component: () =>
                        import("@/views/organizer/EventDetailView.vue"),
                },
                {
                    path: "events/:id/dashboard",
                    name: "organizer-event-dashboard",
                    component: () =>
                        import("@/views/organizer/EventDashboardView.vue"),
                },
                {
                    path: "payment-settings",
                    name: "organizer-payment-settings",
                    component: () =>
                        import("@/views/organizer/PaymentSettingsView.vue"),
                    beforeEnter: (to, from, next) => {
                        const authStore = useAuthStore();
                        // Apenas admin do organizador pode acessar
                        if (!authStore.canEditPaymentSettings) {
                            next({ name: "organizer-dashboard" });
                            return;
                        }
                        next();
                    },
                },
                {
                    path: "events/:id/payout/config",
                    name: "organizer-event-payout-config",
                    component: () =>
                        import("@/views/organizer/PayoutConfigView.vue"),
                    beforeEnter: (to, from, next) => {
                        const authStore = useAuthStore();
                        // Apenas admin do organizador pode acessar
                        if (!authStore.canEditPaymentSettings) {
                            next({ name: "organizer-dashboard" });
                            return;
                        }
                        next();
                    },
                },
            ],
        },
        // Rotas Admin
        {
            path: "/admin",
            component: DashboardLayout,
            meta: { requiresAuth: true },
            children: [
                {
                    path: "dashboard",
                    name: "admin-dashboard",
                    component: () => import("@/views/admin/DashboardView.vue"),
                },
                {
                    path: "organizers",
                    name: "admin-organizers",
                    component: () => import("@/views/admin/DashboardView.vue"),
                },
                {
                    path: "events",
                    name: "admin-events",
                    component: () => import("@/views/admin/DashboardView.vue"),
                },
            ],
        },
    ],
});

// Navigation guard
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const requiresAuth = to.matched.some(
        (record) => record.meta.requiresAuth !== false,
    );

    if (requiresAuth && !authStore.isAuthenticated) {
        // Tenta recuperar sessão do localStorage
        await authStore.initAuth();

        if (!authStore.isAuthenticated) {
            next("/login");
            return;
        }
    }

    if (to.path === "/login" && authStore.isAuthenticated) {
        // Redireciona para dashboard apropriado
        if (authStore.isSuperAdmin) {
            next("/admin/dashboard");
        } else if (authStore.hasOrganizers) {
            next("/organizer/dashboard");
        } else {
            next("/");
        }
        return;
    }

    next();
});

export default router;

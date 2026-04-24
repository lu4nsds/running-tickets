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
            path: "/ativar-conta",
            name: "activate-account",
            component: () => import("@/views/auth/ActivateAccountView.vue"),
            meta: { requiresAuth: false, title: "Ativar Conta" },
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
                {
                    path: "validate-tickets",
                    name: "organizer-validate-tickets-select",
                    component: () =>
                        import("@/views/admin/TicketValidationSelectView.vue"),
                },
                {
                    path: "validate-tickets/:eventId",
                    name: "organizer-validate-tickets",
                    component: () =>
                        import("@/views/admin/TicketValidationView.vue"),
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
                    component: () => import("@/views/admin/OrganizersView.vue"),
                },
                {
                    path: "organizers/create",
                    name: "admin-organizer-create",
                    component: () =>
                        import("@/views/admin/OrganizerCreateView.vue"),
                },
                {
                    path: "organizers/:id",
                    name: "admin-organizer-show",
                    component: () =>
                        import("@/views/admin/OrganizerShowView.vue"),
                },
                {
                    path: "organizers/:id/edit",
                    name: "admin-organizer-edit",
                    component: () =>
                        import("@/views/admin/OrganizerEditView.vue"),
                },
                {
                    path: "organizers/:id/users/create",
                    name: "admin-organizer-user-create",
                    component: () =>
                        import("@/views/admin/OrganizerUserCreateView.vue"),
                },
                {
                    path: "events",
                    name: "admin-events",
                    component: () => import("@/views/admin/EventsView.vue"),
                },
                {
                    path: "events/create",
                    name: "admin-event-create",
                    component: () =>
                        import("@/views/admin/EventCreateView.vue"),
                },
                {
                    path: "events/:id",
                    name: "admin-event-show",
                    component: () => import("@/views/admin/EventShowView.vue"),
                },
                {
                    path: "events/:id/edit",
                    name: "admin-event-edit",
                    component: () => import("@/views/admin/EventEditView.vue"),
                },
                {
                    path: "events/:eventId/categories/create",
                    name: "admin-category-create",
                    component: () =>
                        import("@/views/admin/CategoryFormView.vue"),
                },
                {
                    path: "events/:eventId/categories/:categoryId/edit",
                    name: "admin-category-edit",
                    component: () =>
                        import("@/views/admin/CategoryFormView.vue"),
                },
                {
                    path: "events/:eventId/ticket-types/create",
                    name: "admin-ticket-type-create",
                    component: () =>
                        import("@/views/admin/TicketTypeFormView.vue"),
                },
                {
                    path: "events/:eventId/ticket-types/:ticketTypeId/edit",
                    name: "admin-ticket-type-edit",
                    component: () =>
                        import("@/views/admin/TicketTypeFormView.vue"),
                },
                {
                    path: "validate-tickets",
                    name: "admin-validate-tickets-select",
                    component: () =>
                        import("@/views/admin/TicketValidationSelectView.vue"),
                },
                {
                    path: "validate-tickets/:eventId",
                    name: "admin-validate-tickets",
                    component: () =>
                        import("@/views/admin/TicketValidationView.vue"),
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

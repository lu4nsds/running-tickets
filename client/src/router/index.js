import { createRouter, createWebHistory } from "vue-router";
import HomeView from "../views/HomeView.vue";

const router = createRouter({
    history: createWebHistory(import.meta.env.BASE_URL),
    routes: [
        {
            path: "/",
            name: "home",
            component: HomeView,
        },
        // Página de listagem de eventos com filtros
        {
            path: "/eventos",
            name: "events",
            component: () => import("../views/EventsView.vue"),
        },
        // Event details - usará slug
        {
            path: "/eventos/:slug",
            name: "event-details",
            component: () => import("../views/EventDetailsView.vue"),
        },
        // Checkout - Informações dos participantes
        {
            path: "/checkout",
            name: "checkout",
            component: () => import("../views/CheckoutView.vue"),
        },
        // Pagamento
        {
            path: "/pagamento",
            name: "payment",
            component: () => import("../views/PaymentView.vue"),
        },
        {
            path: "/pagamento/sucesso",
            name: "payment-success",
            component: () => import("../views/PaymentSuccessView.vue"),
        },
        {
            path: "/pagamento/pendente",
            name: "payment-pending",
            component: () => import("../views/PaymentPendingView.vue"),
        },
        {
            path: "/pagamento/erro",
            name: "payment-error",
            component: () => import("../views/PaymentErrorView.vue"),
        },
        // Meus pedidos - usará reference
        {
            path: "/meus-pedidos",
            name: "my-orders",
            component: () => import("../views/MyOrdersView.vue"),
            meta: { requiresAuth: true },
        },
        {
            path: "/meus-pedidos/:reference",
            name: "order-details",
            component: () => import("../views/OrderDetailsView.vue"),
            meta: { requiresAuth: true },
        },
        // Meus ingressos - usará UUID code
        {
            path: "/meus-ingressos",
            name: "my-tickets",
            component: () => import("../views/MyTicketsView.vue"),
            meta: { requiresAuth: true },
        },
        {
            path: "/meus-ingressos/:code",
            name: "ticket-details",
            component: () => import("../views/TicketDetailsView.vue"),
            meta: { requiresAuth: true },
        },
        // Autenticação
        {
            path: "/entrar",
            name: "login",
            component: () => import("../views/LoginView.vue"),
        },
        {
            path: "/cadastro",
            name: "register",
            component: () => import("../views/RegisterView.vue"),
        },
        {
            path: "/auth/callback",
            name: "auth-callback",
            component: () => import("../views/AuthCallbackView.vue"),
        },
    ],
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition;
        } else {
            return { top: 0 };
        }
    },
});

// Navigation guard para rotas protegidas
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem("auth_token");

    if (to.meta.requiresAuth && !token) {
        next({ name: "login", query: { redirect: to.fullPath } });
    } else {
        next();
    }
});

export default router;

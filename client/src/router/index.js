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
        // Checkout - Identificação (apenas para não autenticados)
        {
            path: "/checkout/identificacao",
            name: "checkout-identificacao",
            component: () => import("../views/CheckoutIdentificacaoView.vue"),
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
        // Meus ingressos
        {
            path: "/meus-ingressos",
            name: "my-tickets",
            component: () => import("../views/MyTicketsView.vue"),
            meta: { requiresAuth: true },
        },
        {
            path: "/meus-ingressos/evento/:eventId",
            name: "event-tickets",
            component: () => import("../views/EventTicketsView.vue"),
            meta: { requiresAuth: true },
        },
        // Autenticação
        {
            path: "/saindo",
            name: "logout",
            component: () => import("../views/LogoutView.vue"),
        },
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
        // Redefinição de Senha
        {
            path: "/esqueceu-senha",
            name: "esqueceu-senha",
            component: () => import("../views/ForgotPasswordView.vue"),
        },
        {
            path: "/redefinir-senha",
            name: "redefinir-senha",
            component: () => import("../views/ResetPasswordView.vue"),
        },
        // Verificação de Email
        {
            path: "/verificar-email",
            name: "verificar-email",
            component: () => import("../views/VerifyEmailView.vue"),
            meta: { requiresAuth: true },
        },
        {
            path: "/email/verificar/:id/:hash",
            name: "email-verificado",
            component: () => import("../views/EmailVerifiedView.vue"),
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
router.beforeEach((to) => {
    const token = localStorage.getItem("auth_token");

    if (to.meta.requiresAuth && !token) {
        return { name: "login", query: { redirect: to.fullPath } };
    }

    // Redirecionar para identificação se o usuário não está autenticado
    // e não escolheu continuar como convidado
    if (to.name === "checkout") {
        const guestOk = sessionStorage.getItem("checkoutGuest");
        if (!token && !guestOk) {
            return { name: "checkout-identificacao" };
        }
    }
});

export default router;

/**
 * Endpoints da API
 * Centraliza todos os endpoints para facilitar manutenção
 */
export const API_ENDPOINTS = {
    AUTH: {
        LOGIN: "/auth/login",
        LOGOUT: "/auth/logout",
        ME: "/auth/me",
    },
    ORGANIZER: {
        DASHBOARD: "/organizer/dashboard",
        EVENTS: "/organizer/events",
        PAYMENT_SETTINGS: "/organizer/payment-settings",
        EVENT: {
            DETAIL: (eventId) => `/organizer/events/${eventId}`,
            DASHBOARD: (eventId) => `/organizer/events/${eventId}/dashboard`,
            CATEGORIES: (eventId) => `/organizer/events/${eventId}/categories`,
            TICKET_TYPES: (eventId) =>
                `/organizer/events/${eventId}/ticket-types`,
            PAYOUT: (eventId) => `/organizer/events/${eventId}/payout`,
            VALIDATE_PAYOUT: (eventId) =>
                `/organizer/events/${eventId}/payout/validate`,
        },
        // Manter compatibilidade com código existente
        EVENT_DASHBOARD: (eventId) => `/organizer/events/${eventId}/dashboard`,
    },
    ADMIN: {
        DASHBOARD: "/admin/dashboard",
        EVENT: {
            SET_PAYOUT_MODE: (eventId) =>
                `/admin/events/${eventId}/payout-mode`,
        },
    },
};

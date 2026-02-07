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
        EVENT_DASHBOARD: (eventId) => `/organizer/events/${eventId}/dashboard`,
    },
    ADMIN: {
        DASHBOARD: "/admin/dashboard",
    },
};

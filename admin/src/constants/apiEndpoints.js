/**
 * Endpoints da API
 * Centraliza todos os endpoints para facilitar manutenção
 */
export const API_ENDPOINTS = {
    AUTH: {
        LOGIN:           "/admin/auth/login",
        LOGOUT:          "/admin/auth/logout",
        ME:              "/admin/auth/me",
        ACTIVATE:        "/admin/password/activate",
        FORGOT_PASSWORD: "/admin/password/forgot",
        RESET_PASSWORD:  "/admin/password/reset",
    },
    ORGANIZER: {
        DASHBOARD: "/organizer/dashboard",
        EVENTS:    "/organizer/events",
        EVENT: {
            DETAIL:     (eventId) => `/organizer/events/${eventId}`,
            DASHBOARD:  (eventId) => `/organizer/events/${eventId}/dashboard`,
            CATEGORIES: (eventId) => `/organizer/events/${eventId}/categories`,
            TICKET_TYPES: (eventId) => `/organizer/events/${eventId}/ticket-types`,
        },
        // Manter compatibilidade com código existente
        EVENT_DASHBOARD: (eventId) => `/organizer/events/${eventId}/dashboard`,
    },
    ADMIN: {
        DASHBOARD:           "/admin/dashboard",
        ORGANIZERS:          "/admin/organizers",
        ORGANIZER:           (organizerId) => `/admin/organizers/${organizerId}`,
        ORGANIZER_DASHBOARD: (organizerId) => `/admin/organizers/${organizerId}/dashboard`,
    },
};

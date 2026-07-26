import api from "@/api/axios";

// Conexão OAuth do organizador com o Mercado Pago (split de pagamento).
export const organizerPaymentAccountApi = {
    // Organizador Admin (contexto do próprio organizador)
    status: () => api.get("/organizer/payment-account").then((r) => r.data),
    connect: () =>
        api.post("/organizer/payment-account/connect").then((r) => r.data),
    disconnect: () =>
        api.delete("/organizer/payment-account").then((r) => r.data),

    // Super admin: status de conexão de um organizador específico — usado no
    // formulário de evento para habilitar/desabilitar o modo Split.
    statusForOrganizer: (organizerId) =>
        api
            .get(`/admin/organizers/${organizerId}/payment-account`)
            .then((r) => r.data),
};

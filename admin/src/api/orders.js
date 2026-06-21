import api from './axios'

export const adminOrdersApi = {
    // Pedidos de um evento
    getEventOrders: (eventId, params = {}) =>
        api.get(`/admin/events/${eventId}/orders`, { params }).then((r) => r.data),

    // Cancelamentos/estornos de um evento
    getCancellations: (eventId, params = {}) =>
        api
            .get(`/admin/events/${eventId}/cancellations`, { params })
            .then((r) => r.data),

    // Aprova o cancelamento e executa o estorno no gateway
    approveCancellation: (id) =>
        api.post(`/admin/cancellations/${id}/approve`).then((r) => r.data),

    // Rejeita o cancelamento
    rejectCancellation: (id, payload = {}) =>
        api.post(`/admin/cancellations/${id}/reject`, payload).then((r) => r.data),
}

import api from './axios'

export const adminEventsApi = {
    exportParticipants: (eventId) =>
        api.get(`/admin/events/${eventId}/report`, { responseType: 'blob' }),
}

export const organizerEventsApi = {
    exportParticipants: (eventId) =>
        api.get(`/organizer/events/${eventId}/report`, { responseType: 'blob' }),
}

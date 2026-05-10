import api from '@/api/axios'

export const whatsappApi = {
  connect:    () => api.post('/admin/whatsapp/connect').then(r => r.data),
  status:     () => api.get('/admin/whatsapp/status').then(r => r.data),
  disconnect: () => api.delete('/admin/whatsapp/session'),
}

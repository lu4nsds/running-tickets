import api from '@/api/axios'

export const settingsApi = {
  get:    ()        => api.get('/admin/settings').then(r => r.data.data),
  update: (payload) => api.put('/admin/settings', payload).then(r => r.data.data),
}

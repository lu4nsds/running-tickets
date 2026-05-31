import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCheckoutStore = defineStore('checkout', () => {
  const checkoutData = ref(null)
  const participants = ref([])

  const hasCheckoutData = computed(() => !!checkoutData.value)

  const isCheckoutIntact = computed(() => {
    if (!checkoutData.value || !participants.value.length) return true
    const validIds = new Set(checkoutData.value.tickets.map(t => t.id))
    return participants.value.every(p => validIds.has(p.ticket_type_id))
  })

  function setCheckoutData(data) {
    checkoutData.value = data
  }

  function setParticipants(list) {
    participants.value = list
  }

  function clearCheckout() {
    checkoutData.value = null
    participants.value = []
  }

  return {
    checkoutData,
    participants,
    hasCheckoutData,
    isCheckoutIntact,
    setCheckoutData,
    setParticipants,
    clearCheckout,
  }
})

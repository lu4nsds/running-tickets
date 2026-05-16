import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

export const useCheckoutStore = defineStore('checkout', () => {
  const checkoutData = ref(null)
  const participants = ref([])

  const paymentOrder = ref(
    JSON.parse(sessionStorage.getItem('payment_order') || 'null')
  )

  const hasCheckoutData = computed(() => !!checkoutData.value)
  const hasPaymentOrder = computed(() => !!paymentOrder.value)

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

  function setPaymentOrder(order) {
    paymentOrder.value = order
    sessionStorage.setItem('payment_order', JSON.stringify(order))
  }

  function clearCheckout() {
    checkoutData.value = null
    participants.value = []
    paymentOrder.value = null
    sessionStorage.removeItem('payment_order')
  }

  return {
    checkoutData,
    participants,
    paymentOrder,
    hasCheckoutData,
    hasPaymentOrder,
    isCheckoutIntact,
    setCheckoutData,
    setParticipants,
    setPaymentOrder,
    clearCheckout,
  }
})

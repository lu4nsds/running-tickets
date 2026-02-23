import { defineStore } from "pinia";
import { ref } from "vue";
import api from "../api/axios";

export const useOrdersStore = defineStore("orders", () => {
    const orders = ref([]);
    const currentOrder = ref(null);
    const loading = ref(false);
    const error = ref(null);

    async function fetchOrders() {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get("/orders");
            orders.value = response.data.data;
            return response.data;
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar pedidos";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchOrderByReference(reference) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/orders/${reference}`);
            currentOrder.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar pedido";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function createOrder(orderData) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post("/orders", orderData);
            return response.data;
        } catch (err) {
            error.value = err.response?.data?.message || "Erro ao criar pedido";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function cancelOrder(reference) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.post(`/orders/${reference}/cancel`);

            // Atualiza na lista se existir
            const index = orders.value.findIndex(
                (o) => o.reference === reference,
            );
            if (index !== -1) {
                orders.value[index] = response.data.order;
            }

            // Atualiza current se for o mesmo
            if (currentOrder.value?.reference === reference) {
                currentOrder.value = response.data.order;
            }

            return response.data;
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao cancelar pedido";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    function clearCurrentOrder() {
        currentOrder.value = null;
    }

    return {
        orders,
        currentOrder,
        loading,
        error,
        fetchOrders,
        fetchOrderByReference,
        createOrder,
        cancelOrder,
        clearCurrentOrder,
    };
});

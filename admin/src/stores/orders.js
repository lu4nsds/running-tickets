import { defineStore } from "pinia";
import { ref } from "vue";
import { adminOrdersApi } from "@/api/orders";

export const useOrdersStore = defineStore("orders", () => {
    // State
    const orders = ref([]);
    const cancellations = ref([]);
    const ordersPagination = ref({ currentPage: 1, lastPage: 1, perPage: 6, total: 0 });
    const cancellationsPagination = ref({ currentPage: 1, lastPage: 1, perPage: 6, total: 0 });
    const isLoading = ref(false);
    const error = ref(null);

    const PER_PAGE = 6;

    function mapPagination(meta) {
        return {
            currentPage: meta?.current_page ?? 1,
            lastPage: meta?.last_page ?? 1,
            perPage: meta?.per_page ?? PER_PAGE,
            total: meta?.total ?? 0,
        };
    }

    async function fetchEventOrders(eventId, page = 1, search = "") {
        isLoading.value = true;
        error.value = null;
        try {
            const params = { page, per_page: PER_PAGE };
            if (search) params.search = search;
            const data = await adminOrdersApi.getEventOrders(eventId, params);
            orders.value = data.data;
            ordersPagination.value = mapPagination(data.meta);
            return data;
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar pedidos.";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchCancellations(eventId, page = 1, search = "") {
        isLoading.value = true;
        error.value = null;
        try {
            const params = { page, per_page: PER_PAGE };
            if (search) params.search = search;
            const data = await adminOrdersApi.getCancellations(eventId, params);
            cancellations.value = data.data;
            cancellationsPagination.value = mapPagination(data.meta);
            return data;
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                "Erro ao carregar cancelamentos.";
            throw err;
        } finally {
            isLoading.value = false;
        }
    }

    async function approveCancellation(id) {
        const data = await adminOrdersApi.approveCancellation(id);
        applyCancellationUpdate(data.cancellation);
        return data;
    }

    async function rejectCancellation(id, reviewNotes) {
        const data = await adminOrdersApi.rejectCancellation(id, {
            review_notes: reviewNotes,
        });
        applyCancellationUpdate(data.cancellation);
        return data;
    }

    function applyCancellationUpdate(updated) {
        if (!updated) return;
        const index = cancellations.value.findIndex((c) => c.id === updated.id);
        if (index !== -1) {
            cancellations.value[index] = updated;
        }
    }

    return {
        orders,
        cancellations,
        ordersPagination,
        cancellationsPagination,
        isLoading,
        error,
        fetchEventOrders,
        fetchCancellations,
        approveCancellation,
        rejectCancellation,
    };
});

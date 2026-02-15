import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/api/axios";

export const useOrganizersStore = defineStore("organizers", () => {
    // State
    const organizers = ref([]);
    const pagination = ref({
        currentPage: 1,
        lastPage: 1,
        perPage: 6,
        total: 0,
    });
    const isLoading = ref(false);
    const error = ref(null);
    const filters = ref({
        search: "",
        status: "",
    });

    // Getters
    const hasOrganizers = computed(() => organizers.value.length > 0);
    const isEmpty = computed(
        () => !isLoading.value && organizers.value.length === 0,
    );

    // Actions
    const fetchOrganizers = async (page = 1) => {
        isLoading.value = true;
        error.value = null;

        try {
            const params = {
                page,
                per_page: pagination.value.perPage,
            };

            if (filters.value.search) {
                params.search = filters.value.search;
            }
            if (filters.value.status) {
                params.status = filters.value.status;
            }

            const response = await api.get("/admin/organizers", { params });

            organizers.value = response.data.data;
            pagination.value = {
                currentPage: response.data.meta?.current_page || 1,
                lastPage: response.data.meta?.last_page || 1,
                perPage: response.data.meta?.per_page || 6,
                total: response.data.meta?.total || 0,
            };

            return { success: true };
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar organizadores";
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    };

    const deleteOrganizer = async (id) => {
        try {
            await api.delete(`/admin/organizers/${id}`);
            organizers.value = organizers.value.filter((o) => o.id !== id);
            return { success: true };
        } catch (err) {
            return {
                success: false,
                error:
                    err.response?.data?.message ||
                    "Erro ao deletar organizador",
            };
        }
    };

    const fetchOrganizer = async (id) => {
        try {
            const response = await api.get(`/admin/organizers/${id}`);
            return { success: true, data: response.data.data };
        } catch (err) {
            return {
                success: false,
                error:
                    err.response?.data?.message ||
                    "Erro ao carregar organizador",
            };
        }
    };

    const createOrganizer = async (data) => {
        try {
            const response = await api.post("/admin/organizers", data);
            return { success: true, data: response.data.data };
        } catch (err) {
            return {
                success: false,
                error:
                    err.response?.data?.message || "Erro ao criar organizador",
                errors: err.response?.data?.errors,
            };
        }
    };

    const updateOrganizer = async (id, data) => {
        try {
            const response = await api.put(`/admin/organizers/${id}`, data);
            return { success: true, data: response.data.data };
        } catch (err) {
            return {
                success: false,
                error:
                    err.response?.data?.message ||
                    "Erro ao atualizar organizador",
                errors: err.response?.data?.errors,
            };
        }
    };

    const setFilters = (newFilters) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    const clearFilters = () => {
        filters.value = { search: "", status: "" };
    };

    return {
        // State
        organizers,
        pagination,
        isLoading,
        error,
        filters,
        // Getters
        hasOrganizers,
        isEmpty,
        // Actions
        fetchOrganizers,
        fetchOrganizer,
        createOrganizer,
        updateOrganizer,
        deleteOrganizer,
        setFilters,
        clearFilters,
    };
});

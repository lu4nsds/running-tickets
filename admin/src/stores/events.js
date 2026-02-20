import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/api/axios";

export const useEventsStore = defineStore("events", () => {
    // State
    const events = ref([]);
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
        organizer_id: "",
    });

    // Getters
    const hasEvents = computed(() => events.value.length > 0);
    const isEmpty = computed(
        () => !isLoading.value && events.value.length === 0,
    );

    // Actions
    const fetchEvents = async (page = 1) => {
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
            if (filters.value.organizer_id) {
                params.organizer_id = filters.value.organizer_id;
            }

            const response = await api.get("/admin/events", { params });

            events.value = response.data.data;
            pagination.value = {
                currentPage: response.data.meta?.current_page || 1,
                lastPage: response.data.meta?.last_page || 1,
                perPage: response.data.meta?.per_page || 6,
                total: response.data.meta?.total || 0,
            };

            return { success: true };
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar eventos";
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    };

    const fetchEvent = async (id) => {
        try {
            const response = await api.get(`/admin/events/${id}`);
            return { success: true, data: response.data.data };
        } catch (err) {
            return {
                success: false,
                error: err.response?.data?.message || "Erro ao carregar evento",
            };
        }
    };

    const createEvent = async (data) => {
        try {
            // Se tem arquivo, usar FormData
            let payload = data;
            let config = {};

            if (data instanceof FormData) {
                config = {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                };
            }

            const response = await api.post("/admin/events", payload, config);
            return { success: true, data: response.data.data };
        } catch (err) {
            return {
                success: false,
                error: err.response?.data?.message || "Erro ao criar evento",
                errors: err.response?.data?.errors,
            };
        }
    };

    const updateEvent = async (id, data) => {
        try {
            let payload = data;
            let config = {};

            if (data instanceof FormData) {
                // Laravel não aceita PUT com FormData, usar POST com _method
                data.append("_method", "PUT");
                config = {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                };
                const response = await api.post(
                    `/admin/events/${id}`,
                    payload,
                    config,
                );
                return { success: true, data: response.data.data };
            } else {
                const response = await api.put(`/admin/events/${id}`, payload);
                return { success: true, data: response.data.data };
            }
        } catch (err) {
            return {
                success: false,
                error:
                    err.response?.data?.message || "Erro ao atualizar evento",
                errors: err.response?.data?.errors,
            };
        }
    };

    const deleteEvent = async (id) => {
        try {
            await api.delete(`/admin/events/${id}`);
            events.value = events.value.filter((e) => e.id !== id);
            return { success: true };
        } catch (err) {
            return {
                success: false,
                error: err.response?.data?.message || "Erro ao deletar evento",
            };
        }
    };

    const fetchOrganizers = async () => {
        try {
            const response = await api.get("/admin/organizers", {
                params: { per_page: 100 },
            });
            return { success: true, data: response.data.data };
        } catch (err) {
            return {
                success: false,
                error:
                    err.response?.data?.message ||
                    "Erro ao carregar organizadores",
            };
        }
    };

    const setFilters = (newFilters) => {
        filters.value = { ...filters.value, ...newFilters };
    };

    const clearFilters = () => {
        filters.value = { search: "", status: "", organizer_id: "" };
    };

    return {
        // State
        events,
        pagination,
        isLoading,
        error,
        filters,
        // Getters
        hasEvents,
        isEmpty,
        // Actions
        fetchEvents,
        fetchEvent,
        createEvent,
        updateEvent,
        deleteEvent,
        fetchOrganizers,
        setFilters,
        clearFilters,
    };
});

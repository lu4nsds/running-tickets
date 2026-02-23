import { defineStore } from "pinia";
import { ref } from "vue";
import api from "../api/axios";

export const useEventsStore = defineStore("events", () => {
    const events = ref([]);
    const currentEvent = ref(null);
    const loading = ref(false);
    const error = ref(null);
    const pagination = ref({
        current_page: 1,
        last_page: 1,
        per_page: 15,
        total: 0,
    });

    async function fetchEvents(params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get("/events", { params });
            events.value = response.data.data;

            if (response.data.meta) {
                pagination.value = {
                    current_page: response.data.meta.current_page,
                    last_page: response.data.meta.last_page,
                    per_page: response.data.meta.per_page,
                    total: response.data.meta.total,
                };
            }

            return response.data;
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar eventos";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    async function fetchEventBySlug(slug) {
        loading.value = true;
        error.value = null;
        try {
            const response = await api.get(`/events/${slug}`);
            currentEvent.value = response.data.data;
            return response.data.data;
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar evento";
            throw err;
        } finally {
            loading.value = false;
        }
    }

    function clearCurrentEvent() {
        currentEvent.value = null;
    }

    return {
        events,
        currentEvent,
        loading,
        error,
        pagination,
        fetchEvents,
        fetchEventBySlug,
        clearCurrentEvent,
    };
});

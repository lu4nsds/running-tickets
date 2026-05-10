import { defineStore } from "pinia";
import { ref, computed } from "vue";
import api from "@/api/axios";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";

export const useDashboardStore = defineStore("dashboard", () => {
    // State
    const data = ref(null);
    const isLoading = ref(false);
    const error = ref(null);
    const dateFilter = ref({
        preset: "current_month",
        startDate: null,
        endDate: null,
    });

    // Getters (computed)
    const summary        = computed(() => data.value?.summary || {});
    const topOrganizers  = computed(() => data.value?.top_organizers || []);
    const platformHealth = computed(() => data.value?.platform_health || {});
    const salesTrend     = computed(() => data.value?.sales_trend || []);
    const alerts         = computed(() => data.value?.alerts || []);
    const appliedFilters = computed(() => data.value?.applied_filters || null);

    // Stubs — payout breakdown removed; kept so template doesn't break (always empty)
    const payoutPercentages = computed(() => []);
    const donutSegments     = computed(() => []);
    const pendingPayouts    = computed(() => []);
    const top4PendingPayouts = computed(() => []);

    // Computed: Top 4 organizadores
    const top4Organizers = computed(() => topOrganizers.value.slice(0, 4));

    // Computed: Alertas críticos
    const criticalAlerts = computed(() => {
        return alerts.value
            .filter((alert) => alert.severity === "high")
            .slice(0, 3);
    });

    // Computed: Eventos próximos
    const upcomingEvents = computed(() => data.value?.upcoming_events || []);

    // Computed: Label de eventos ativos
    const activeEventsLabel = computed(() => {
        const active = summary.value.active_events || 0;
        return `${active} Ativos`;
    });

    // Computed: Dados do gráfico de linha (SVG path)
    const chartPath = computed(() => {
        if (!salesTrend.value || salesTrend.value.length === 0) {
            return { area: "", line: "" };
        }

        const maxRevenue = Math.max(
            ...salesTrend.value.map((d) => parseFloat(d.revenue)),
        );
        const points = salesTrend.value.map((d, i) => {
            const x = (i / (salesTrend.value.length - 1)) * 800;
            const y = 200 - (parseFloat(d.revenue) / maxRevenue) * 160;
            return { x, y, date: d.date, revenue: d.revenue };
        });

        const areaPoints = points.map((p) => `${p.x},${p.y}`).join(" L");
        const area = `M0,200 L${areaPoints} L800,200 Z`;
        const line = `M${points.map((p) => `${p.x},${p.y}`).join(" L")}`;

        return { area, line, points };
    });

    // Actions
    const fetchPlatformDashboard = async (filterParams = null) => {
        isLoading.value = true;
        error.value = null;

        const params = filterParams || dateFilter.value;
        const queryParams = {};

        if (params.preset && params.preset !== "custom") {
            queryParams.preset = params.preset;
        } else if (
            params.preset === "custom" &&
            params.startDate &&
            params.endDate
        ) {
            queryParams.preset = "custom";
            queryParams.start_date = params.startDate;
            queryParams.end_date = params.endDate;
        }

        try {
            const response = await api.get(API_ENDPOINTS.ADMIN.DASHBOARD, {
                params: queryParams,
            });
            data.value = response.data;
            return { success: true, data: response.data };
        } catch (err) {
            error.value =
                err.response?.data?.message || "Erro ao carregar dashboard";
            console.error("Erro ao buscar dashboard:", err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    };

    const setDateFilter = async (filter) => {
        dateFilter.value = filter;
        await fetchPlatformDashboard(filter);
    };

    const fetchOrganizerDashboard = async (organizerId) => {
        isLoading.value = true;
        error.value = null;

        try {
            const response = await api.get(
                API_ENDPOINTS.ADMIN.ORGANIZER_DASHBOARD(organizerId),
            );
            data.value = response.data;
            return { success: true, data: response.data };
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                "Erro ao carregar dashboard do organizador";
            console.error("Erro ao buscar dashboard do organizador:", err);
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    };

    const clearError = () => {
        error.value = null;
    };

    return {
        // State
        data,
        isLoading,
        error,
        dateFilter,
        // Getters
        summary,
        topOrganizers,
        payoutPercentages,
        donutSegments,
        pendingPayouts,
        top4PendingPayouts,
        platformHealth,
        salesTrend,
        alerts,
        appliedFilters,
        top4Organizers,
        criticalAlerts,
        upcomingEvents,
        activeEventsLabel,
        chartPath,
        // Actions
        fetchPlatformDashboard,
        fetchOrganizerDashboard,
        setDateFilter,
        clearError,
    };
});

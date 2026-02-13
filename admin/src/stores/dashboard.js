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
    const summary = computed(() => data.value?.summary || {});
    const topOrganizers = computed(() => data.value?.top_organizers || []);
    const payoutBreakdown = computed(() => data.value?.payout_breakdown || []);
    const pendingPayouts = computed(() => data.value?.pending_payouts || []);
    const platformHealth = computed(() => data.value?.platform_health || {});
    const salesTrend = computed(() => data.value?.sales_trend || []);
    const alerts = computed(() => data.value?.alerts || []);
    const appliedFilters = computed(() => data.value?.applied_filters || null);

    // Computed: Calcular percentuais do breakdown financeiro
    const payoutPercentages = computed(() => {
        if (!payoutBreakdown.value || payoutBreakdown.value.length === 0) {
            return [];
        }

        const totalRevenue = payoutBreakdown.value.reduce(
            (sum, item) => sum + parseFloat(item.revenue),
            0,
        );

        return payoutBreakdown.value.map((item) => ({
            ...item,
            percentage:
                totalRevenue > 0
                    ? ((parseFloat(item.revenue) / totalRevenue) * 100).toFixed(
                          0,
                      )
                    : 0,
        }));
    });

    // Computed: Gerar segmentos do donut chart
    const donutSegments = computed(() => {
        if (!payoutPercentages.value || payoutPercentages.value.length === 0) {
            return [];
        }

        const colors = ["#00E676", "#3B82F6", "#A855F7", "#F59E0B", "#EF4444"];
        const radius = 70;
        const innerRadius = 50;
        let currentAngle = -90; // Start at top

        return payoutPercentages.value.map((item, index) => {
            const percentage = parseFloat(item.percentage);
            const angle = (percentage / 100) * 360;
            const startAngle = currentAngle;
            const endAngle = currentAngle + angle;

            // Convert to radians
            const startRad = (startAngle * Math.PI) / 180;
            const endRad = (endAngle * Math.PI) / 180;

            // Calculate path for donut segment
            const x1 = Math.cos(startRad) * radius;
            const y1 = Math.sin(startRad) * radius;
            const x2 = Math.cos(endRad) * radius;
            const y2 = Math.sin(endRad) * radius;
            const x3 = Math.cos(endRad) * innerRadius;
            const y3 = Math.sin(endRad) * innerRadius;
            const x4 = Math.cos(startRad) * innerRadius;
            const y4 = Math.sin(startRad) * innerRadius;

            const largeArc = angle > 180 ? 1 : 0;

            const path = [
                `M ${x1} ${y1}`,
                `A ${radius} ${radius} 0 ${largeArc} 1 ${x2} ${y2}`,
                `L ${x3} ${y3}`,
                `A ${innerRadius} ${innerRadius} 0 ${largeArc} 0 ${x4} ${y4}`,
                "Z",
            ].join(" ");

            // Calculate label position (middle of segment, at radius + 15)
            const midAngle = (startAngle + endAngle) / 2;
            const midRad = (midAngle * Math.PI) / 180;
            const labelRadius = radius + 15;
            const labelX = Math.cos(midRad) * labelRadius;
            const labelY = Math.sin(midRad) * labelRadius;

            currentAngle = endAngle;

            return {
                path,
                color: colors[index % colors.length],
                percentage: item.percentage,
                revenue: item.revenue,
                labelX,
                labelY,
            };
        });
    });

    // Computed: Top 4 organizadores
    const top4Organizers = computed(() => topOrganizers.value.slice(0, 4));

    // Computed: Top 4 pagamentos pendentes
    const top4PendingPayouts = computed(() => pendingPayouts.value.slice(0, 4));

    // Computed: Alertas críticos
    const criticalAlerts = computed(() => {
        return alerts.value
            .filter((alert) => alert.severity === "high")
            .slice(0, 3);
    });

    // Computed: Eventos próximos (próximos 10 eventos ordenados por data)
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

        // Criar path da área
        const areaPoints = points.map((p) => `${p.x},${p.y}`).join(" L");
        const area = `M0,200 L${areaPoints} L800,200 Z`;

        // Criar path da linha
        const line = `M${points.map((p) => `${p.x},${p.y}`).join(" L")}`;

        return { area, line, points };
    });

    // Actions
    const fetchPlatformDashboard = async (filterParams = null) => {
        isLoading.value = true;
        error.value = null;

        // Usar filtros passados ou os do state
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
        payoutBreakdown,
        pendingPayouts,
        platformHealth,
        salesTrend,
        alerts,
        appliedFilters,
        payoutPercentages,
        donutSegments,
        top4Organizers,
        top4PendingPayouts,
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

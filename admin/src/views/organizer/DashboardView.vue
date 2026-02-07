<template>
    <div class="space-y-6">
        <!-- Organizer Header -->
        <div
            class="bg-card-bg border border-surface-elevated rounded-xl p-6 flex items-center justify-between"
        >
            <div class="flex items-center gap-4">
                <!-- Logo -->
                <div
                    class="w-16 h-16 bg-surface rounded-lg flex items-center justify-center"
                >
                    <span
                        class="material-symbols-outlined text-primary text-3xl"
                    >
                        directions_run
                    </span>
                </div>
                <!-- Info -->
                <div>
                    <h1 class="text-2xl font-bold text-white">
                        {{ organizerName }}
                    </h1>
                    <p class="text-text-muted text-sm">
                        {{ organizerEmail }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Loading State -->
        <div v-if="isLoading" class="flex items-center justify-center py-20">
            <div class="text-center">
                <div
                    class="inline-block w-8 h-8 border-4 border-primary border-t-transparent rounded-full animate-spin mb-4"
                ></div>
                <p class="text-text-muted">Carregando dados...</p>
            </div>
        </div>

        <!-- Dashboard Content -->
        <div v-else class="space-y-6">
            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Total Events -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-text-muted text-sm mb-1">
                                Total de Eventos
                            </p>
                            <p class="text-3xl font-bold text-white">
                                {{ dashboardData.summary?.total_events || 0 }}
                            </p>
                        </div>
                        <span
                            class="material-symbols-outlined text-primary text-2xl"
                        >
                            event
                        </span>
                    </div>
                    <div class="flex items-center gap-1 text-sm">
                        <span class="text-text-muted">
                            {{ dashboardData.summary?.active_events || 0 }}
                            ativos
                        </span>
                    </div>
                </div>

                <!-- Total Orders -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-text-muted text-sm mb-1">
                                Total de Pedidos
                            </p>
                            <p class="text-3xl font-bold text-white">
                                {{
                                    formatNumber(
                                        (dashboardData.summary
                                            ?.total_paid_orders || 0) +
                                            (dashboardData.summary
                                                ?.total_pending_orders || 0),
                                    )
                                }}
                            </p>
                        </div>
                        <span
                            class="material-symbols-outlined text-primary text-2xl"
                        >
                            shopping_cart
                        </span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="text-primary">
                            {{
                                formatNumber(
                                    dashboardData.summary?.total_paid_orders ||
                                        0,
                                )
                            }}
                            pagos
                        </span>
                        <span class="text-text-muted">•</span>
                        <span class="text-yellow-400">
                            {{
                                formatNumber(
                                    dashboardData.summary
                                        ?.total_pending_orders || 0,
                                )
                            }}
                            pendentes
                        </span>
                    </div>
                </div>

                <!-- Revenue -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-start justify-between mb-4">
                        <div>
                            <p class="text-text-muted text-sm mb-1">
                                Receita Total
                            </p>
                            <p class="text-3xl font-bold text-white">
                                {{
                                    formatCurrency(
                                        dashboardData.summary?.total_revenue ||
                                            0,
                                    )
                                }}
                            </p>
                        </div>
                        <span
                            class="material-symbols-outlined text-primary text-2xl"
                        >
                            trending_up
                        </span>
                    </div>
                </div>
            </div>

            <!-- Charts Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Sales History Chart -->
                <div
                    class="lg:col-span-2 bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h3 class="text-lg font-semibold text-white">
                                Histórico de Vendas
                            </h3>
                            <p class="text-sm text-text-muted">
                                Últimos 7 dias
                            </p>
                        </div>
                    </div>
                    <apexchart
                        type="line"
                        height="280"
                        :options="chartOptions"
                        :series="chartSeries"
                    ></apexchart>
                </div>

                <!-- Performance Metrics -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <h3 class="text-lg font-semibold text-white mb-6">
                        Métricas de Performance
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-text-muted mb-1">
                                Taxa Média de Ocupação
                            </p>
                            <p class="text-2xl font-bold text-primary">
                                {{ averageOccupancyRate }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-text-muted mb-1">
                                Taxa Média de Conversão
                            </p>
                            <p class="text-xl font-semibold text-white">
                                {{ averageConversionRate }}%
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-text-muted mb-1">
                                Ticket Médio
                            </p>
                            <p class="text-xl font-semibold text-white">
                                {{ formatCurrency(averageTicketValue) }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ticket Types Performance -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <div class="flex justify-between items-center mb-6">
                    <div>
                        <h3 class="text-lg font-semibold text-white">
                            Performance por Tipo de Ingresso
                        </h3>
                        <p class="text-sm text-text-muted">
                            Top 10 ingressos mais vendidos
                        </p>
                    </div>
                </div>
                <apexchart
                    type="bar"
                    height="350"
                    :options="ticketTypesChartOptions"
                    :series="ticketTypesChartSeries"
                ></apexchart>
            </div>

            <!-- Events Overview -->
            <div
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-white">
                        Visão Geral dos Eventos
                    </h3>
                    <a
                        href="#"
                        class="text-primary text-sm hover:text-primary/80 transition-colors flex items-center gap-1"
                    >
                        Ver Todos
                        <span class="material-symbols-outlined text-[16px]">
                            arrow_forward
                        </span>
                    </a>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="border-b border-surface-elevated text-left"
                            >
                                <th
                                    class="pb-3 text-xs font-semibold text-text-muted uppercase tracking-wider"
                                >
                                    Nome do Evento
                                </th>
                                <th
                                    class="pb-3 text-xs font-semibold text-text-muted uppercase tracking-wider"
                                >
                                    Data
                                </th>
                                <th
                                    class="pb-3 text-xs font-semibold text-text-muted uppercase tracking-wider"
                                >
                                    Receita
                                </th>
                                <th
                                    class="pb-3 text-xs font-semibold text-text-muted uppercase tracking-wider"
                                >
                                    Pedidos
                                </th>
                                <th
                                    class="pb-3 text-xs font-semibold text-text-muted uppercase tracking-wider"
                                >
                                    Ingressos Vendidos
                                </th>
                                <th
                                    class="pb-3 text-xs font-semibold text-text-muted uppercase tracking-wider"
                                >
                                    Ocupação
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-surface-elevated">
                            <tr
                                v-for="event in dashboardData.top_events"
                                :key="event.id"
                                class="hover:bg-surface/30 transition-colors"
                            >
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-2 h-2 rounded-full bg-primary"
                                        ></div>
                                        <span class="text-white font-medium">
                                            {{ event.name }}
                                        </span>
                                    </div>
                                </td>
                                <td class="py-4 text-text-secondary text-sm">
                                    {{ formatDate(event.date) }}
                                </td>
                                <td class="py-4">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="material-symbols-outlined text-primary text-[16px]"
                                        >
                                            payments
                                        </span>
                                        <span class="text-white font-medium">
                                            {{ formatCurrency(event.revenue) }}
                                        </span>
                                    </div>
                                </td>
                                <td
                                    class="py-4 text-text-secondary text-sm font-medium"
                                >
                                    {{ event.orders }}
                                </td>
                                <td class="py-4">
                                    <span class="text-white font-medium">
                                        {{ event.tickets_sold }} /
                                        {{ event.capacity }}
                                    </span>
                                </td>
                                <td class="py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex-1 bg-surface rounded-full h-2 max-w-[100px]"
                                        >
                                            <div
                                                class="bg-primary h-2 rounded-full"
                                                :style="{
                                                    width: `${event.occupancy_rate}%`,
                                                }"
                                            ></div>
                                        </div>
                                        <span
                                            class="text-text-secondary text-sm font-medium"
                                        >
                                            {{ event.occupancy_rate }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import api from "@/api/axios";
import VueApexCharts from "vue3-apexcharts";

const authStore = useAuthStore();

const isLoading = ref(true);
const dashboardData = ref({
    summary: {},
    top_events: [],
    sales_trend: [],
});

// Computed properties
const organizerName = computed(() => {
    return authStore.userOrganizers[0]?.name || "Organizador";
});

const organizerEmail = computed(() => {
    return authStore.userOrganizers[0]?.email || "";
});

const averageOccupancyRate = computed(() => {
    if (
        !dashboardData.value.top_events ||
        dashboardData.value.top_events.length === 0
    ) {
        return 0;
    }
    const total = dashboardData.value.top_events.reduce(
        (sum, event) => sum + (event.occupancy_rate || 0),
        0,
    );
    return (total / dashboardData.value.top_events.length).toFixed(1);
});

const averageConversionRate = computed(() => {
    if (
        !dashboardData.value.top_events ||
        dashboardData.value.top_events.length === 0
    ) {
        return 0;
    }
    const total = dashboardData.value.top_events.reduce(
        (sum, event) => sum + (event.conversion_rate || 0),
        0,
    );
    return (total / dashboardData.value.top_events.length).toFixed(1);
});

const averageTicketValue = computed(() => {
    const revenue = dashboardData.value.summary?.total_revenue || 0;
    const orders = dashboardData.value.summary?.total_paid_orders || 0;
    return orders > 0 ? revenue / orders : 0;
});

// Chart configuration
const chartOptions = ref({
    chart: {
        type: "line",
        toolbar: { show: false },
        background: "transparent",
        fontFamily: "Inter, sans-serif",
    },
    theme: {
        mode: "dark",
    },
    stroke: {
        curve: "smooth",
        width: 3,
    },
    colors: ["#00E676"],
    grid: {
        borderColor: "#1A1D23",
        strokeDashArray: 4,
    },
    xaxis: {
        categories: [],
        labels: {
            style: {
                colors: "#6B7280",
            },
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: "#6B7280",
            },
            formatter: (value) => {
                return "R$ " + value.toLocaleString("pt-BR");
            },
        },
    },
    tooltip: {
        theme: "dark",
        y: {
            formatter: (value) => {
                return (
                    "R$ " +
                    value.toLocaleString("pt-BR", {
                        minimumFractionDigits: 2,
                    })
                );
            },
        },
    },
    markers: {
        size: 5,
        colors: ["#00E676"],
        strokeColors: "#0F1114",
        strokeWidth: 2,
        hover: {
            size: 7,
        },
    },
});

const chartSeries = ref([
    {
        name: "Receita",
        data: [],
    },
]);

// Ticket Types Chart Configuration
const ticketTypesChartOptions = ref({
    chart: {
        type: "bar",
        toolbar: { show: false },
        background: "transparent",
        fontFamily: "Inter, sans-serif",
    },
    theme: {
        mode: "dark",
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: "55%",
            borderRadius: 6,
            dataLabels: {
                position: "top",
            },
        },
    },
    dataLabels: {
        enabled: false,
    },
    colors: ["#00E676", "#00B8D4"],
    stroke: {
        show: true,
        width: 2,
        colors: ["transparent"],
    },
    grid: {
        borderColor: "#1A1D23",
        strokeDashArray: 4,
    },
    xaxis: {
        categories: [],
        labels: {
            style: {
                colors: "#6B7280",
            },
            rotate: -45,
            rotateAlways: true,
        },
    },
    yaxis: [
        {
            title: {
                text: "Quantidade Vendida",
                style: { color: "#6B7280" },
            },
            labels: {
                style: { colors: "#6B7280" },
                formatter: (value) => Math.round(value),
            },
        },
        {
            opposite: true,
            title: {
                text: "Receita (R$)",
                style: { color: "#6B7280" },
            },
            labels: {
                style: { colors: "#6B7280" },
                formatter: (value) => {
                    return "R$ " + value.toLocaleString("pt-BR");
                },
            },
        },
    ],
    legend: {
        labels: {
            colors: "#6B7280",
        },
    },
    tooltip: {
        theme: "dark",
        y: [
            {
                formatter: (value) => value + " ingressos",
            },
            {
                formatter: (value) => {
                    return (
                        "R$ " +
                        value.toLocaleString("pt-BR", {
                            minimumFractionDigits: 2,
                        })
                    );
                },
            },
        ],
    },
});

const ticketTypesChartSeries = ref([
    {
        name: "Quantidade Vendida",
        data: [],
    },
    {
        name: "Receita (R$)",
        data: [],
    },
]);

// Methods
const fetchDashboardData = async () => {
    isLoading.value = true;
    try {
        const response = await api.get("/organizer/dashboard");
        console.log("Dashboard API Response:", response.data);
        dashboardData.value = response.data;

        // Update sales trend chart
        if (response.data.sales_trend) {
            chartOptions.value.xaxis.categories = response.data.sales_trend.map(
                (item) => {
                    const date = new Date(item.date);
                    return date.toLocaleDateString("pt-BR", {
                        day: "2-digit",
                        month: "short",
                    });
                },
            );
            chartSeries.value[0].data = response.data.sales_trend.map((item) =>
                parseFloat(item.revenue || 0),
            );
        }

        // Update ticket types chart
        if (response.data.ticket_types_sales) {
            ticketTypesChartOptions.value.xaxis.categories =
                response.data.ticket_types_sales.map((item) => item.name);
            ticketTypesChartSeries.value[0].data =
                response.data.ticket_types_sales.map((item) =>
                    parseInt(item.quantity_sold || 0),
                );
            ticketTypesChartSeries.value[1].data =
                response.data.ticket_types_sales.map((item) =>
                    parseFloat(item.total_revenue || 0),
                );
        }
    } catch (error) {
        console.error("Erro ao buscar dados do dashboard:", error);
    } finally {
        isLoading.value = false;
    }
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value || 0);
};

const formatNumber = (value) => {
    return new Intl.NumberFormat("pt-BR").format(value || 0);
};

const formatDate = (dateString) => {
    if (!dateString) return "-";
    const date = new Date(dateString);
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    });
};

onMounted(() => {
    fetchDashboardData();
});
</script>

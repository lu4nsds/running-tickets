<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="space-y-6">
            <SkeletonCard type="info-section" />
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <SkeletonCard v-for="i in 4" :key="i" type="metric-card" />
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <SkeletonCard type="chart" />
                <SkeletonCard type="chart" />
            </div>
            <SkeletonCard type="table-full" />
        </div>

        <!-- Error State -->
        <ErrorState
            v-else-if="error"
            title="Erro ao carregar dashboard"
            :message="error"
            @retry="fetchDashboard"
        />

        <!-- Dashboard Content -->
        <div v-else-if="dashboardData" class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center gap-4">
                    <button
                        @click="$router.back()"
                        class="flex items-center gap-2 text-text-muted hover:text-white transition-colors"
                    >
                        <span class="material-symbols-outlined"
                            >arrow_back</span
                        >
                        <span>Voltar</span>
                    </button>

                    <div class="h-8 w-px bg-surface-elevated"></div>

                    <div>
                        <h1 class="text-2xl font-bold text-white">
                            {{ dashboardData.event?.name }}
                        </h1>
                        <p class="text-text-muted text-sm mt-1">
                            {{ dashboardData.event?.location }} •
                            {{ daysUntilEvent }}
                        </p>
                    </div>
                </div>

                <button
                    @click="viewDetails"
                    class="px-4 py-2 bg-surface hover:bg-surface-elevated border border-surface-elevated rounded-lg text-white font-medium transition-colors flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-[18px]"
                        >info</span
                    >
                    <span>Ver Detalhes</span>
                </button>
            </div>

            <!-- Metrics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Total Revenue -->
                <MetricCard
                    title="Receita Total"
                    :value="
                        formatCurrency(
                            dashboardData.summary?.total_revenue * 100,
                        )
                    "
                    icon="payments"
                />

                <!-- Total Orders -->
                <MetricCard
                    title="Total de Pedidos"
                    :value="
                        formatNumber(dashboardData.summary?.total_orders || 0)
                    "
                    icon="shopping_cart"
                >
                    <div class="flex items-center gap-4 text-sm mt-2">
                        <span class="text-primary">
                            {{
                                formatNumber(
                                    dashboardData.summary?.paid_orders || 0,
                                )
                            }}
                            pagos
                        </span>
                        <span class="text-text-muted">
                            {{
                                formatNumber(
                                    dashboardData.summary?.pending_orders || 0,
                                )
                            }}
                            pendentes
                        </span>
                    </div>
                </MetricCard>

                <!-- Avg Ticket Value -->
                <MetricCard
                    title="Valor Médio"
                    :value="formatCurrency(avgTicketValue)"
                    icon="local_atm"
                />

                <!-- Conversion Rate -->
                <MetricCard
                    title="Taxa de Conversão"
                    :value="
                        formatPercentage(
                            dashboardData.conversion_funnel?.conversion_rate ||
                                0,
                        )
                    "
                    icon="trending_up"
                    :trend="
                        formatPercentage(
                            dashboardData.conversion_funnel?.conversion_rate ||
                                0,
                            0,
                        )
                    "
                    :trend-direction="
                        dashboardData.conversion_funnel?.conversion_rate >= 50
                            ? 'up'
                            : 'down'
                    "
                />
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Demographics -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <h3 class="text-lg font-semibold text-white mb-6">
                        Demografia por Gênero
                    </h3>
                    <VueApexCharts
                        v-if="demographicsChartOptions"
                        type="donut"
                        height="360"
                        :options="demographicsChartOptions"
                        :series="demographicsChartSeries"
                    />
                </div>

                <!-- Sales Velocity -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <h3 class="text-lg font-semibold text-white mb-4">
                        Velocidade de Vendas (7 dias)
                    </h3>
                    <VueApexCharts
                        v-if="salesVelocityOptions"
                        type="bar"
                        height="300"
                        :options="salesVelocityOptions"
                        :series="salesVelocitySeries"
                    />
                </div>
            </div>

            <!-- Sales by Category -->
            <div
                v-if="
                    dashboardData &&
                    dashboardData.sales_by_category &&
                    dashboardData.sales_by_category.length > 0
                "
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h3 class="text-lg font-semibold text-white mb-4">
                    Vendas por Categoria
                </h3>
                <VueApexCharts
                    v-if="categoryChartOptions"
                    type="bar"
                    height="350"
                    :options="categoryChartOptions"
                    :series="categoryChartSeries"
                />
            </div>

            <!-- Ticket Types Performance -->
            <div
                v-if="
                    dashboardData.ticket_types &&
                    dashboardData.ticket_types.length > 0
                "
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h3 class="text-lg font-semibold text-white mb-4">
                    Performance por Tipo de Ingresso
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr
                                class="border-b border-surface-elevated text-left"
                            >
                                <th
                                    class="py-3 px-4 text-text-muted font-medium text-sm"
                                >
                                    Tipo
                                </th>
                                <th
                                    class="py-3 px-4 text-text-muted font-medium text-sm"
                                >
                                    Preço
                                </th>
                                <th
                                    class="py-3 px-4 text-text-muted font-medium text-sm"
                                >
                                    Vendidos/Total
                                </th>
                                <th
                                    class="py-3 px-4 text-text-muted font-medium text-sm"
                                >
                                    Ocupação
                                </th>
                                <th
                                    class="py-3 px-4 text-text-muted font-medium text-sm"
                                >
                                    Receita
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="ticket in dashboardData.ticket_types"
                                :key="ticket.id"
                                class="border-b border-surface-elevated hover:bg-surface/30 transition-colors"
                            >
                                <td class="py-4 px-4">
                                    <p class="text-white font-medium">
                                        {{ ticket.name }}
                                    </p>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-white font-semibold">
                                        {{ formatCurrency(ticket.price_cents) }}
                                    </p>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-white">
                                        {{ ticket.sold }} /
                                        {{ ticket.total_quantity }}
                                    </p>
                                </td>
                                <td class="py-4 px-4">
                                    <div class="space-y-2">
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <span class="text-white text-sm">
                                                {{
                                                    ticket.sold_percentage?.toFixed(
                                                        1,
                                                    )
                                                }}%
                                            </span>
                                        </div>
                                        <div
                                            class="w-full bg-surface rounded-full h-2"
                                        >
                                            <div
                                                class="bg-primary h-2 rounded-full transition-all"
                                                :style="{
                                                    width: `${Math.min(ticket.sold_percentage || 0, 100)}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-4 px-4">
                                    <p class="text-white font-semibold">
                                        {{
                                            formatCurrency(ticket.revenue * 100)
                                        }}
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Sales Projection -->
            <div
                v-if="
                    dashboardData.projection &&
                    dashboardData.projection.length > 0
                "
                class="bg-card-bg border border-surface-elevated rounded-xl p-6"
            >
                <h3
                    class="text-lg font-semibold text-white mb-4 flex items-center gap-2"
                >
                    <span class="material-symbols-outlined text-primary"
                        >insights</span
                    >
                    Projeção de Vendas
                </h3>

                <div
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4"
                >
                    <div
                        v-for="proj in dashboardData.projection"
                        :key="proj.ticket_type"
                        class="bg-surface/30 border border-surface-elevated rounded-lg p-4"
                    >
                        <p class="text-text-muted text-sm mb-2">
                            {{ proj.ticket_type }}
                        </p>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-text-secondary text-sm"
                                    >Vendidos:</span
                                >
                                <span class="text-white font-medium">
                                    {{ formatNumber(proj.current_sold) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-primary text-sm"
                                    >Projetado:</span
                                >
                                <span class="text-primary font-semibold">
                                    {{ formatNumber(proj.projected_sold) }}
                                </span>
                            </div>
                            <div
                                class="flex justify-between pt-2 border-t border-surface-elevated"
                            >
                                <span class="text-text-secondary text-sm"
                                    >Receita Proj.:</span
                                >
                                <span class="text-white font-semibold">
                                    {{
                                        formatCurrency(
                                            proj.projected_revenue * 100,
                                        )
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import api from "@/api/axios";
import { API_ENDPOINTS } from "@/constants/apiEndpoints";
import { useLoading } from "@/composables/useLoading";
import { useCurrency } from "@/composables/useCurrency";
import ErrorState from "@/components/ui/ErrorState.vue";
import SkeletonCard from "@/components/ui/SkeletonCard.vue";
import MetricCard from "@/components/dashboard/MetricCard.vue";
import VueApexCharts from "vue3-apexcharts";

const route = useRoute();
const router = useRouter();
const { isLoading, error, withLoading } = useLoading(true);
const { formatCurrency, formatNumber, formatPercentage } = useCurrency();

const dashboardData = ref(null);

// Computed
const avgTicketValue = computed(() => {
    const summary = dashboardData.value?.summary;
    if (!summary?.total_orders || !summary?.total_revenue) return 0;
    return (summary.total_revenue / summary.total_orders) * 100;
});

const daysUntilEvent = computed(() => {
    const days = dashboardData.value?.event?.days_until_event;
    if (days === null || days === undefined) return "";
    if (days < 0) return "Evento encerrado";
    if (days < 1) return "O evento é hoje!";

    const daysInt = Math.floor(days);
    if (daysInt === 1) return "Amanhã";
    return `${daysInt} dias para o evento`;
});

// Demographics Chart (Donut)
const demographicsChartSeries = computed(() => {
    const demographics = dashboardData.value?.demographics;
    if (!demographics) return [0, 0, 0];
    return [
        demographics.male || 0,
        demographics.female || 0,
        demographics.other || 0,
    ];
});

const demographicsChartOptions = computed(() => {
    const demographics = dashboardData.value?.demographics;
    const total =
        (demographics?.male || 0) +
        (demographics?.female || 0) +
        (demographics?.other || 0);

    return {
        chart: {
            type: "donut",
            background: "transparent",
            animations: {
                enabled: true,
                easing: "easeinout",
                speed: 600,
                animateGradually: {
                    enabled: true,
                    delay: 100,
                },
                dynamicAnimation: {
                    enabled: true,
                    speed: 300,
                },
            },
        },
        theme: {
            mode: "dark",
        },
        labels: ["Masculino", "Feminino", "Outro"],
        colors: ["#00E676", "#3B82F6", "#94A3B8"],
        plotOptions: {
            pie: {
                startAngle: 0,
                endAngle: 360,
                expandOnClick: true,
                donut: {
                    size: "75%",
                    background: "transparent",
                    labels: {
                        show: true,
                        name: {
                            show: true,
                            fontSize: "16px",
                            fontWeight: 600,
                            color: "#E2E8F0",
                            offsetY: -10,
                        },
                        value: {
                            show: true,
                            fontSize: "32px",
                            fontWeight: "bold",
                            color: "#FFFFFF",
                            offsetY: 10,
                            formatter: (val) => val,
                        },
                        total: {
                            show: true,
                            label: "Total de Participantes",
                            fontSize: "13px",
                            fontWeight: 500,
                            color: "#94A3B8",
                            formatter: () => {
                                return total.toLocaleString("pt-BR");
                            },
                        },
                    },
                },
            },
        },
        dataLabels: {
            enabled: false,
        },
        stroke: {
            show: true,
            width: 3,
            colors: ["#0F172A"],
        },
        states: {
            hover: {
                filter: {
                    type: "lighten",
                    value: 0.1,
                },
            },
            active: {
                filter: {
                    type: "none",
                },
            },
        },
        legend: {
            show: true,
            position: "bottom",
            horizontalAlign: "center",
            fontSize: "14px",
            fontWeight: 500,
            offsetY: 0,
            markers: {
                width: 12,
                height: 12,
                radius: 12,
                offsetX: -5,
            },
            labels: {
                colors: "#E2E8F0",
                useSeriesColors: false,
            },
            itemMargin: {
                horizontal: 16,
                vertical: 8,
            },
            formatter: (seriesName, opts) => {
                const value = opts.w.globals.series[opts.seriesIndex];
                const percent =
                    total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                return `${seriesName}: ${value} (${percent}%)`;
            },
        },
        tooltip: {
            enabled: true,
            theme: "dark",
            fillSeriesColor: false,
            style: {
                fontSize: "13px",
            },
            y: {
                formatter: (val) => {
                    const percent =
                        total > 0 ? ((val / total) * 100).toFixed(1) : 0;
                    return `${val} tickets (${percent}%)`;
                },
                title: {
                    formatter: (seriesName) => seriesName,
                },
            },
        },
        responsive: [
            {
                breakpoint: 480,
                options: {
                    chart: {
                        width: "100%",
                    },
                    legend: {
                        position: "bottom",
                        fontSize: "12px",
                    },
                },
            },
        ],
    };
});

// Category Sales Chart (Horizontal Bar)
const categoryChartSeries = computed(() => {
    const categories = dashboardData.value?.sales_by_category || [];
    return [
        {
            name: "Tickets Vendidos",
            data: categories.map((item) => item.tickets_sold),
        },
    ];
});

const categoryChartOptions = computed(() => {
    const categories = dashboardData.value?.sales_by_category || [];

    return {
        chart: {
            type: "bar",
            background: "transparent",
            toolbar: {
                show: false,
            },
        },
        theme: {
            mode: "dark",
        },
        plotOptions: {
            bar: {
                horizontal: true,
                borderRadius: 4,
                dataLabels: {
                    position: "top",
                },
            },
        },
        dataLabels: {
            enabled: true,
            formatter: (val) => formatNumber(val),
            offsetX: 30,
            style: {
                fontSize: "12px",
                colors: ["#FFFFFF"],
            },
        },
        xaxis: {
            categories: categories.map((item) => item.name),
            labels: {
                style: {
                    colors: "#94A3B8",
                },
            },
        },
        yaxis: {
            labels: {
                style: {
                    colors: "#94A3B8",
                },
            },
        },
        colors: ["#00E676"],
        grid: {
            borderColor: "#334155",
            xaxis: {
                lines: {
                    show: true,
                },
            },
        },
        tooltip: {
            theme: "dark",
            y: {
                formatter: (val) => `${formatNumber(val)} tickets`,
            },
        },
    };
});

// Sales Velocity Chart
const salesVelocitySeries = computed(() => {
    const salesData = dashboardData.value?.sales_velocity || [];
    return [
        {
            name: "Pedidos",
            data: salesData.map((item) => item.orders),
        },
        {
            name: "Receita (R$)",
            data: salesData.map((item) => item.revenue),
        },
    ];
});

const salesVelocityOptions = computed(() => ({
    chart: {
        type: "bar",
        background: "transparent",
        toolbar: {
            show: false,
        },
    },
    theme: {
        mode: "dark",
    },
    plotOptions: {
        bar: {
            horizontal: false,
            columnWidth: "55%",
            borderRadius: 4,
        },
    },
    dataLabels: {
        enabled: false,
    },
    stroke: {
        show: true,
        width: 2,
        colors: ["transparent"],
    },
    xaxis: {
        categories: (dashboardData.value?.sales_velocity || []).map((item) => {
            const date = new Date(item.date);
            return date.toLocaleDateString("pt-BR", {
                day: "2-digit",
                month: "short",
            });
        }),
        labels: {
            style: {
                colors: "#94A3B8",
            },
        },
    },
    yaxis: [
        {
            title: {
                text: "Pedidos",
                style: {
                    color: "#94A3B8",
                },
            },
            labels: {
                style: {
                    colors: "#94A3B8",
                },
            },
        },
        {
            opposite: true,
            title: {
                text: "Receita (R$)",
                style: {
                    color: "#94A3B8",
                },
            },
            labels: {
                style: {
                    colors: "#94A3B8",
                },
                formatter: (val) => `R$ ${val.toFixed(0)}`,
            },
        },
    ],
    fill: {
        opacity: 1,
    },
    tooltip: {
        theme: "dark",
        y: [
            {
                formatter: (val) => `${val} pedidos`,
            },
            {
                formatter: (val) =>
                    `R$ ${val.toLocaleString("pt-BR", {
                        minimumFractionDigits: 2,
                    })}`,
            },
        ],
    },
    colors: ["#00E676", "#3B82F6"],
    grid: {
        borderColor: "#334155",
    },
}));

// Methods
const fetchDashboard = async () => {
    await withLoading(async () => {
        const eventId = route.params.id;
        const response = await api.get(
            API_ENDPOINTS.ORGANIZER.EVENT.DASHBOARD(eventId),
        );
        dashboardData.value = response.data;
    });
};

const viewDetails = () => {
    router.push(`/organizer/events/${route.params.id}`);
};

onMounted(() => {
    fetchDashboard();
});
</script>

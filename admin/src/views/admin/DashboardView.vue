<template>
    <div class="space-y-8">
        <!-- Error Message -->
        <div
            v-if="dashboardStore.error"
            class="bg-red-500/10 border border-red-500/30 rounded-lg p-4 flex items-center gap-3"
        >
            <span class="material-symbols-outlined text-red-500">error</span>
            <div>
                <p class="text-red-400 font-bold">Erro ao carregar dashboard</p>
                <p class="text-red-300 text-sm">{{ dashboardStore.error }}</p>
            </div>
            <button
                @click="dashboardStore.fetchPlatformDashboard"
                class="ml-auto px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm font-medium transition-all"
            >
                Tentar Novamente
            </button>
        </div>

        <!-- Header -->
        <header
            class="flex flex-col md:flex-row md:items-center justify-between gap-4"
        >
            <div>
                <h1 class="text-3xl font-extrabold text-white">
                    Dashboard Executivo
                </h1>
                <p class="text-slate-400 mt-1">
                    Bem-vindo ao centro de comando Running Tickets.
                </p>
            </div>
        </header>

        <!-- Date Range Filters -->
        <DateRangeSelector
            :initial-preset="dashboardStore.dateFilter.preset"
            @filter-change="onFilterChange"
        />

        <!-- Top Row: Metrics -->
        <div
            v-if="dashboardStore.isLoading"
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
        >
            <div
                v-for="i in 4"
                :key="i"
                class="bg-card-dark p-6 rounded-xl border border-border-dark animate-pulse"
            >
                <!-- Skeleton: Header with icon and badge -->
                <div class="flex items-center justify-between mb-4">
                    <div class="w-10 h-10 rounded-lg bg-white/5"></div>
                    <div class="w-16 h-6 rounded bg-white/5"></div>
                </div>
                <!-- Skeleton: Label -->
                <div class="w-24 h-4 rounded bg-white/5 mb-2"></div>
                <!-- Skeleton: Value -->
                <div class="w-32 h-8 rounded bg-white/5 mb-4"></div>
                <!-- Skeleton: Footer stats -->
                <div
                    class="border-t border-border-dark pt-4 flex items-center gap-4"
                >
                    <div class="flex flex-col gap-1">
                        <div class="w-12 h-3 rounded bg-white/5"></div>
                        <div class="w-16 h-5 rounded bg-white/5"></div>
                    </div>
                    <div class="w-px h-6 bg-border-dark"></div>
                    <div class="flex flex-col gap-1">
                        <div class="w-14 h-3 rounded bg-white/5"></div>
                        <div class="w-14 h-5 rounded bg-white/5"></div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6"
        >
            <!-- Finance Card -->
            <div
                class="bg-card-dark p-6 rounded-xl border border-border-dark hover:border-primary/30 transition-all group"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center text-primary"
                    >
                        <span class="material-symbols-outlined"
                            >account_balance_wallet</span
                        >
                    </div>
                </div>
                <p class="text-slate-400 text-sm font-medium">
                    Performance Financeira
                </p>
                <h3 class="text-2xl font-extrabold mt-1">
                    {{
                        formatCurrency(
                            dashboardStore.summary.total_revenue || 0,
                        )
                    }}
                </h3>
                <div
                    class="mt-4 flex items-center justify-between text-xs border-t border-border-dark pt-4"
                >
                    <span class="text-slate-500">Pendente:</span>
                    <span class="text-orange-400 font-bold">{{
                        formatCurrency(
                            dashboardStore.summary.pending_revenue || 0,
                        )
                    }}</span>
                </div>
            </div>

            <!-- Orders Card -->
            <div
                class="bg-card-dark p-6 rounded-xl border border-border-dark hover:border-primary/30 transition-all group"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center text-blue-400"
                    >
                        <span class="material-symbols-outlined"
                            >shopping_cart</span
                        >
                    </div>
                </div>
                <p class="text-slate-400 text-sm font-medium">
                    Status de Pedidos
                </p>
                <h3 class="text-2xl font-extrabold mt-1">
                    {{ formatNumber(dashboardStore.summary.total_orders || 0) }}
                </h3>
                <div
                    class="mt-4 flex items-center gap-3 text-xs border-t border-border-dark pt-4"
                >
                    <div class="flex flex-col">
                        <span class="text-slate-500 uppercase text-[10px]"
                            >Pagos</span
                        >
                        <span class="text-primary font-bold">{{
                            formatNumber(
                                dashboardStore.summary.paid_orders || 0,
                            )
                        }}</span>
                    </div>
                    <div class="w-px h-6 bg-border-dark"></div>
                    <div class="flex flex-col">
                        <span class="text-slate-500 uppercase text-[10px]"
                            >Pendentes</span
                        >
                        <span class="text-slate-300 font-bold">{{
                            formatNumber(
                                dashboardStore.summary.pending_orders || 0,
                            )
                        }}</span>
                    </div>
                </div>
            </div>

            <!-- Ecosystem Card -->
            <div
                class="bg-card-dark p-6 rounded-xl border border-border-dark hover:border-primary/30 transition-all group"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center text-purple-400"
                    >
                        <span class="material-symbols-outlined">hub</span>
                    </div>
                    <span
                        class="text-xs font-bold text-purple-400 bg-purple-500/10 px-2 py-1 rounded"
                    >
                        {{ dashboardStore.activeEventsLabel }}
                    </span>
                </div>
                <p class="text-slate-400 text-sm font-medium">Ecossistema</p>
                <h3 class="text-2xl font-extrabold mt-1">
                    {{ formatNumber(dashboardStore.summary.total_events || 0) }}
                    <span class="text-sm font-normal text-slate-500"
                        >Eventos</span
                    >
                </h3>
                <div
                    class="mt-4 flex items-center justify-between text-xs border-t border-border-dark pt-4"
                >
                    <span class="text-slate-500">Organizadores:</span>
                    <span class="text-white font-bold">{{
                        formatNumber(
                            dashboardStore.summary.total_organizers || 0,
                        )
                    }}</span>
                </div>
            </div>

            <!-- Platform Health Card -->
            <div
                class="bg-card-dark p-6 rounded-xl border border-border-dark hover:border-primary/30 transition-all group"
            >
                <div class="flex items-center justify-between mb-4">
                    <div
                        class="w-10 h-10 rounded-lg bg-yellow-500/10 flex items-center justify-center text-yellow-400"
                    >
                        <span class="material-symbols-outlined"
                            >monitor_heart</span
                        >
                    </div>
                    <div class="flex gap-1">
                        <div
                            class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse"
                        ></div>
                        <div
                            class="w-1.5 h-1.5 rounded-full bg-primary animate-pulse delay-75"
                        ></div>
                    </div>
                </div>
                <p class="text-slate-400 text-sm font-medium">
                    Saúde da Plataforma
                </p>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    <div class="text-center">
                        <p class="text-primary text-lg font-bold">
                            {{
                                dashboardStore.platformHealth.conversion_rate ||
                                0
                            }}%
                        </p>
                        <p class="text-[10px] text-slate-500 uppercase">
                            Conv.
                        </p>
                    </div>
                    <div class="text-center border-x border-border-dark">
                        <p class="text-red-400 text-lg font-bold">
                            {{
                                dashboardStore.platformHealth
                                    .cancellation_rate || 0
                            }}%
                        </p>
                        <p class="text-[10px] text-slate-500 uppercase">
                            Canc.
                        </p>
                    </div>
                    <div class="text-center">
                        <div class="flex items-center justify-center gap-1">
                            <p class="text-white text-lg font-bold">
                                {{
                                    formatCurrency(
                                        dashboardStore.platformHealth
                                            .avg_order_value || 0,
                                    )
                                }}
                            </p>
                            <div class="group relative">
                                <span
                                    class="material-symbols-outlined text-slate-500 text-xs cursor-help"
                                    >info</span
                                >
                                <div
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 hidden group-hover:block z-10"
                                >
                                    <div
                                        class="bg-slate-900 text-white text-xs rounded-lg px-3 py-2 w-48 shadow-xl border border-slate-700"
                                    >
                                        <p class="font-bold mb-1">
                                            Ticket Médio
                                        </p>
                                        <p class="text-slate-300">
                                            Valor médio de cada pedido pago na
                                            plataforma (receita total ÷ pedidos
                                            pagos)
                                        </p>
                                    </div>
                                    <div
                                        class="w-2 h-2 bg-slate-900 border-b border-r border-slate-700 absolute top-0 left-1/2 -translate-x-1/2 translate-y-1/2 rotate-45"
                                    ></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500 uppercase">
                            Tkt. M.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Middle Section: Main Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Area Chart Column -->
            <div
                class="lg:col-span-2 bg-card-dark rounded-xl border border-border-dark p-6 overflow-hidden relative"
            >
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h4 class="text-lg font-bold text-white">
                            Tendência de Vendas
                        </h4>
                        <p class="text-sm text-slate-500">
                            Volume de transações diárias processadas
                        </p>
                    </div>
                </div>
                <!-- Chart Area -->
                <div
                    v-if="dashboardStore.isLoading"
                    class="h-[300px] w-full animate-pulse"
                >
                    <!-- Skeleton Chart -->
                    <div class="h-full flex flex-col justify-end gap-1">
                        <div class="flex items-end gap-1 h-full px-4">
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[40%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[65%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[50%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[80%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[60%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[90%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[75%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[55%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[70%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[85%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[45%]"
                            ></div>
                            <div
                                class="flex-1 bg-white/5 rounded-t h-[60%]"
                            ></div>
                        </div>
                        <!-- X Axis labels skeleton -->
                        <div class="flex justify-between px-4 mt-2">
                            <div class="w-8 h-3 rounded bg-white/5"></div>
                            <div class="w-8 h-3 rounded bg-white/5"></div>
                            <div class="w-8 h-3 rounded bg-white/5"></div>
                            <div class="w-8 h-3 rounded bg-white/5"></div>
                            <div class="w-8 h-3 rounded bg-white/5"></div>
                        </div>
                    </div>
                </div>
                <div
                    v-else-if="
                        !dashboardStore.salesTrend ||
                        dashboardStore.salesTrend.length === 0
                    "
                    class="h-[300px] w-full flex items-center justify-center"
                >
                    <div class="text-center">
                        <span
                            class="material-symbols-outlined text-slate-600 text-5xl mb-2"
                            >show_chart</span
                        >
                        <p class="text-slate-500 text-sm">
                            Sem dados de vendas nos últimos 30 dias
                        </p>
                    </div>
                </div>
                <div v-else>
                    <apexchart
                        :key="'chart-' + dashboardStore.salesTrend.length"
                        type="line"
                        height="300"
                        :options="chartOptions"
                        :series="chartSeries"
                    ></apexchart>
                </div>
            </div>

            <!-- Donut Chart Column -->
            <div
                class="bg-card-dark rounded-xl border border-border-dark p-6 flex flex-col"
            >
                <h4 class="text-lg font-bold text-white mb-6">
                    Divisão Financeira
                </h4>
                <div
                    v-if="dashboardStore.isLoading"
                    class="flex-1 flex flex-col items-center justify-center animate-pulse"
                >
                    <!-- Skeleton Donut Chart -->
                    <div class="relative w-48 h-48 mb-6">
                        <div
                            class="absolute inset-0 rounded-full border-[20px] border-white/5"
                        ></div>
                        <div
                            class="absolute top-0 left-0 w-48 h-48 rounded-full border-[20px] border-primary/20 border-t-transparent border-r-transparent rotate-45"
                        ></div>
                        <div
                            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-28 h-28 rounded-full bg-card-dark"
                        ></div>
                    </div>
                    <!-- Skeleton Legend -->
                    <div class="w-full space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-3 h-3 rounded-full bg-white/10"
                                ></div>
                                <div class="w-24 h-4 rounded bg-white/5"></div>
                            </div>
                            <div class="w-12 h-4 rounded bg-white/5"></div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-3 h-3 rounded-full bg-white/10"
                                ></div>
                                <div class="w-28 h-4 rounded bg-white/5"></div>
                            </div>
                            <div class="w-12 h-4 rounded bg-white/5"></div>
                        </div>
                    </div>
                </div>
                <div
                    v-else-if="dashboardStore.payoutPercentages.length === 0"
                    class="flex-1 flex items-center justify-center"
                >
                    <p class="text-slate-500 text-sm">Sem dados disponíveis</p>
                </div>
                <div v-else>
                    <div
                        class="relative flex-1 flex items-center justify-center mb-6"
                    >
                        <!-- Donut Chart SVG -->
                        <svg class="w-56 h-56" viewBox="0 0 200 200">
                            <g transform="translate(100, 100)">
                                <!-- Render donut segments -->
                                <path
                                    v-for="(
                                        segment, index
                                    ) in dashboardStore.donutSegments"
                                    :key="index"
                                    :d="segment.path"
                                    :fill="segment.color"
                                    :class="'transition-all hover:opacity-80 cursor-pointer'"
                                />
                                <!-- Center circle for donut hole -->
                                <circle
                                    r="50"
                                    fill="#0f1419"
                                    class="pointer-events-none"
                                />
                                <!-- Center text -->
                                <text
                                    y="-5"
                                    text-anchor="middle"
                                    class="text-[10px] fill-slate-500 font-bold uppercase"
                                >
                                    Total
                                </text>
                                <text
                                    y="12"
                                    text-anchor="middle"
                                    class="text-xl font-extrabold fill-white"
                                >
                                    {{
                                        formatCurrency(
                                            dashboardStore.payoutPercentages.reduce(
                                                (sum, p) =>
                                                    sum + parseFloat(p.revenue),
                                                0,
                                            ),
                                        )
                                    }}
                                </text>
                            </g>
                            <!-- Labels with percentage only -->
                            <g
                                v-for="(
                                    segment, index
                                ) in dashboardStore.donutSegments"
                                :key="'label-' + index"
                            >
                                <text
                                    :x="segment.labelX + 100"
                                    :y="segment.labelY + 104"
                                    text-anchor="middle"
                                    class="text-sm font-bold pointer-events-none"
                                    :fill="segment.color"
                                >
                                    {{ segment.percentage }}%
                                </text>
                            </g>
                        </svg>
                    </div>
                    <div class="space-y-3">
                        <div
                            v-for="(
                                item, index
                            ) in dashboardStore.payoutPercentages"
                            :key="index"
                            class="flex items-center justify-between text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    :class="[
                                        'w-3 h-3 rounded-full',
                                        index === 0
                                            ? 'bg-primary shadow-[0_0_5px_#00e677]'
                                            : index === 1
                                              ? 'bg-blue-500 shadow-[0_0_5px_#3b82f6]'
                                              : 'bg-purple-500 shadow-[0_0_5px_#a855f7]',
                                    ]"
                                ></span>
                                <span class="text-slate-400">
                                    {{
                                        item.payout_mode === "platform"
                                            ? "Plataforma (Repasse)"
                                            : item.payout_mode === "direct"
                                              ? "Direto Organizador"
                                              : item.mode_label ||
                                                item.payout_mode
                                    }}
                                </span>
                            </div>
                            <div class="text-right">
                                <span class="text-white font-bold"
                                    >{{ item.percentage }}%</span
                                >
                                <p class="text-xs text-slate-500">
                                    {{ formatCurrency(item.revenue) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Row: Tables & Alerts -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Top Organizers -->
            <div
                class="bg-card-dark rounded-xl border border-border-dark p-6 flex flex-col min-h-[400px]"
            >
                <div class="flex items-center justify-between mb-6">
                    <h4 class="font-bold text-white flex items-center gap-2">
                        <span
                            class="material-symbols-outlined text-primary text-xl"
                            >workspace_premium</span
                        >
                        Top Organizadores
                    </h4>
                    <span
                        class="text-[10px] bg-white/5 px-2 py-0.5 rounded text-slate-400"
                        >{{
                            dashboardStore.topOrganizers.length
                        }}
                        organizadores</span
                    >
                </div>
                <div
                    v-if="dashboardStore.isLoading"
                    class="space-y-4 animate-pulse flex-1"
                >
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="flex items-center justify-between p-3 rounded-lg border border-border-dark/50"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-6 h-6 rounded bg-white/5"></div>
                            <div class="flex flex-col gap-1">
                                <div class="w-24 h-4 rounded bg-white/5"></div>
                                <div class="w-16 h-3 rounded bg-white/5"></div>
                            </div>
                        </div>
                        <div class="w-20 h-4 rounded bg-white/5"></div>
                    </div>
                </div>
                <div
                    v-else-if="dashboardStore.topOrganizers.length === 0"
                    class="text-center py-8 flex-1 flex items-center justify-center"
                >
                    <p class="text-slate-500 text-sm">
                        Nenhum organizador encontrado
                    </p>
                </div>
                <div v-else class="flex flex-col flex-1">
                    <div class="flex flex-col justify-between flex-1 h-[280px]">
                        <div
                            v-for="(org, index) in paginatedOrganizers"
                            :key="org.id"
                            :class="[
                                'flex items-center justify-between p-3 rounded-lg border transition-all',
                                organizersPage === 0 && index === 0
                                    ? 'bg-primary/5 border-primary/20'
                                    : 'border-transparent hover:border-border-dark',
                            ]"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    :class="[
                                        'text-lg font-extrabold italic w-6 text-center',
                                        organizersPage === 0 && index === 0
                                            ? 'text-primary'
                                            : 'text-slate-600',
                                    ]"
                                >
                                    #{{
                                        organizersPage * itemsPerPage +
                                        index +
                                        1
                                    }}
                                </span>
                                <div>
                                    <p
                                        :class="[
                                            'text-sm font-bold',
                                            organizersPage === 0 && index === 0
                                                ? 'text-white'
                                                : 'text-slate-300',
                                        ]"
                                    >
                                        {{ org.name }}
                                    </p>
                                    <p class="text-xs text-slate-500">
                                        {{ org.total_events }} Eventos
                                    </p>
                                </div>
                            </div>
                            <p
                                :class="[
                                    'text-sm font-bold',
                                    organizersPage === 0 && index === 0
                                        ? 'text-primary'
                                        : 'text-slate-300',
                                ]"
                            >
                                {{ formatCurrency(org.total_revenue) }}
                            </p>
                        </div>
                    </div>
                    <!-- Pagination Controls -->
                    <div
                        v-if="
                            dashboardStore.topOrganizers.length > itemsPerPage
                        "
                        class="flex justify-center items-center gap-4 pt-6 mt-auto"
                    >
                        <button
                            @click="
                                organizersPage = Math.max(0, organizersPage - 1)
                            "
                            :disabled="organizersPage === 0"
                            class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-lg"
                                >chevron_left</span
                            >
                        </button>
                        <div class="flex items-center gap-2">
                            <template
                                v-for="page in totalOrganizersPages"
                                :key="page"
                            >
                                <div
                                    :class="[
                                        'w-2 h-2 rounded-full transition-all',
                                        organizersPage === page - 1
                                            ? 'bg-primary shadow-[0_0_8px_rgba(0,230,118,0.6)]'
                                            : 'bg-slate-600',
                                    ]"
                                ></div>
                            </template>
                        </div>
                        <button
                            @click="
                                organizersPage = Math.min(
                                    totalOrganizersPages - 1,
                                    organizersPage + 1,
                                )
                            "
                            :disabled="
                                organizersPage >= totalOrganizersPages - 1
                            "
                            class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-lg"
                                >chevron_right</span
                            >
                        </button>
                    </div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div
                class="bg-card-dark rounded-xl border border-border-dark p-6 flex flex-col min-h-[400px]"
            >
                <div class="flex items-center justify-between mb-6">
                    <h4 class="font-bold text-white flex items-center gap-2">
                        <span
                            class="material-symbols-outlined text-orange-400 text-xl"
                            >pending_actions</span
                        >
                        Pagamentos Pendentes
                    </h4>
                    <span
                        class="text-[10px] bg-white/5 px-2 py-0.5 rounded text-slate-400"
                        >{{ sortedPendingPayouts.length }} organizadores</span
                    >
                </div>
                <div
                    v-if="dashboardStore.isLoading"
                    class="space-y-4 animate-pulse"
                >
                    <div v-for="i in 4" :key="i">
                        <div class="flex items-center justify-between py-2">
                            <div class="flex flex-col gap-1">
                                <div class="w-16 h-3 rounded bg-white/5"></div>
                                <div
                                    class="w-28 h-4 rounded bg-white/5 mt-1"
                                ></div>
                                <div
                                    class="w-20 h-3 rounded bg-white/5 mt-1"
                                ></div>
                            </div>
                            <div class="w-20 h-5 rounded bg-white/5"></div>
                        </div>
                        <div
                            v-if="i < 4"
                            class="h-px bg-border-dark mt-2"
                        ></div>
                    </div>
                </div>
                <div
                    v-else-if="sortedPendingPayouts.length === 0"
                    class="text-center py-8"
                >
                    <p class="text-slate-500 text-sm">
                        Nenhum pagamento pendente
                    </p>
                </div>
                <div v-else class="flex flex-col flex-1">
                    <div class="flex flex-col justify-between flex-1 h-[280px]">
                        <template
                            v-for="(payout, index) in paginatedPendingPayouts"
                            :key="payout.organizer_id"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <p
                                        class="text-xs text-slate-500 uppercase font-bold tracking-wider"
                                    >
                                        Organizador
                                    </p>
                                    <p
                                        class="text-sm font-bold text-white mt-1"
                                    >
                                        {{ payout.organizer_name }}
                                    </p>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        Próximo evento:
                                        {{ payout.days_until }} dias
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="text-xs font-bold text-orange-400"
                                    >
                                        {{
                                            formatCurrency(
                                                payout.amount_to_transfer,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div
                                v-if="
                                    index < paginatedPendingPayouts.length - 1
                                "
                                class="h-px bg-border-dark"
                            ></div>
                        </template>
                    </div>
                    <!-- Pagination Controls - Always at bottom -->
                    <div
                        v-if="sortedPendingPayouts.length > itemsPerPage"
                        class="flex justify-center items-center gap-4 pt-6 mt-auto"
                    >
                        <button
                            @click="
                                pendingPayoutsPage = Math.max(
                                    0,
                                    pendingPayoutsPage - 1,
                                )
                            "
                            :disabled="pendingPayoutsPage === 0"
                            class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-lg"
                                >chevron_left</span
                            >
                        </button>
                        <div class="flex items-center gap-2">
                            <template
                                v-for="page in totalPendingPayoutsPages"
                                :key="page"
                            >
                                <div
                                    :class="[
                                        'w-2 h-2 rounded-full transition-all',
                                        pendingPayoutsPage === page - 1
                                            ? 'bg-primary shadow-[0_0_8px_rgba(0,230,118,0.6)]'
                                            : 'bg-slate-600',
                                    ]"
                                ></div>
                            </template>
                        </div>
                        <button
                            @click="
                                pendingPayoutsPage = Math.min(
                                    totalPendingPayoutsPages - 1,
                                    pendingPayoutsPage + 1,
                                )
                            "
                            :disabled="
                                pendingPayoutsPage >=
                                totalPendingPayoutsPages - 1
                            "
                            class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-lg"
                                >chevron_right</span
                            >
                        </button>
                    </div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div
                class="bg-card-dark rounded-xl border border-border-dark p-6 flex flex-col min-h-[400px]"
            >
                <div class="flex items-center justify-between mb-6">
                    <h4 class="font-bold text-white flex items-center gap-2">
                        <span
                            class="material-symbols-outlined text-blue-400 text-xl"
                            >event</span
                        >
                        Eventos Próximos
                    </h4>
                    <span class="text-xs text-slate-500">
                        {{ dashboardStore.upcomingEvents.length }} eventos
                    </span>
                </div>
                <div
                    v-if="dashboardStore.isLoading"
                    class="space-y-3 animate-pulse"
                >
                    <div
                        v-for="i in 5"
                        :key="i"
                        class="flex items-center gap-3 p-3 rounded-lg border border-border-dark/50"
                    >
                        <!-- Date skeleton -->
                        <div class="flex-shrink-0 w-12 text-center">
                            <div
                                class="w-8 h-6 rounded bg-white/5 mx-auto"
                            ></div>
                            <div
                                class="w-6 h-3 rounded bg-white/5 mx-auto mt-1"
                            ></div>
                        </div>
                        <!-- Info skeleton -->
                        <div class="flex-1 flex flex-col gap-1">
                            <div class="w-32 h-4 rounded bg-white/5"></div>
                            <div class="w-20 h-3 rounded bg-white/5"></div>
                        </div>
                        <!-- Badge skeleton -->
                        <div class="w-16 h-6 rounded-full bg-white/5"></div>
                    </div>
                </div>
                <div
                    v-else-if="dashboardStore.upcomingEvents.length === 0"
                    class="text-center py-8"
                >
                    <span
                        class="material-symbols-outlined text-slate-600 text-4xl mb-2"
                        >event_available</span
                    >
                    <p class="text-slate-500 text-sm">Nenhum evento próximo</p>
                </div>
                <div v-else class="flex flex-col flex-1">
                    <div class="space-y-3 flex-1 h-[280px]">
                        <div
                            v-for="event in paginatedUpcomingEvents"
                            :key="event.id"
                            class="flex items-center gap-3 p-3 rounded-lg border border-border-dark hover:border-primary/30 transition-all"
                        >
                            <div class="flex-shrink-0 text-center">
                                <div
                                    class="text-2xl font-extrabold text-primary"
                                >
                                    {{ formatEventDay(event.date_start) }}
                                </div>
                                <div
                                    class="text-[10px] text-slate-500 uppercase font-bold"
                                >
                                    {{ formatEventMonth(event.date_start) }}
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p
                                    class="text-sm font-bold text-white truncate"
                                >
                                    {{ event.title }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    {{ event.organizer_name }}
                                </p>
                            </div>
                            <div class="flex-shrink-0 text-right">
                                <span class="text-xs font-bold text-slate-400">
                                    {{ event.days_until }} dias
                                </span>
                            </div>
                        </div>
                    </div>
                    <!-- Pagination Controls - Always at bottom -->
                    <div
                        v-if="
                            dashboardStore.upcomingEvents.length > itemsPerPage
                        "
                        class="flex justify-center items-center gap-4 pt-6 mt-auto"
                    >
                        <button
                            @click="
                                upcomingEventsPage = Math.max(
                                    0,
                                    upcomingEventsPage - 1,
                                )
                            "
                            :disabled="upcomingEventsPage === 0"
                            class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-lg"
                                >chevron_left</span
                            >
                        </button>
                        <div class="flex items-center gap-2">
                            <template
                                v-for="page in totalUpcomingEventsPages"
                                :key="page"
                            >
                                <div
                                    :class="[
                                        'w-2 h-2 rounded-full transition-all',
                                        upcomingEventsPage === page - 1
                                            ? 'bg-primary shadow-[0_0_8px_rgba(0,230,118,0.6)]'
                                            : 'bg-slate-600',
                                    ]"
                                ></div>
                            </template>
                        </div>
                        <button
                            @click="
                                upcomingEventsPage = Math.min(
                                    totalUpcomingEventsPages - 1,
                                    upcomingEventsPage + 1,
                                )
                            "
                            :disabled="
                                upcomingEventsPage >=
                                totalUpcomingEventsPages - 1
                            "
                            class="flex items-center justify-center w-10 h-10 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                        >
                            <span class="material-symbols-outlined text-lg"
                                >chevron_right</span
                            >
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref, computed } from "vue";
import { useDashboardStore } from "@/stores/dashboard";
import { useCurrency } from "@/composables/useCurrency";
import VueApexCharts from "vue3-apexcharts";
import DateRangeSelector from "@/components/dashboard/DateRangeSelector.vue";

const dashboardStore = useDashboardStore();
const { formatCurrency, formatNumber } = useCurrency();
const upcomingEventsPage = ref(0);
const pendingPayoutsPage = ref(0);
const organizersPage = ref(0);
const itemsPerPage = 5;

// Handle filter change from DateRangeSelector
const onFilterChange = async (filter) => {
    await dashboardStore.setDateFilter(filter);
};

// Computed properties for pagination - Organizers
const paginatedOrganizers = computed(() => {
    const start = organizersPage.value * itemsPerPage;
    return dashboardStore.topOrganizers.slice(start, start + itemsPerPage);
});

const totalOrganizersPages = computed(() => {
    return Math.ceil(dashboardStore.topOrganizers.length / itemsPerPage);
});

// Computed properties for pagination - Upcoming Events
const paginatedUpcomingEvents = computed(() => {
    const start = upcomingEventsPage.value * itemsPerPage;
    return dashboardStore.upcomingEvents.slice(start, start + itemsPerPage);
});

const totalUpcomingEventsPages = computed(() => {
    return Math.ceil(dashboardStore.upcomingEvents.length / itemsPerPage);
});

// Sort pending payouts by nearest event date
const sortedPendingPayouts = computed(() => {
    if (
        !dashboardStore.pendingPayouts ||
        dashboardStore.pendingPayouts.length === 0
    ) {
        return [];
    }

    // Get all unique organizers from pending payouts
    const organizerMap = new Map();

    dashboardStore.pendingPayouts.forEach((payout) => {
        if (!organizerMap.has(payout.organizer_id)) {
            organizerMap.set(payout.organizer_id, {
                ...payout,
                days_until: payout.nearest_event_days || 999999,
            });
        }
    });

    // Convert to array and sort by nearest event
    return Array.from(organizerMap.values())
        .sort((a, b) => a.days_until - b.days_until)
        .slice(0, 10);
});

const paginatedPendingPayouts = computed(() => {
    const start = pendingPayoutsPage.value * itemsPerPage;
    return sortedPendingPayouts.value.slice(start, start + itemsPerPage);
});

const totalPendingPayoutsPages = computed(() => {
    return Math.ceil(sortedPendingPayouts.value.length / itemsPerPage);
});

// Format date for chart X axis - parse manual para evitar problemas de timezone
const formatChartDate = (dateStr) => {
    // dateStr vem como "2026-02-12", fazemos parse manual
    const [year, month, day] = dateStr.split("-");
    return `${day}/${month}`;
};

// Chart configuration - computed para reagir às mudanças do store
const chartOptions = computed(() => ({
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
        categories: dashboardStore.salesTrend.map((item) =>
            formatChartDate(item.date),
        ),
        labels: {
            style: {
                colors: "#6B7280",
            },
            rotate: -45,
            rotateAlways: false,
        },
    },
    yaxis: {
        labels: {
            style: {
                colors: "#6B7280",
            },
            formatter: (value) => {
                return "R$ " + (value / 100).toLocaleString("pt-BR");
            },
        },
    },
    tooltip: {
        theme: "dark",
        y: {
            formatter: (value) => {
                return (
                    "R$ " +
                    (value / 100).toLocaleString("pt-BR", {
                        minimumFractionDigits: 2,
                    })
                );
            },
        },
    },
    markers: {
        size: 5,
        colors: ["#00E676"],
        strokeColors: "#0f1419",
        strokeWidth: 2,
        hover: {
            size: 7,
        },
    },
}));

const chartSeries = computed(() => [
    {
        name: "Receita",
        data: dashboardStore.salesTrend.map((item) => parseFloat(item.revenue)),
    },
]);

// Helper functions for date formatting
const formatEventDay = (date) => {
    return new Date(date).getDate();
};

const formatEventMonth = (date) => {
    const months = [
        "Jan",
        "Fev",
        "Mar",
        "Abr",
        "Mai",
        "Jun",
        "Jul",
        "Ago",
        "Set",
        "Out",
        "Nov",
        "Dez",
    ];
    return months[new Date(date).getMonth()];
};

// Carregar dados ao montar o componente
onMounted(async () => {
    await dashboardStore.fetchPlatformDashboard();
});
</script>

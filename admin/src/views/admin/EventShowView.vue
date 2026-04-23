<template>
    <div class="space-y-6">
        <!-- Loading State -->
        <div v-if="isLoading" class="space-y-6">
            <!-- Skeleton Header -->
            <div class="flex items-center gap-2 text-text-muted">
                <div
                    class="h-4 w-16 bg-surface-elevated rounded animate-pulse"
                ></div>
                <span>/</span>
                <div
                    class="h-4 w-32 bg-surface-elevated rounded animate-pulse"
                ></div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="space-y-2">
                        <div
                            class="h-8 w-64 bg-surface-elevated rounded animate-pulse"
                        ></div>
                        <div
                            class="h-4 w-40 bg-surface-elevated rounded animate-pulse"
                        ></div>
                    </div>
                </div>
                <div class="flex gap-3">
                    <div
                        class="h-10 w-24 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                    <div
                        class="h-10 w-32 bg-surface-elevated rounded-lg animate-pulse"
                    ></div>
                </div>
            </div>

            <!-- Skeleton Content -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <div
                    v-for="n in 3"
                    :key="n"
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6 animate-pulse"
                >
                    <div
                        class="h-4 w-24 bg-surface-elevated rounded mb-4"
                    ></div>
                    <div class="h-8 w-20 bg-surface-elevated rounded"></div>
                </div>
            </div>
        </div>

        <!-- Error State -->
        <ErrorState
            v-else-if="error"
            title="Erro ao carregar evento"
            :message="error"
            @retry="fetchEvent"
        />

        <!-- Content -->
        <div v-else-if="event" class="space-y-6">
            <!-- Breadcrumb -->
            <nav class="flex items-center gap-2 text-sm">
                <router-link
                    to="/admin/events"
                    class="text-primary hover:underline"
                >
                    Eventos
                </router-link>
                <span class="text-text-muted">/</span>
                <span class="text-text-muted">{{ event.title }}</span>
            </nav>

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-white">
                            {{ event.title }}
                        </h1>
                        <span :class="getStatusBadgeClass(event.status)">
                            {{ getStatusLabel(event.status) }}
                        </span>
                    </div>
                    <p class="text-text-muted text-sm mt-1">
                        Organizado por
                        <span class="text-primary">{{
                            event.organizer?.name
                        }}</span>
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3">
                    <button
                        @click="editEvent"
                        class="flex items-center gap-2 px-4 py-2.5 bg-primary text-black rounded-lg font-medium hover:brightness-110 transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]"
                            >edit</span
                        >
                        Editar Evento
                    </button>
                    <button
                        @click="confirmDelete"
                        class="flex items-center gap-2 px-4 py-2.5 border border-red-500 text-red-500 rounded-lg font-medium hover:bg-red-500/10 transition-colors"
                    >
                        <span class="material-symbols-outlined text-[20px]"
                            >delete</span
                        >
                        Deletar Evento
                    </button>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Total Inscritos -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center"
                        >
                            <span class="material-symbols-outlined text-primary"
                                >group</span
                            >
                        </div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider"
                        >
                            Total Inscritos
                        </p>
                    </div>
                    <p class="text-3xl font-bold text-white">
                        {{
                            stats.totalParticipants?.toLocaleString("pt-BR") ||
                            0
                        }}
                    </p>
                    <p class="text-text-muted text-xs mt-1">
                        {{ getParticipantsPercentage() }}
                    </p>
                </div>

                <!-- Receita-->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center"
                        >
                            <span class="material-symbols-outlined text-primary"
                                >payments</span
                            >
                        </div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider"
                        >
                            Receita
                        </p>
                    </div>
                    <p class="text-3xl font-bold text-white">
                        {{ formatCurrency(stats.totalRevenue || 0) }}
                    </p>
                    <p class="text-xs mt-1 flex items-center gap-1">
                        <span class="text-text-muted">Líquido:</span>
                        <span class="font-semibold text-white/80">{{ formatCurrency(stats.totalNetRevenue || 0) }}</span>
                        <div class="group relative">
                            <span class="material-symbols-outlined text-slate-500 text-sm cursor-help">info</span>
                            <div class="absolute bottom-full left-0 mb-2 hidden group-hover:block z-10">
                                <div class="bg-slate-900 text-white text-xs rounded-lg px-3 py-2 w-56 shadow-xl border border-slate-700">
                                    <p class="font-bold mb-1">Valor Líquido Estimado</p>
                                    <p class="text-slate-300">Valor após dedução das taxas do Mercado Pago, calculado com base nos pedidos já pagos deste evento.</p>
                                </div>
                                <div class="w-2 h-2 bg-slate-900 border-b border-r border-slate-700 absolute top-0 left-2 translate-y-1/2 rotate-45"></div>
                            </div>
                        </div>
                    </p>
                    <p class="text-text-muted text-xs mt-1">
                        Ticket Médio:
                        {{ formatCurrency(stats.avgTicketPrice || 0) }}
                    </p>
                </div>

                <!-- Vagas Restantes -->
                <div
                    class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                >
                    <div class="flex items-center gap-3 mb-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center"
                        >
                            <span class="material-symbols-outlined text-primary"
                                >confirmation_number</span
                            >
                        </div>
                        <p
                            class="text-text-muted text-xs uppercase tracking-wider"
                        >
                            Vagas Restantes
                        </p>
                    </div>
                    <p class="text-3xl font-bold text-white">
                        {{
                            event.max_participants
                                ? (
                                      event.max_participants -
                                      (stats.totalParticipants || 0)
                                  ).toLocaleString("pt-BR")
                                : "∞"
                        }}
                    </p>
                    <p
                        v-if="event.max_participants"
                        class="text-text-muted text-xs mt-1"
                    >
                        {{ getVacanciesPercentage() }} preenchido
                    </p>
                    <p v-else class="text-text-muted text-xs mt-1">Ilimitado</p>
                </div>
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Left Column (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Informações Gerais -->
                    <div
                        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                    >
                        <h3
                            class="flex items-center gap-2 text-white font-semibold mb-6"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-[20px]"
                                >info</span
                            >
                            Informações Gerais
                        </h3>

                        <div class="grid grid-cols-2 gap-6">
                            <div>
                                <p
                                    class="text-text-muted text-xs uppercase tracking-wider mb-1"
                                >
                                    Cidade do Evento
                                </p>
                                <p class="text-white">{{ event.city }}</p>
                            </div>

                            <div>
                                <p
                                    class="text-text-muted text-xs uppercase tracking-wider mb-1"
                                >
                                    Local do Evento
                                </p>
                                <p class="text-white">{{ event.venue }}</p>
                            </div>

                            <div>
                                <p
                                    class="text-text-muted text-xs uppercase tracking-wider mb-1"
                                >
                                    Início
                                </p>
                                <p class="text-white flex items-center gap-1.5">
                                    <span
                                        class="material-symbols-outlined text-text-muted text-[16px]"
                                        >calendar_today</span
                                    >
                                    {{ formatDateTime(event.date_start) }}
                                </p>
                            </div>

                            <div>
                                <p
                                    class="text-text-muted text-xs uppercase tracking-wider mb-1"
                                >
                                    Fim
                                </p>
                                <p class="text-white flex items-center gap-1.5">
                                    <span
                                        class="material-symbols-outlined text-text-muted text-[16px]"
                                        >calendar_today</span
                                    >
                                    {{ formatDateTime(event.date_end) }}
                                </p>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div
                            v-if="event.description"
                            class="mt-6 pt-6 border-t border-surface-elevated"
                        >
                            <p
                                class="text-text-muted text-xs uppercase tracking-wider mb-2"
                            >
                                Descrição
                            </p>
                            <p class="text-text-secondary whitespace-pre-wrap">
                                {{ event.description }}
                            </p>
                        </div>
                    </div>

                    <!-- Forma de Pagamento/Repasse -->
                    <div
                        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                    >
                        <h3
                            class="flex items-center gap-2 text-white font-semibold mb-4"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-[20px]"
                                >account_balance</span
                            >
                            Forma de Repasse
                        </h3>
                        <div v-if="event.payout_mode">
                            <span
                                class="px-3 py-1.5 rounded-full text-sm font-semibold inline-flex items-center gap-2"
                                :class="
                                    event.payout_mode === 'direct'
                                        ? 'bg-primary/10 text-primary border border-primary/20'
                                        : 'bg-blue-500/10 text-blue-400 border border-blue-500/20'
                                "
                            >
                                <span
                                    class="material-symbols-outlined text-[18px]"
                                    >{{
                                        {
                                            direct: "trending_flat",
                                            platform: "account_balance_wallet",
                                        }[event.payout_mode]
                                    }}</span
                                >
                                {{
                                    event.payout_mode === "direct"
                                        ? "Repasse Direto"
                                        : "Repasse via Plataforma"
                                }}
                            </span>
                            <p class="text-text-muted text-sm mt-3">
                                {{
                                    event.payout_mode === "direct"
                                        ? "Pagamentos vão diretamente para a conta do organizador"
                                        : "Pagamentos ficam na plataforma para repasse manual"
                                }}
                            </p>
                            <p
                                v-if="event.payout_provider"
                                class="text-text-muted text-xs mt-2"
                            >
                                Provedor: {{ event.payout_provider }}
                            </p>
                        </div>
                        <div
                            v-else
                            class="flex items-center gap-2 text-yellow-500"
                        >
                            <span class="material-symbols-outlined text-[20px]"
                                >warning</span
                            >
                            <p class="text-sm font-medium">
                                Forma de repasse não configurada
                            </p>
                        </div>
                    </div>

                    <!-- Categorias -->
                    <div
                        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3
                                class="flex items-center gap-2 text-white font-semibold"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-[20px]"
                                    >category</span
                                >
                                Categorias
                            </h3>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-[10px] bg-white/5 px-2 py-0.5 rounded text-slate-400"
                                    >{{
                                        event.categories?.length || 0
                                    }}
                                    categorias</span
                                >
                                <button
                                    @click="openCategoryModal()"
                                    class="flex items-center gap-2 px-4 py-2 bg-primary text-black rounded-lg font-medium hover:brightness-110 transition-colors text-sm"
                                >
                                    <span
                                        class="material-symbols-outlined text-[18px]"
                                        >add</span
                                    >
                                    Adicionar
                                </button>
                            </div>
                        </div>

                        <div v-if="event.categories?.length">
                            <table class="w-full">
                                <thead>
                                    <tr
                                        class="text-left text-text-muted text-xs uppercase tracking-wider"
                                    >
                                        <th class="pb-3">Distância</th>
                                        <th class="pb-3">Nome</th>
                                        <th class="pb-3">Inscritos</th>
                                        <th class="pb-3">Status</th>
                                        <th class="pb-3 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="category in paginatedCategories"
                                        :key="category.id"
                                        class="border-t border-surface-elevated h-[65px]"
                                    >
                                        <td class="py-3">
                                            <span
                                                class="inline-flex items-center justify-center px-3 py-1 rounded bg-primary/10 text-primary font-bold text-sm"
                                            >
                                                {{
                                                    formatDistance(
                                                        category.distance,
                                                    )
                                                }}k
                                            </span>
                                        </td>
                                        <td class="py-3">
                                            <p class="text-white font-medium">
                                                {{ category.name }}
                                            </p>
                                            <p class="text-text-muted text-xs">
                                                {{
                                                    category.description ||
                                                    "Sem descrição"
                                                }}
                                            </p>
                                        </td>
                                        <td class="py-3 text-text-muted">
                                            {{
                                                category.participants_count || 0
                                            }}
                                            inscritos
                                        </td>
                                        <td class="py-3">
                                            <span
                                                :class="getCategoryStatusClass(category.active)"
                                            >
                                                {{ getCategoryStatusLabel(category.active) }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <div
                                                class="flex items-center justify-end gap-2"
                                            >
                                                <button
                                                    @click="
                                                        openCategoryModal(
                                                            category,
                                                        )
                                                    "
                                                    class="p-1.5 text-text-muted hover:text-primary rounded transition-colors"
                                                    title="Editar categoria"
                                                >
                                                    <span
                                                        class="material-symbols-outlined text-[18px]"
                                                        >edit</span
                                                    >
                                                </button>
                                                <button
                                                    @click="
                                                        confirmDeleteCategory(
                                                            category,
                                                        )
                                                    "
                                                    class="p-1.5 text-text-muted hover:text-red-400 rounded transition-colors"
                                                    title="Deletar categoria"
                                                >
                                                    <span
                                                        class="material-symbols-outlined text-[18px]"
                                                        >delete</span
                                                    >
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Empty rows to maintain fixed height -->
                                    <tr
                                        v-for="n in itemsPerPage -
                                        paginatedCategories.length"
                                        :key="'empty-cat-' + n"
                                        class="border-t border-surface-elevated h-[65px]"
                                    >
                                        <td colspan="4"></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination Controls -->
                            <div
                                v-if="totalCategoriesPages > 1"
                                class="flex justify-center items-center gap-4 pt-4 mt-4 border-t border-surface-elevated"
                            >
                                <button
                                    @click="
                                        categoriesPage = Math.max(
                                            0,
                                            categoriesPage - 1,
                                        )
                                    "
                                    :disabled="categoriesPage === 0"
                                    class="flex items-center justify-center w-8 h-8 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <span
                                        class="material-symbols-outlined text-base"
                                        >chevron_left</span
                                    >
                                </button>
                                <span class="text-text-muted text-sm">
                                    {{ categoriesPage + 1 }} /
                                    {{ totalCategoriesPages }}
                                </span>
                                <button
                                    @click="
                                        categoriesPage = Math.min(
                                            totalCategoriesPages - 1,
                                            categoriesPage + 1,
                                        )
                                    "
                                    :disabled="
                                        categoriesPage >=
                                        totalCategoriesPages - 1
                                    "
                                    class="flex items-center justify-center w-8 h-8 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <span
                                        class="material-symbols-outlined text-base"
                                        >chevron_right</span
                                    >
                                </button>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <span
                                class="material-symbols-outlined text-text-muted text-4xl mb-2"
                            >
                                category
                            </span>
                            <p class="text-text-muted">
                                Nenhuma categoria cadastrada
                            </p>
                        </div>
                    </div>

                    <!-- Tipos de Ingresso -->
                    <div
                        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                    >
                        <div class="flex items-center justify-between mb-6">
                            <h3
                                class="flex items-center gap-2 text-white font-semibold"
                            >
                                <span
                                    class="material-symbols-outlined text-primary text-[20px]"
                                    >confirmation_number</span
                                >
                                Tipos de Ingresso
                            </h3>
                            <div class="flex items-center gap-3">
                                <span
                                    class="text-[10px] bg-white/5 px-2 py-0.5 rounded text-slate-400"
                                    >{{
                                        event.ticket_types?.length || 0
                                    }}
                                    tipos</span
                                >
                                <button
                                    @click="openTicketTypeModal()"
                                    class="flex items-center gap-2 px-4 py-2 bg-primary text-black rounded-lg font-medium hover:brightness-110 transition-colors text-sm"
                                >
                                    <span
                                        class="material-symbols-outlined text-[18px]"
                                        >add</span
                                    >
                                    Adicionar
                                </button>
                            </div>
                        </div>

                        <div v-if="event.ticket_types?.length">
                            <table class="w-full">
                                <thead>
                                    <tr
                                        class="text-left text-text-muted text-xs uppercase tracking-wider"
                                    >
                                        <th class="pb-3">Nome</th>
                                        <th class="pb-3">Valor</th>
                                        <th class="pb-3">Status</th>
                                        <th class="pb-3 text-right">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="ticket in paginatedTicketTypes"
                                        :key="ticket.id"
                                        class="border-t border-surface-elevated h-[65px]"
                                    >
                                        <td class="py-3 text-white">
                                            {{ ticket.name }}
                                        </td>
                                        <td
                                            class="py-3 text-primary font-medium"
                                        >
                                            {{
                                                formatCurrency(
                                                    (ticket.price_cents || 0) /
                                                        100,
                                                )
                                            }}
                                        </td>
                                        <td class="py-3">
                                            <span
                                                :class="
                                                    getTicketStatusClass(
                                                        getTicketStatus(ticket),
                                                    )
                                                "
                                            >
                                                {{
                                                    getTicketStatusLabel(
                                                        getTicketStatus(ticket),
                                                    )
                                                }}
                                            </span>
                                        </td>
                                        <td class="py-3 text-right">
                                            <div
                                                class="flex items-center justify-end gap-2"
                                            >
                                                <button
                                                    @click="
                                                        openTicketTypeModal(
                                                            ticket,
                                                        )
                                                    "
                                                    class="p-1.5 text-text-muted hover:text-primary rounded transition-colors"
                                                    title="Editar tipo de ingresso"
                                                >
                                                    <span
                                                        class="material-symbols-outlined text-[18px]"
                                                        >edit</span
                                                    >
                                                </button>
                                                <button
                                                    @click="
                                                        confirmDeleteTicketType(
                                                            ticket,
                                                        )
                                                    "
                                                    class="p-1.5 text-text-muted hover:text-red-400 rounded transition-colors"
                                                    title="Deletar tipo de ingresso"
                                                >
                                                    <span
                                                        class="material-symbols-outlined text-[18px]"
                                                        >delete</span
                                                    >
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <!-- Empty rows to maintain fixed height -->
                                    <tr
                                        v-for="n in itemsPerPage -
                                        paginatedTicketTypes.length"
                                        :key="'empty-ticket-' + n"
                                        class="border-t border-surface-elevated h-[65px]"
                                    >
                                        <td colspan="4"></td>
                                    </tr>
                                </tbody>
                            </table>

                            <!-- Pagination Controls -->
                            <div
                                v-if="totalTicketTypesPages > 1"
                                class="flex justify-center items-center gap-4 pt-4 mt-4 border-t border-surface-elevated"
                            >
                                <button
                                    @click="
                                        ticketTypesPage = Math.max(
                                            0,
                                            ticketTypesPage - 1,
                                        )
                                    "
                                    :disabled="ticketTypesPage === 0"
                                    class="flex items-center justify-center w-8 h-8 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <span
                                        class="material-symbols-outlined text-base"
                                        >chevron_left</span
                                    >
                                </button>
                                <span class="text-text-muted text-sm">
                                    {{ ticketTypesPage + 1 }} /
                                    {{ totalTicketTypesPages }}
                                </span>
                                <button
                                    @click="
                                        ticketTypesPage = Math.min(
                                            totalTicketTypesPages - 1,
                                            ticketTypesPage + 1,
                                        )
                                    "
                                    :disabled="
                                        ticketTypesPage >=
                                        totalTicketTypesPages - 1
                                    "
                                    class="flex items-center justify-center w-8 h-8 rounded-full bg-surface-elevated border border-border-dark text-slate-400 hover:bg-white/10 hover:text-white transition-all disabled:opacity-30 disabled:cursor-not-allowed"
                                >
                                    <span
                                        class="material-symbols-outlined text-base"
                                        >chevron_right</span
                                    >
                                </button>
                            </div>
                        </div>

                        <div v-else class="text-center py-8">
                            <span
                                class="material-symbols-outlined text-text-muted text-4xl mb-2"
                            >
                                confirmation_number
                            </span>
                            <p class="text-text-muted">
                                Nenhum tipo de ingresso cadastrado
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Banner do Evento -->
                    <div
                        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                    >
                        <h3
                            class="flex items-center gap-2 text-white font-semibold mb-4"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-[20px]"
                                >image</span
                            >
                            Banner do Evento
                        </h3>

                        <div
                            class="aspect-video rounded-lg bg-surface-elevated overflow-hidden"
                        >
                            <img
                                v-if="event.banner_url"
                                :src="event.banner_url"
                                :alt="event.title"
                                class="w-full h-full object-cover"
                            />
                            <div
                                v-else
                                class="w-full h-full flex items-center justify-center"
                            >
                                <span
                                    class="material-symbols-outlined text-text-muted text-4xl"
                                >
                                    image
                                </span>
                            </div>
                        </div>

                        <button
                            @click="editEvent"
                            class="w-full mt-4 py-2 border border-surface-elevated text-text-muted rounded-lg hover:border-primary hover:text-primary transition-colors text-sm"
                        >
                            Trocar Imagem
                        </button>
                    </div>

                    <!-- Meta Dados -->
                    <div
                        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
                    >
                        <h3
                            class="flex items-center gap-2 text-white font-semibold mb-4"
                        >
                            <span
                                class="material-symbols-outlined text-primary text-[20px]"
                                >data_object</span
                            >
                            Meta Dados
                        </h3>

                        <div class="space-y-3">
                            <div
                                class="flex items-center justify-between py-2 border-b border-surface-elevated"
                            >
                                <span class="text-text-muted text-sm"
                                    >Criado em:</span
                                >
                                <span class="text-white text-sm">{{
                                    formatDate(event.created_at)
                                }}</span>
                            </div>
                            <div
                                class="flex items-center justify-between py-2 border-b border-surface-elevated"
                            >
                                <span class="text-text-muted text-sm"
                                    >Última atualização:</span
                                >
                                <span class="text-white text-sm">{{
                                    formatDate(event.updated_at)
                                }}</span>
                            </div>
                            <div class="flex items-center justify-between py-2">
                                <span class="text-text-muted text-sm"
                                    >ID do Evento:</span
                                >
                                <span class="text-text-muted text-sm font-mono"
                                    >#EVT-{{
                                        String(event.id).padStart(4, "0")
                                    }}</span
                                >
                            </div>
                        </div>

                        <button
                            class="w-full mt-4 py-2 border border-surface-elevated text-text-muted rounded-lg hover:border-primary hover:text-primary transition-colors text-sm flex items-center justify-center gap-2"
                        >
                            <span class="material-symbols-outlined text-[18px]"
                                >share</span
                            >
                            Compartilhar Página
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação de Exclusão -->
        <Modal
            v-model="showDeleteModal"
            title="Deletar Evento"
            :subtitle="event?.title"
        >
            <p class="text-text-secondary">
                Tem certeza que deseja excluir permanentemente
                <strong class="text-white">{{ event?.title }}</strong
                >?
            </p>
            <p class="text-sm text-red-400 mt-2">
                Esta ação não pode ser desfeita. Todos os dados, inscrições e
                pedidos relacionados serão removidos.
            </p>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteModal = false"
                        class="px-4 py-2 text-text-muted hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="deleteEvent"
                        :disabled="isDeleting"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-colors flex items-center gap-2 disabled:opacity-50"
                    >
                        <span
                            v-if="isDeleting"
                            class="material-symbols-outlined text-[18px] animate-spin"
                            >progress_activity</span
                        >
                        <span
                            v-else
                            class="material-symbols-outlined text-[18px]"
                            >delete</span
                        >
                        {{ isDeleting ? "Excluindo..." : "Excluir" }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Modal de Confirmação de Exclusão de Categoria -->
        <Modal
            v-model="showDeleteCategoryModal"
            title="Deletar Categoria"
            :subtitle="categoryToDelete?.name"
        >
            <p class="text-text-secondary">
                Tem certeza que deseja excluir a categoria
                <strong class="text-white">{{ categoryToDelete?.name }}</strong
                >?
            </p>
            <p class="text-sm text-red-400 mt-2">
                Esta ação não pode ser desfeita.
            </p>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteCategoryModal = false"
                        class="px-4 py-2 text-text-muted hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="deleteCategory"
                        :disabled="isDeletingCategory"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-colors flex items-center gap-2 disabled:opacity-50"
                    >
                        <span
                            v-if="isDeletingCategory"
                            class="material-symbols-outlined text-[18px] animate-spin"
                            >progress_activity</span
                        >
                        <span
                            v-else
                            class="material-symbols-outlined text-[18px]"
                            >delete</span
                        >
                        {{ isDeletingCategory ? "Excluindo..." : "Excluir" }}
                    </button>
                </div>
            </template>
        </Modal>

        <!-- Modal de Confirmação de Exclusão de Tipo de Ingresso -->
        <Modal
            v-model="showDeleteTicketTypeModal"
            title="Deletar Tipo de Ingresso"
            :subtitle="ticketTypeToDelete?.name"
        >
            <p class="text-text-secondary">
                Tem certeza que deseja excluir o tipo de ingresso
                <strong class="text-white">{{
                    ticketTypeToDelete?.name
                }}</strong
                >?
            </p>
            <p class="text-sm text-red-400 mt-2">
                Esta ação não pode ser desfeita.
            </p>

            <template #footer>
                <div class="flex justify-end gap-3">
                    <button
                        @click="showDeleteTicketTypeModal = false"
                        class="px-4 py-2 text-text-muted hover:text-white transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="deleteTicketType"
                        :disabled="isDeletingTicketType"
                        class="px-4 py-2 bg-red-500 text-white rounded-lg font-medium hover:bg-red-600 transition-colors flex items-center gap-2 disabled:opacity-50"
                    >
                        <span
                            v-if="isDeletingTicketType"
                            class="material-symbols-outlined text-[18px] animate-spin"
                            >progress_activity</span
                        >
                        <span
                            v-else
                            class="material-symbols-outlined text-[18px]"
                            >delete</span
                        >
                        {{ isDeletingTicketType ? "Excluindo..." : "Excluir" }}
                    </button>
                </div>
            </template>
        </Modal>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useEventsStore } from "@/stores/events";
import { useToast } from "@/composables/useToast";
import api from "@/api/axios";
import ErrorState from "@/components/ui/ErrorState.vue";
import Modal from "@/components/ui/Modal.vue";

const route = useRoute();
const router = useRouter();
const store = useEventsStore();
const toast = useToast();

// State
const isLoading = ref(true);
const error = ref(null);
const event = ref(null);
const stats = ref({
    totalParticipants: 0,
    totalRevenue: 0,
    avgTicketPrice: 0,
});
const showDeleteModal = ref(false);
const isDeleting = ref(false);

// Category delete modal state
const showDeleteCategoryModal = ref(false);
const categoryToDelete = ref(null);
const isDeletingCategory = ref(false);

// Ticket type delete modal state
const showDeleteTicketTypeModal = ref(false);
const ticketTypeToDelete = ref(null);
const isDeletingTicketType = ref(false);

// Pagination state for categories and ticket types
const categoriesPage = ref(0);
const ticketTypesPage = ref(0);
const itemsPerPage = 5;

// Computed - Paginated Categories
const paginatedCategories = computed(() => {
    if (!event.value?.categories) return [];
    const start = categoriesPage.value * itemsPerPage;
    return event.value.categories.slice(start, start + itemsPerPage);
});

const totalCategoriesPages = computed(() => {
    if (!event.value?.categories) return 0;
    return Math.ceil(event.value.categories.length / itemsPerPage);
});

// Computed - Paginated Ticket Types
const paginatedTicketTypes = computed(() => {
    if (!event.value?.ticket_types) return [];
    const start = ticketTypesPage.value * itemsPerPage;
    return event.value.ticket_types.slice(start, start + itemsPerPage);
});

const totalTicketTypesPages = computed(() => {
    if (!event.value?.ticket_types) return 0;
    return Math.ceil(event.value.ticket_types.length / itemsPerPage);
});

// Methods
const fetchEvent = async () => {
    isLoading.value = true;
    error.value = null;

    try {
        const result = await store.fetchEvent(route.params.id);
        if (result.success) {
            event.value = result.data;
            // Calcular stats básicas (substituir por dados reais da API)
            calculateStats();
        } else {
            error.value = result.error;
        }
    } catch (err) {
        error.value = "Erro ao carregar evento";
    } finally {
        isLoading.value = false;
    }
};

const calculateStats = () => {
    // TODO: Pegar dados reais da API
    const participants = event.value.participants_count || 0;
    const revenue = event.value.total_revenue || 0;

    const netRevenue = event.value.total_net_revenue || 0;

    stats.value = {
        totalParticipants: participants,
        totalRevenue: revenue / 100,
        totalNetRevenue: netRevenue / 100,
        avgTicketPrice: participants > 0 ? revenue / 100 / participants : 0,
    };
};

const getParticipantsPercentage = () => {
    if (!event.value?.max_participants) return "Sem limite de vagas";
    const percentage =
        (stats.value.totalParticipants / event.value.max_participants) * 100;
    return `${percentage.toFixed(0)}% das vagas preenchidas`;
};

const getVacanciesPercentage = () => {
    if (!event.value?.max_participants) return "";
    const percentage =
        (stats.value.totalParticipants / event.value.max_participants) * 100;
    return `${percentage.toFixed(0)}%`;
};

const confirmDelete = () => {
    showDeleteModal.value = true;
};

const deleteEvent = async () => {
    isDeleting.value = true;
    try {
        const result = await store.deleteEvent(event.value.id);
        if (result.success) {
            toast.success("Evento excluído com sucesso");
            router.push("/admin/events");
        } else {
            toast.error(result.error);
        }
    } finally {
        isDeleting.value = false;
        showDeleteModal.value = false;
    }
};

const editEvent = () => {
    router.push(`/admin/events/${event.value.id}/edit`);
};

// Category delete methods
const confirmDeleteCategory = (category) => {
    categoryToDelete.value = category;
    showDeleteCategoryModal.value = true;
};

const deleteCategory = async () => {
    if (!categoryToDelete.value) return;

    isDeletingCategory.value = true;
    try {
        await api.delete(
            `/admin/events/${event.value.id}/categories/${categoryToDelete.value.id}`,
        );

        // Remove from local state
        event.value.categories = event.value.categories.filter(
            (c) => c.id !== categoryToDelete.value.id,
        );

        toast.success("Categoria excluída com sucesso");
    } catch (err) {
        toast.error(err.response?.data?.message || "Erro ao excluir categoria");
    } finally {
        isDeletingCategory.value = false;
        showDeleteCategoryModal.value = false;
        categoryToDelete.value = null;
    }
};

// Ticket type delete methods
const confirmDeleteTicketType = (ticketType) => {
    ticketTypeToDelete.value = ticketType;
    showDeleteTicketTypeModal.value = true;
};

const deleteTicketType = async () => {
    if (!ticketTypeToDelete.value) return;

    isDeletingTicketType.value = true;
    try {
        await api.delete(
            `/admin/events/${event.value.id}/ticket-types/${ticketTypeToDelete.value.id}`,
        );

        // Remove from local state
        event.value.ticket_types = event.value.ticket_types.filter(
            (t) => t.id !== ticketTypeToDelete.value.id,
        );

        toast.success("Tipo de ingresso excluído com sucesso");
    } catch (err) {
        toast.error(
            err.response?.data?.message || "Erro ao excluir tipo de ingresso",
        );
    } finally {
        isDeletingTicketType.value = false;
        showDeleteTicketTypeModal.value = false;
        ticketTypeToDelete.value = null;
    }
};

// Navigation methods for category and ticket type forms
const openCategoryModal = (category = null) => {
    if (category) {
        router.push(
            `/admin/events/${event.value.id}/categories/${category.id}/edit`,
        );
    } else {
        router.push(`/admin/events/${event.value.id}/categories/create`);
    }
};

const openTicketTypeModal = (ticketType = null) => {
    if (ticketType) {
        router.push(
            `/admin/events/${event.value.id}/ticket-types/${ticketType.id}/edit`,
        );
    } else {
        router.push(`/admin/events/${event.value.id}/ticket-types/create`);
    }
};

// Formatters
const formatDateTime = (date) => {
    if (!date) return "-";
    const d = new Date(date);
    return (
        d.toLocaleDateString("pt-BR", {
            day: "2-digit",
            month: "2-digit",
            year: "numeric",
        }) +
        " - " +
        d.toLocaleTimeString("pt-BR", {
            hour: "2-digit",
            minute: "2-digit",
        })
    );
};

const formatDate = (date) => {
    if (!date) return "-";
    const d = new Date(date);
    return d.toLocaleDateString("pt-BR");
};

const formatCurrency = (value) => {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format(value);
};

const formatDistance = (value) => {
    if (value == null || value === "") {
        return "?";
    }
    const num = parseFloat(value);
    if (isNaN(num)) {
        return "?";
    }
    // Remove trailing zeros: 10.00 -> 10, 21.10 -> 21.1, 21.15 -> 21.15
    return num % 1 === 0 ? num.toFixed(0) : num.toString();
};

// Status helpers
const getStatusLabel = (status) => {
    const labels = {
        ativo: "Ativo",
        inativo: "Inativo",
    };
    return labels[status] || status;
};

const getStatusBadgeClass = (status) => {
    const classes = {
        ativo: "px-3 py-1 rounded-full text-xs font-semibold bg-primary text-black",
        inativo:
            "px-3 py-1 rounded-full text-xs font-semibold bg-yellow-500 text-black",
    };
    return classes[status] || classes.inativo;
};

const getCategoryStatusLabel = (active) => active ? "Ativa" : "Inativa";

const getCategoryStatusClass = (active) =>
    active
        ? "px-2 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary"
        : "px-2 py-0.5 rounded text-xs font-medium bg-red-500/10 text-red-400";

const getTicketStatusLabel = (status) => {
    const labels = {
        active: "Vendas Abertas",
        inactive: "Inativo",
        upcoming: "Em Breve",
        ended: "Encerrado",
        soldout: "Esgotado",
    };
    return labels[status] || status;
};

const getTicketStatusClass = (status) => {
    const classes = {
        active: "px-2 py-0.5 rounded text-xs font-medium bg-primary/10 text-primary",
        inactive:
            "px-2 py-0.5 rounded text-xs font-medium bg-red-500/10 text-red-400",
        upcoming:
            "px-2 py-0.5 rounded text-xs font-medium bg-blue-500/10 text-blue-400",
        ended: "px-2 py-0.5 rounded text-xs font-medium bg-gray-500/10 text-gray-400",
        soldout:
            "px-2 py-0.5 rounded text-xs font-medium bg-yellow-500/10 text-yellow-400",
    };
    return classes[status] || classes.inactive;
};

// Deriva o status do ticket baseado em active, start_sale, end_sale e quota
const getTicketStatus = (ticket) => {
    if (!ticket.active) return "inactive";

    const now = new Date();
    const startSale = ticket.start_sale ? new Date(ticket.start_sale) : null;
    const endSale = ticket.end_sale ? new Date(ticket.end_sale) : null;

    // Verifica se está esgotado (quota preenchida)
    if (ticket.quota && ticket.tickets_sold_count >= ticket.quota) {
        return "soldout";
    }

    // Verifica período de vendas
    if (startSale && now < startSale) return "upcoming";
    if (endSale && now > endSale) return "ended";

    return "active";
};

// Lifecycle
onMounted(() => {
    fetchEvent();
});

// Watch route to reload data when returning from category/ticket-type forms
watch(
    () => route.path,
    () => {
        if (route.name === "admin-event-show") {
            fetchEvent();
        }
    },
);
</script>

<template>
    <div class="min-h-screen bg-background-dark text-slate-100">
        <Navbar :hide-search="true" :hide-actions="true" />

        <main class="max-w-[1440px] mx-auto w-full px-4 md:px-10 py-10">
            <!-- Loading State -->
            <div
                v-if="loading"
                class="flex items-center justify-center min-h-[60vh]"
            >
                <div
                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary"
                ></div>
            </div>

            <!-- Error State -->
            <div
                v-else-if="error"
                class="flex flex-col items-center justify-center min-h-[60vh] gap-4"
            >
                <svg
                    class="w-16 h-16 text-red-500"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                >
                    <path
                        fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                        clip-rule="evenodd"
                    />
                </svg>
                <h2 class="text-2xl font-bold text-white">{{ error }}</h2>
                <button
                    @click="router.push({ name: 'events' })"
                    class="mt-4 px-6 py-3 bg-primary text-background-dark font-bold rounded-lg hover:bg-primary/90 transition-colors"
                >
                    Voltar para Eventos
                </button>
            </div>

            <!-- Payment Content -->
            <div v-else class="grid grid-cols-12 gap-8">
                <!-- Left Column -->
                <div class="col-span-12 lg:col-span-8 flex flex-col gap-6">
                    <!-- Section 1: Identification -->
                    <section
                        class="bg-surface-dark rounded-xl border border-border-dark p-8"
                    >
                        <div class="flex items-center gap-3 mb-8">
                            <span
                                class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-sm font-bold border border-primary/20"
                            >
                                1
                            </span>
                            <h2
                                class="text-xl font-bold text-white uppercase tracking-wider"
                            >
                                Identificação
                            </h2>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                            <div class="col-span-1 sm:col-span-2">
                                <label
                                    class="block text-sm font-medium text-slate-400 mb-2"
                                    >Nome Completo</label
                                >
                                <input
                                    v-model="buyerInfo.name"
                                    type="text"
                                    placeholder="Como no documento"
                                    class="input-field"
                                />
                            </div>
                            <div class="col-span-1">
                                <label
                                    class="block text-sm font-medium text-slate-400 mb-2"
                                    >E-mail</label
                                >
                                <input
                                    v-model="buyerInfo.email"
                                    type="email"
                                    placeholder="seu@email.com"
                                    :class="[
                                        'input-field',
                                        fieldErrors.email
                                            ? 'border-red-500 focus:border-red-500'
                                            : '',
                                    ]"
                                />
                                <p
                                    v-if="fieldErrors.email"
                                    class="text-red-400 text-xs mt-1"
                                >
                                    {{ fieldErrors.email }}
                                </p>
                            </div>
                            <div class="col-span-1">
                                <label
                                    class="block text-sm font-medium text-slate-400 mb-2"
                                    >CPF</label
                                >
                                <input
                                    v-model="buyerInfo.cpf"
                                    @input="handleCPFInput"
                                    maxlength="14"
                                    placeholder="000.000.000-00"
                                    class="input-field"
                                    type="text"
                                    inputmode="numeric"
                                />
                            </div>
                        </div>
                    </section>

                    <!-- Section 2: Payment Method -->
                    <section
                        class="bg-surface-dark rounded-xl border border-border-dark p-8"
                    >
                        <div class="flex items-center gap-3 mb-8">
                            <span
                                class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary text-sm font-bold border border-primary/20"
                            >
                                2
                            </span>
                            <h2
                                class="text-xl font-bold text-white uppercase tracking-wider"
                            >
                                Pagamento
                            </h2>
                        </div>

                        <!-- Payment Method Tabs -->
                        <div
                            class="flex gap-1 mb-8 p-1 bg-surface-darker rounded-xl border border-border-dark"
                        >
                            <button
                                v-for="tab in paymentTabs"
                                :key="tab.id"
                                @click="activeTab = tab.id"
                                :class="[
                                    'flex-1 flex items-center justify-center gap-2 py-3 px-3 rounded-lg text-sm font-semibold transition-all',
                                    activeTab === tab.id
                                        ? 'bg-primary/10 text-primary border border-primary/30'
                                        : 'text-slate-400 hover:text-slate-200',
                                ]"
                            >
                                <component
                                    :is="tab.icon"
                                    class="w-4 h-4 shrink-0"
                                />
                                <span class="hidden sm:inline">{{
                                    tab.label
                                }}</span>
                                <span class="sm:hidden">{{ tab.short }}</span>
                            </button>
                        </div>

                        <!-- Credit / Debit Card Form -->
                        <div
                            v-if="
                                activeTab === 'credit' || activeTab === 'debit'
                            "
                            class="space-y-6"
                        >
                            <!-- Card Number -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-400 mb-2"
                                    >Número do Cartão</label
                                >
                                <div class="relative">
                                    <input
                                        v-model="cardData.number"
                                        @input="handleCardNumberInput"
                                        maxlength="19"
                                        placeholder="0000 0000 0000 0000"
                                        :class="[
                                            'input-field pr-28',
                                            fieldErrors.number
                                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                                                : '',
                                        ]"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="cc-number"
                                    />
                                    <div
                                        class="absolute right-4 top-1/2 -translate-y-1/2 flex items-center gap-2"
                                    >
                                        <span
                                            v-if="cardBrand"
                                            class="text-xs font-bold uppercase tracking-wider px-2 py-1 rounded bg-primary/10 text-primary border border-primary/20"
                                        >
                                            {{ cardBrandLabel }}
                                        </span>
                                        <div
                                            v-else
                                            class="flex gap-1 opacity-20"
                                        >
                                            <!-- Visa placeholder -->
                                            <div
                                                class="w-8 h-5 bg-slate-600 rounded text-center text-[8px] leading-5 font-black text-white"
                                            >
                                                VISA
                                            </div>
                                            <div
                                                class="w-8 h-5 rounded overflow-hidden relative"
                                            >
                                                <div
                                                    class="absolute left-0 top-0 w-5 h-5 rounded-full bg-red-500 opacity-80"
                                                ></div>
                                                <div
                                                    class="absolute right-0 top-0 w-5 h-5 rounded-full bg-yellow-400 opacity-80"
                                                ></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <p
                                    v-if="fieldErrors.number"
                                    class="text-red-400 text-xs mt-1"
                                >
                                    {{ fieldErrors.number }}
                                </p>
                            </div>

                            <!-- Cardholder Name -->
                            <div>
                                <label
                                    class="block text-sm font-medium text-slate-400 mb-2"
                                    >Nome Impresso no Cartão</label
                                >
                                <input
                                    v-model="cardData.holderName"
                                    @input="
                                        cardData.holderName =
                                            cardData.holderName.toUpperCase()
                                    "
                                    type="text"
                                    placeholder="COMO APARECE NO CARTÃO"
                                    :class="[
                                        'input-field uppercase',
                                        fieldErrors.holderName
                                            ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                                            : '',
                                    ]"
                                    autocomplete="cc-name"
                                />
                                <p
                                    v-if="fieldErrors.holderName"
                                    class="text-red-400 text-xs mt-1"
                                >
                                    {{ fieldErrors.holderName }}
                                </p>
                            </div>

                            <!-- Expiry + CVV -->
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-slate-400 mb-2"
                                        >Data de Validade</label
                                    >
                                    <input
                                        v-model="cardData.expiry"
                                        @input="handleExpiryInput"
                                        maxlength="5"
                                        placeholder="MM/AA"
                                        :class="[
                                            'input-field',
                                            fieldErrors.expiry
                                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                                                : '',
                                        ]"
                                        type="text"
                                        inputmode="numeric"
                                        autocomplete="cc-exp"
                                    />
                                    <p
                                        v-if="fieldErrors.expiry"
                                        class="text-red-400 text-xs mt-1"
                                    >
                                        {{ fieldErrors.expiry }}
                                    </p>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <label
                                            class="text-sm font-medium text-slate-400"
                                            >CVV</label
                                        >
                                        <div class="relative group">
                                            <svg
                                                class="w-4 h-4 text-slate-500 cursor-help"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                            <div
                                                class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 w-48 bg-surface-darker border border-border-dark rounded-lg p-2 text-xs text-slate-300 hidden group-hover:block z-10 pointer-events-none"
                                            >
                                                3 dígitos no verso do cartão.
                                                American Express: 4 dígitos na
                                                frente.
                                            </div>
                                        </div>
                                    </div>
                                    <input
                                        v-model="cardData.cvv"
                                        :maxlength="
                                            cardBrand === 'amex' ? 4 : 3
                                        "
                                        placeholder="000"
                                        :class="[
                                            'input-field',
                                            fieldErrors.cvv
                                                ? 'border-red-500 focus:border-red-500 focus:ring-red-500/20'
                                                : '',
                                        ]"
                                        type="password"
                                        inputmode="numeric"
                                        autocomplete="cc-csc"
                                    />
                                    <p
                                        v-if="fieldErrors.cvv"
                                        class="text-red-400 text-xs mt-1"
                                    >
                                        {{ fieldErrors.cvv }}
                                    </p>
                                </div>
                            </div>

                            <!-- Installments (credit only) -->
                            <div v-if="activeTab === 'credit'">
                                <label
                                    class="block text-sm font-medium text-slate-400 mb-2"
                                    >Parcelamento</label
                                >
                                <div class="relative">
                                    <select
                                        v-model="selectedInstallment"
                                        :disabled="
                                            !installments.length ||
                                            loadingInstallments
                                        "
                                        class="input-field appearance-none pr-10 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        <option
                                            v-if="loadingInstallments"
                                            :value="null"
                                        >
                                            Carregando parcelas...
                                        </option>
                                        <option
                                            v-else-if="!installments.length"
                                            :value="null"
                                        >
                                            Digite o número do cartão para ver
                                            as parcelas
                                        </option>
                                        <option
                                            v-for="inst in installments"
                                            :key="inst.installments"
                                            :value="inst"
                                        >
                                            {{ inst.recommended_message }}
                                        </option>
                                    </select>
                                    <div
                                        class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-slate-400"
                                    >
                                        <svg
                                            class="w-4 h-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PIX Form -->
                        <div v-else-if="activeTab === 'pix'">
                            <!-- Before generating -->
                            <div v-if="!pixData">
                                <div class="flex items-center gap-4 mb-8">
                                    <div
                                        class="size-14 bg-primary/10 rounded-2xl border border-primary/20 flex items-center justify-center shrink-0"
                                    >
                                        <svg
                                            class="w-7 h-7 text-primary"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                        >
                                            <path
                                                d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"
                                            />
                                        </svg>
                                    </div>
                                    <div>
                                        <h3
                                            class="text-white font-bold text-lg"
                                        >
                                            Pague via PIX
                                        </h3>
                                        <p class="text-slate-400 text-sm mt-1">
                                            Confirmação imediata após o
                                            pagamento
                                        </p>
                                    </div>
                                </div>

                                <!-- Steps -->
                                <div class="space-y-4 mb-8">
                                    <div
                                        v-for="(step, i) in pixSteps"
                                        :key="i"
                                        class="flex items-start gap-4"
                                    >
                                        <span
                                            class="flex items-center justify-center size-7 rounded-full bg-primary/10 text-primary text-xs font-bold border border-primary/20 shrink-0 mt-0.5"
                                        >
                                            {{ i + 1 }}
                                        </span>
                                        <p
                                            class="text-slate-300 text-sm leading-relaxed"
                                        >
                                            {{ step }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- QR Code generated -->
                            <div v-else class="text-center">
                                <div
                                    class="flex items-center justify-center gap-2 text-primary mb-2"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <p class="text-white font-bold text-lg">
                                        QR Code PIX gerado!
                                    </p>
                                </div>
                                <p class="text-slate-400 text-sm mb-6">
                                    Escaneie o código ou copie a chave PIX
                                    abaixo
                                </p>

                                <!-- QR Code -->
                                <div class="flex justify-center mb-6">
                                    <div class="bg-white p-4 rounded-2xl">
                                        <img
                                            :src="`data:image/png;base64,${pixData.qr_code_base64}`"
                                            alt="QR Code PIX"
                                            class="w-44 h-44"
                                        />
                                    </div>
                                </div>

                                <!-- Copy PIX code -->
                                <div
                                    class="bg-surface-darker border border-border-dark rounded-xl p-4 mb-4 text-left"
                                >
                                    <p
                                        class="text-slate-400 text-xs mb-2 font-medium"
                                    >
                                        Código PIX (copia e cola)
                                    </p>
                                    <div class="flex items-center gap-3">
                                        <p
                                            class="text-white text-xs font-mono flex-1 break-all leading-relaxed"
                                        >
                                            {{ pixData.qr_code }}
                                        </p>
                                        <button
                                            @click="copyPixCode"
                                            class="shrink-0 p-2 rounded-lg bg-primary/10 border border-primary/20 text-primary hover:bg-primary/20 transition-all"
                                            :title="
                                                pixCopied
                                                    ? 'Copiado!'
                                                    : 'Copiar código'
                                            "
                                        >
                                            <svg
                                                v-if="!pixCopied"
                                                class="w-4 h-4"
                                                fill="none"
                                                viewBox="0 0 24 24"
                                                stroke="currentColor"
                                            >
                                                <path
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                    stroke-width="2"
                                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"
                                                />
                                            </svg>
                                            <svg
                                                v-else
                                                class="w-4 h-4"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <!-- Expiry warning -->
                                <div
                                    class="flex items-center justify-center gap-2 text-amber-400 text-xs"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                    <span>O QR Code expira em 30 minutos</span>
                                </div>
                            </div>
                        </div>

                        <!-- Security Badges -->
                        <div
                            class="mt-10 pt-8 border-t border-border-dark flex flex-wrap justify-center gap-8 opacity-40"
                        >
                            <div class="flex items-center gap-2">
                                <svg
                                    class="w-5 h-5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span
                                    class="text-xs font-bold uppercase tracking-widest"
                                    >SSL Secure Connection</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <svg
                                    class="w-5 h-5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span
                                    class="text-xs font-bold uppercase tracking-widest"
                                    >PCI DSS Compliant</span
                                >
                            </div>
                            <div class="flex items-center gap-2">
                                <svg
                                    class="w-5 h-5"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                                <span
                                    class="text-xs font-bold uppercase tracking-widest"
                                    >Dados Criptografados</span
                                >
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Column: Order Summary -->
                <div class="col-span-12 lg:col-span-4">
                    <div class="sticky top-24 flex flex-col gap-4">
                        <!-- Order Summary Card -->
                        <div
                            class="bg-surface-dark rounded-xl border border-border-dark p-6 shadow-2xl overflow-hidden relative"
                        >
                            <div
                                class="absolute top-0 right-0 w-32 h-32 bg-primary/5 rounded-full -mr-16 -mt-16 blur-3xl"
                            ></div>

                            <h3
                                class="text-lg font-bold text-white mb-6 border-b border-border-dark/50 pb-4 flex items-center gap-2"
                            >
                                <svg
                                    class="w-5 h-5 text-primary"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 3H6.28l-.31-1.243A1 1 0 005 1H3zM16 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM6.5 18a1.5 1.5 0 100-3 1.5 1.5 0 000 3z"
                                    />
                                </svg>
                                Resumo do Pedido
                            </h3>

                            <!-- Event and reference -->
                            <div class="flex flex-col mb-4">
                                <span
                                    class="text-white font-bold text-sm mb-1"
                                    >{{ orderData.event_title }}</span
                                >
                                <span class="text-slate-500 text-xs"
                                    >Pedido: {{ orderData.reference }}</span
                                >
                            </div>

                            <!-- Items -->
                            <div
                                class="space-y-2 mb-4"
                                v-if="orderData.items?.length"
                            >
                                <div
                                    v-for="item in orderData.items"
                                    :key="item.id"
                                    class="flex justify-between items-start text-sm"
                                >
                                    <span class="text-slate-300 flex-1 pr-2">
                                        1x {{ item.ticket_type?.name }}
                                        <span
                                            v-if="item.category"
                                            class="text-slate-500"
                                        >
                                            - {{ item.category.name }}
                                        </span>
                                    </span>
                                    <span
                                        class="text-white font-medium shrink-0"
                                    >
                                        {{
                                            formatPrice(
                                                item.ticket_type?.price_cents ||
                                                    0,
                                            )
                                        }}
                                    </span>
                                </div>
                            </div>

                            <div class="border-t border-border-dark my-4"></div>

                            <!-- Total -->
                            <div
                                class="flex justify-between items-end mb-6"
                            >
                                <div>
                                    <span
                                        class="text-slate-400 font-bold uppercase text-xs tracking-widest"
                                        >Total</span
                                    >
                                    <p
                                        v-if="activeTab === 'credit' && selectedInstallment && selectedInstallment.installments > 1"
                                        class="text-xs text-slate-500 mt-0.5"
                                    >
                                        {{ selectedInstallment.recommended_message }}
                                    </p>
                                </div>
                                <span
                                    class="text-3xl font-black text-primary tracking-tighter"
                                    style="
                                        text-shadow: 0 0 10px
                                            rgba(0, 230, 118, 0.5);
                                    "
                                >
                                    {{
                                        activeTab === 'credit' && selectedInstallment?.total_amount
                                            ? formatPrice(selectedInstallment.total_amount * 100)
                                            : formatPrice(orderData.total_cents)
                                    }}
                                </span>
                            </div>

                            <!-- Action Button -->
                            <button
                                v-if="activeTab !== 'pix' || !pixData"
                                @click="handleSubmit"
                                :disabled="processing"
                                class="w-full bg-primary hover:bg-primary/90 text-background-dark font-black text-sm uppercase tracking-wider py-4 rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 shadow-lg shadow-primary/20"
                            >
                                <template v-if="processing">
                                    <svg
                                        class="animate-spin w-4 h-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                    >
                                        <circle
                                            class="opacity-25"
                                            cx="12"
                                            cy="12"
                                            r="10"
                                            stroke="currentColor"
                                            stroke-width="4"
                                        />
                                        <path
                                            class="opacity-75"
                                            fill="currentColor"
                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                        />
                                    </svg>
                                    <span>Processando...</span>
                                </template>
                                <template
                                    v-else-if="activeTab === 'pix' && !pixData"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"
                                        />
                                    </svg>
                                    <span>GERAR QR CODE PIX</span>
                                </template>
                                <template v-else>
                                    <span>FINALIZAR PAGAMENTO</span>
                                    <svg
                                        class="w-4 h-4"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </template>
                            </button>

                            <!-- PIX waiting state -->
                            <div
                                v-if="activeTab === 'pix' && pixData"
                                class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 text-center"
                            >
                                <p class="text-amber-400 text-sm font-semibold">
                                    Aguardando confirmação do PIX
                                </p>
                                <p class="text-slate-400 text-xs mt-1">
                                    Após o pagamento, seus ingressos serão
                                    gerados automaticamente.
                                </p>
                            </div>
                        </div>

                        <!-- Help Card -->
                        <div
                            class="bg-surface-dark/40 border border-border-dark rounded-xl p-5"
                        >
                            <div class="flex gap-4">
                                <div
                                    class="size-10 shrink-0 bg-surface-darker rounded-full border border-border-dark flex items-center justify-center text-primary"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        fill="currentColor"
                                        viewBox="0 0 20 20"
                                    >
                                        <path
                                            fill-rule="evenodd"
                                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-3a1 1 0 00-.867.5 1 1 0 11-1.731-1A3 3 0 0113 8a3.001 3.001 0 01-2 2.83V11a1 1 0 11-2 0v-1a1 1 0 011-1 1 1 0 100-2zm0 8a1 1 0 100-2 1 1 0 000 2z"
                                            clip-rule="evenodd"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-white text-sm font-bold">
                                        Precisa de ajuda?
                                    </h4>
                                    <p
                                        class="text-slate-500 text-xs mt-1 leading-relaxed"
                                    >
                                        Fale conosco pelo suporte.
                                    </p>
                                    <a
                                        href="#"
                                        class="text-primary text-xs font-bold mt-2 inline-flex items-center gap-1 hover:underline uppercase tracking-wider"
                                    >
                                        Acessar Suporte
                                        <svg
                                            class="w-3 h-3"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                            />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <!-- Processing Overlay -->
        <div
            v-if="processing"
            class="fixed inset-0 bg-background-dark/80 backdrop-blur-sm flex items-center justify-center z-50"
        >
            <div
                class="bg-surface-dark rounded-xl border border-border-dark p-8 text-center max-w-sm w-full mx-4"
            >
                <div
                    class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary mx-auto mb-4"
                ></div>
                <p class="text-white font-bold text-lg">
                    {{
                        activeTab === "pix"
                            ? "Gerando QR Code PIX..."
                            : "Processando pagamento..."
                    }}
                </p>
                <p class="text-slate-400 text-sm mt-2">
                    Aguarde, não feche esta página
                </p>
            </div>
        </div>

        <Footer />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, h } from "vue";
import { useRouter } from "vue-router";
import api from "../api/axios.js";
import Navbar from "../components/Navbar.vue";
import Footer from "../components/Footer.vue";

const router = useRouter();

// ─── State ───────────────────────────────────────────────────────────────────

const loading = ref(true);
const error = ref(null);
const processing = ref(false);
const orderData = ref({});
const buyerInfo = ref({ name: "", email: "", cpf: "" });
const activeTab = ref("credit");

// Card data
const cardData = ref({ number: "", holderName: "", expiry: "", cvv: "" });
const cardBrand = ref(null);
const cardPaymentMethodId = ref(null);
const installments = ref([]);
const selectedInstallment = ref(null);
const loadingInstallments = ref(false);
const fieldErrors = ref({});

// PIX
const pixData = ref(null);
const pixCopied = ref(false);
let pixPollingInterval = null;

// MP SDK instance
let mp = null;

// ─── Constants ───────────────────────────────────────────────────────────────

const pixSteps = [
    "Clique no botão abaixo para gerar o QR Code.",
    "Escaneie o código no app do seu banco ou copie a chave PIX.",
    "A confirmação é instantânea após o pagamento.",
];

// Inline SVG components for tabs
const CreditCardIcon = {
    render() {
        return h(
            "svg",
            { fill: "currentColor", viewBox: "0 0 20 20" },
            h("path", {
                d: "M4 4a2 2 0 00-2 2v1h16V6a2 2 0 00-2-2H4z",
            }),
            h("path", {
                "fill-rule": "evenodd",
                d: "M18 9H2v5a2 2 0 002 2h12a2 2 0 002-2V9zM4 13a1 1 0 011-1h1a1 1 0 110 2H5a1 1 0 01-1-1zm5-1a1 1 0 100 2h1a1 1 0 100-2H9z",
                "clip-rule": "evenodd",
            }),
        );
    },
};

const DebitCardIcon = {
    render() {
        return h(
            "svg",
            { fill: "none", viewBox: "0 0 24 24", stroke: "currentColor" },
            h("path", {
                "stroke-linecap": "round",
                "stroke-linejoin": "round",
                "stroke-width": "2",
                d: "M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z",
            }),
        );
    },
};

const PixIcon = {
    render() {
        return h(
            "svg",
            { fill: "currentColor", viewBox: "0 0 24 24" },
            h("path", { d: "M13 2L3 14h9l-1 8 10-12h-9l1-8z" }),
        );
    },
};

const paymentTabs = [
    {
        id: "credit",
        label: "Cartão de Crédito",
        short: "Crédito",
        icon: CreditCardIcon,
    },
    {
        id: "debit",
        label: "Cartão de Débito",
        short: "Débito",
        icon: DebitCardIcon,
    },
    { id: "pix", label: "PIX", short: "PIX", icon: PixIcon },
];

const cardBrandLabels = {
    visa: "Visa",
    master: "Mastercard",
    amex: "Amex",
    elo: "Elo",
    hipercard: "Hipercard",
};

// ─── Computed ─────────────────────────────────────────────────────────────────

const cardBrandLabel = computed(
    () => cardBrandLabels[cardBrand.value] || cardBrand.value,
);

// ─── Helpers ──────────────────────────────────────────────────────────────────

function formatPrice(cents) {
    return new Intl.NumberFormat("pt-BR", {
        style: "currency",
        currency: "BRL",
    }).format((cents || 0) / 100);
}

function formatCPF(value) {
    const n = value.replace(/\D/g, "").slice(0, 11);
    return n
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d)/, "$1.$2")
        .replace(/(\d{3})(\d{1,2})$/, "$1-$2");
}

function detectCardBrand(number) {
    const n = number.replace(/\s/g, "");
    if (/^4/.test(n)) return "visa";
    if (/^5[1-5]|^2(2[2-9][1-9]|[3-6]\d{2}|7([01]\d|20))/.test(n))
        return "master";
    if (/^3[47]/.test(n)) return "amex";
    if (/^(636368|438935|504175|451416|636297|5067|4576|4011)/.test(n))
        return "elo";
    if (/^(606282|3841)/.test(n)) return "hipercard";
    return null;
}

function formatCardNumber(value) {
    const n = value.replace(/\D/g, "").slice(0, 16);
    return n.replace(/(\d{4})(?=\d)/g, "$1 ");
}

// ─── Handlers ─────────────────────────────────────────────────────────────────

function handleCPFInput(event) {
    buyerInfo.value.cpf = formatCPF(event.target.value);
}

async function handleCardNumberInput(event) {
    cardData.value.number = formatCardNumber(event.target.value);
    fieldErrors.value.number = "";

    const digits = cardData.value.number.replace(/\s/g, "");
    cardBrand.value = detectCardBrand(digits);

    if (digits.length >= 6 && activeTab.value === "credit" && mp) {
        const bin = digits.slice(0, 6);
        await fetchInstallments(bin);
        await fetchPaymentMethodId(bin);
    }
}

function handleExpiryInput(event) {
    let v = event.target.value.replace(/\D/g, "").slice(0, 4);
    if (v.length >= 3) v = v.slice(0, 2) + "/" + v.slice(2);
    cardData.value.expiry = v;
    fieldErrors.value.expiry = "";
}

// ─── MP SDK helpers ───────────────────────────────────────────────────────────

async function fetchPaymentMethodId(bin) {
    try {
        const methods = await mp.getPaymentMethods({ bin });
        if (methods?.results?.length) {
            // Prefer matching payment_type_id to current tab
            const typeFilter =
                activeTab.value === "debit" ? "debit_card" : "credit_card";
            const match =
                methods.results.find((m) => m.payment_type_id === typeFilter) ||
                methods.results[0];
            cardPaymentMethodId.value = match.id;
        }
    } catch (e) {
        // non-critical
    }
}

async function fetchInstallments(bin) {
    if (!mp || !bin) {
        return;
    }

    loadingInstallments.value = true;

    try {
        const amount = String((orderData.value.total_cents || 0) / 100);

        const result = await mp.getInstallments({
            amount,
            locale: "pt-BR",
            bin,
        });

        if (result?.length) {
            // Correto: usar payer_costs, não installments
            installments.value = result[0].payer_costs || [];
            selectedInstallment.value = installments.value[0] || null;
        } else {
            installments.value = [];
        }
    } catch (e) {
        console.error("❌ Erro ao buscar parcelas:", e);
        installments.value = [];
    } finally {
        loadingInstallments.value = false;
    }
}

// ─── Submit handlers ──────────────────────────────────────────────────────────

function hasNonAsciiEmail(email) {
    return /[^\x00-\x7F]/.test(email);
}

function validateCardForm() {
    const errors = {};
    if (hasNonAsciiEmail(buyerInfo.value.email)) {
        errors.email =
            "E-mail inválido. Use apenas letras sem acento (ex: joao@email.com).";
    }
    const digits = cardData.value.number.replace(/\s/g, "");
    if (digits.length < 13) errors.number = "Número do cartão inválido.";
    if ((cardData.value.holderName || "").trim().length < 2)
        errors.holderName = "Informe o nome do titular.";
    if ((cardData.value.expiry || "").length < 5)
        errors.expiry = "Data de validade inválida.";
    const cvvMinLength = cardBrand.value === "amex" ? 4 : 3;
    if ((cardData.value.cvv || "").length < cvvMinLength)
        errors.cvv = "CVV inválido.";
    return errors;
}

async function submitCardPayment() {
    const errors = validateCardForm();
    if (Object.keys(errors).length) {
        fieldErrors.value = errors;
        return;
    }

    processing.value = true;
    fieldErrors.value = {};

    try {
        const [expMonth, expYear] = cardData.value.expiry.split("/");
        const cpfDigits = buyerInfo.value.cpf.replace(/\D/g, "");

        const tokenResult = await mp.createCardToken({
            cardNumber: cardData.value.number.replace(/\s/g, ""),
            cardholderName: cardData.value.holderName,
            cardExpirationMonth: expMonth,
            cardExpirationYear: expYear,
            securityCode: cardData.value.cvv,
            identificationType: "CPF",
            identificationNumber: cpfDigits,
        });

        if (!tokenResult?.id) throw new Error("Falha ao tokenizar o cartão.");

        // If payment_method_id not yet fetched, fetch now
        if (!cardPaymentMethodId.value) {
            await fetchPaymentMethodId(
                cardData.value.number.replace(/\s/g, "").slice(0, 6),
            );
        }

        const response = await api.post(
            `/orders/${orderData.value.reference}/payment`,
            {
                payment_method:
                    activeTab.value === "credit" ? "credit_card" : "debit_card",
                token: tokenResult.id,
                payment_method_id: cardPaymentMethodId.value,
                installments: selectedInstallment.value?.installments ?? 1,
                payer: {
                    email: buyerInfo.value.email,
                    identification: {
                        type: "CPF",
                        number: cpfDigits,
                    },
                },
            },
        );

        const { payment_status } = response.data;
        redirectAfterPayment(payment_status);
    } catch (err) {
        processing.value = false;
        handlePaymentError(err);
    }
}

async function generatePix() {
    if (hasNonAsciiEmail(buyerInfo.value.email)) {
        fieldErrors.value = {
            email: "E-mail inválido. Use apenas letras sem acento (ex: joao@email.com).",
        };
        return;
    }

    processing.value = true;

    try {
        const cpfDigits = buyerInfo.value.cpf.replace(/\D/g, "");

        const response = await api.post(
            `/orders/${orderData.value.reference}/payment`,
            {
                payment_method: "pix",
                payer: {
                    email: buyerInfo.value.email,
                    identification: { type: "CPF", number: cpfDigits },
                },
            },
        );

        const { payment_status, pix } = response.data;

        if (pix?.qr_code) {
            pixData.value = pix;
            startPixPolling();
        } else if (payment_status === "approved") {
            redirectAfterPayment("approved");
        }
    } catch (err) {
        handlePaymentError(err);
    } finally {
        processing.value = false;
    }
}

function handleSubmit() {
    if (activeTab.value === "pix") {
        generatePix();
    } else {
        submitCardPayment();
    }
}

function redirectAfterPayment(status) {
    if (status === "approved") {
        localStorage.removeItem("checkout_data");
        localStorage.removeItem("checkout_participants");
        localStorage.removeItem("payment_order");
        router.push({ name: "payment-success" });
    } else if (["pending", "in_process", "authorized"].includes(status)) {
        router.push({ name: "payment-pending" });
    } else {
        router.push({ name: "payment-error" });
    }
}

function handlePaymentError(err) {
    // Handle Mercado Pago SDK tokenization errors (erros de validação de cartão)
    if (err?.cause?.length) {
        const mpError = err.cause[0];
        const errorMap = {
            205: { field: "number", msg: "Número do cartão incompleto." },
            208: { field: "expiry", msg: "Mês de validade inválido." },
            209: { field: "expiry", msg: "Ano de validade inválido." },
            212: { field: "expiry", msg: "Data de validade inválida." },
            213: { field: "holderName", msg: "Nome do titular inválido." },
            214: { field: "cvv", msg: "CVV inválido." },
            316: { field: "holderName", msg: "Nome do titular inválido." },
            E206: { field: "expiry", msg: "Data de validade inválida." },
            E301: { field: "number", msg: "Número do cartão inválido." },
            E302: { field: "cvv", msg: "CVV inválido." },
        };
        const mapped = errorMap[mpError?.code];
        if (mapped) {
            fieldErrors.value[mapped.field] = mapped.msg;
            return;
        }
        // Se não é erro de validação de campo, redireciona para página de erro
        router.push({
            name: "payment-error",
            query: {
                reason: "invalid_card",
                message: mpError?.description || "Dados do cartão inválidos",
            },
        });
        return;
    }

    // Ingresso esgotado no momento do pagamento
    if (err?.response?.data?.error_code === "ticket_unavailable") {
        router.push({
            name: "payment-error",
            query: {
                reason: "sold_out",
                message:
                    err.response.data.message ||
                    "Ingressos esgotados durante o processo de pagamento",
            },
        });
        return;
    }

    // Pagamento recusado pela operadora
    if (
        err?.response?.status === 400 &&
        err?.response?.data?.message?.includes("recusad")
    ) {
        router.push({
            name: "payment-error",
            query: {
                reason: "payment_rejected",
                message:
                    err.response.data.message ||
                    "Pagamento recusado pela operadora do cartão",
            },
        });
        return;
    }

    // Erro genérico de API
    const msg =
        err?.response?.data?.message ||
        "Erro ao processar pagamento. Tente novamente.";
    router.push({
        name: "payment-error",
        query: {
            reason: "generic_error",
            message: msg,
        },
    });
}

function startPixPolling() {
    stopPixPolling();
    pixPollingInterval = setInterval(async () => {
        try {
            const { data } = await api.get(
                `/orders/${orderData.value.reference}/status`,
            );
            if (data.status === "paid") {
                stopPixPolling();
                redirectAfterPayment("approved");
            } else if (data.status === "cancelled") {
                stopPixPolling();
                redirectAfterPayment("rejected");
            }
        } catch {
            // ignora erros de polling
        }
    }, 5000);
}

function stopPixPolling() {
    if (pixPollingInterval) {
        clearInterval(pixPollingInterval);
        pixPollingInterval = null;
    }
}

async function copyPixCode() {
    if (!pixData.value?.qr_code) return;
    try {
        await navigator.clipboard.writeText(pixData.value.qr_code);
        pixCopied.value = true;
        setTimeout(() => (pixCopied.value = false), 3000);
    } catch {
        // fallback: select text manually
    }
}

// ─── Lifecycle ────────────────────────────────────────────────────────────────

onUnmounted(() => {
    stopPixPolling();
});

onMounted(async () => {
    try {
        const raw = localStorage.getItem("payment_order");
        if (!raw) {
            error.value = "Nenhum pedido encontrado para pagamento.";
            loading.value = false;
            return;
        }

        orderData.value = JSON.parse(raw);

        // Pre-fill buyer info from first participant
        const participantsRaw = localStorage.getItem("checkout_participants");
        if (participantsRaw) {
            const participants = JSON.parse(participantsRaw);
            if (participants?.length) {
                const first = participants[0];
                buyerInfo.value.name = first.name || "";
                buyerInfo.value.email = first.email || "";
                buyerInfo.value.cpf = formatCPF(first.cpf || "");
            }
        }

        if (!orderData.value.public_key) {
            error.value = "Configuração de pagamento inválida.";
            loading.value = false;
            return;
        }

        loading.value = false;

        // Init MP SDK
        mp = new window.MercadoPago(orderData.value.public_key, {
            locale: "pt-BR",
        });
    } catch (err) {
        console.error("Error initializing payment:", err);
        error.value = "Erro ao carregar o pagamento.";
        loading.value = false;
    }
});
</script>

<style scoped>
.input-field {
    width: 100%;
    background-color: var(--surface-darker, #252b3a);
    border: 1px solid var(--border-dark, #3a404b);
    border-radius: 0.5rem;
    padding: 0.75rem 1rem;
    color: white;
    outline: none;
    transition:
        border-color 0.15s ease,
        box-shadow 0.15s ease;
}
.input-field::placeholder {
    color: #475569; /* slate-600 */
}
.input-field:focus {
    border-color: var(--primary, #00e677);
    box-shadow: 0 0 0 2px rgba(0, 230, 119, 0.2);
}
</style>

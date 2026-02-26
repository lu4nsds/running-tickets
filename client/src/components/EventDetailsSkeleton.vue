<template>
    <div class="min-h-screen bg-background-dark text-slate-100 font-display">
        <!-- Hero Skeleton -->
        <div
            class="relative w-full h-[400px] md:h-[500px] bg-surface-dark animate-pulse"
        >
            <div
                class="absolute inset-0 bg-gradient-to-t from-background-dark via-background-dark/80 to-transparent"
            ></div>
            <div
                class="relative h-full max-w-[1440px] mx-auto px-4 md:px-10 pb-10 flex flex-col justify-end"
            >
                <div
                    class="w-24 h-6 bg-surface-darker/50 rounded-full mb-4"
                ></div>
                <div
                    class="w-3/4 h-12 md:h-16 bg-surface-darker/50 rounded-lg mb-2"
                ></div>
                <div class="flex flex-wrap gap-6 mt-2">
                    <div class="w-40 h-5 bg-surface-darker/50 rounded"></div>
                    <div class="w-32 h-5 bg-surface-darker/50 rounded"></div>
                    <div class="w-44 h-5 bg-surface-darker/50 rounded"></div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div
            class="layout-container max-w-[1440px] mx-auto px-4 md:px-10 py-8 md:py-12"
        >
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Left Column -->
                <div class="lg:col-span-8 flex flex-col gap-10">
                    <!-- Sobre o Evento -->
                    <section class="animate-pulse">
                        <div
                            class="w-48 h-7 bg-surface-dark rounded-lg mb-4"
                        ></div>
                        <div class="space-y-3">
                            <div
                                class="w-full h-4 bg-surface-dark rounded"
                            ></div>
                            <div
                                class="w-full h-4 bg-surface-dark rounded"
                            ></div>
                            <div
                                class="w-3/4 h-4 bg-surface-dark rounded"
                            ></div>
                        </div>
                    </section>

                    <!-- Tipos de Ingressos -->
                    <section class="animate-pulse">
                        <div
                            class="w-56 h-7 bg-surface-dark rounded-lg mb-6"
                        ></div>
                        <div class="space-y-4">
                            <!-- Ticket Skeleton 1 -->
                            <div
                                class="bg-surface-dark rounded-xl border border-border-dark p-6"
                            >
                                <div
                                    class="flex justify-between items-start mb-4"
                                >
                                    <div class="flex-1">
                                        <div
                                            class="w-48 h-6 bg-surface-darker rounded-lg mb-2"
                                        ></div>
                                        <div
                                            class="w-64 h-4 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                    <div
                                        class="w-24 h-8 bg-surface-darker rounded-lg"
                                    ></div>
                                </div>
                                <div class="space-y-2 mb-4">
                                    <div
                                        class="w-full h-1.5 bg-surface-darker rounded-full"
                                    ></div>
                                    <div class="flex justify-between">
                                        <div
                                            class="w-32 h-3 bg-surface-darker rounded"
                                        ></div>
                                        <div
                                            class="w-20 h-3 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket Skeleton 2 -->
                            <div
                                class="bg-surface-dark rounded-xl border border-border-dark p-6"
                            >
                                <div
                                    class="flex justify-between items-start mb-4"
                                >
                                    <div class="flex-1">
                                        <div
                                            class="w-40 h-6 bg-surface-darker rounded-lg mb-2"
                                        ></div>
                                        <div
                                            class="w-56 h-4 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                    <div
                                        class="w-24 h-8 bg-surface-darker rounded-lg"
                                    ></div>
                                </div>
                                <div class="space-y-2 mb-4">
                                    <div
                                        class="w-full h-1.5 bg-surface-darker rounded-full"
                                    ></div>
                                    <div class="flex justify-between">
                                        <div
                                            class="w-32 h-3 bg-surface-darker rounded"
                                        ></div>
                                        <div
                                            class="w-20 h-3 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Ticket Skeleton 3 -->
                            <div
                                class="bg-surface-dark rounded-xl border border-border-dark p-6"
                            >
                                <div
                                    class="flex justify-between items-start mb-4"
                                >
                                    <div class="flex-1">
                                        <div
                                            class="w-44 h-6 bg-surface-darker rounded-lg mb-2"
                                        ></div>
                                        <div
                                            class="w-60 h-4 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                    <div
                                        class="w-24 h-8 bg-surface-darker rounded-lg"
                                    ></div>
                                </div>
                                <div class="space-y-2 mb-4">
                                    <div
                                        class="w-full h-1.5 bg-surface-darker rounded-full"
                                    ></div>
                                    <div class="flex justify-between">
                                        <div
                                            class="w-32 h-3 bg-surface-darker rounded"
                                        ></div>
                                        <div
                                            class="w-20 h-3 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Right Sidebar -->
                <div class="lg:col-span-4">
                    <div class="sticky top-24 space-y-6 animate-pulse">
                        <!-- Summary Card -->
                        <div
                            class="bg-surface-dark rounded-xl border border-border-dark p-6"
                        >
                            <div
                                class="w-32 h-6 bg-surface-darker rounded-lg mb-4"
                            ></div>
                            <div class="space-y-3 mb-6">
                                <div class="flex justify-between">
                                    <div
                                        class="w-32 h-4 bg-surface-darker rounded"
                                    ></div>
                                    <div
                                        class="w-20 h-4 bg-surface-darker rounded"
                                    ></div>
                                </div>
                            </div>
                            <div class="pt-4 border-t border-border-dark mb-6">
                                <div class="flex justify-between items-center">
                                    <div
                                        class="w-20 h-5 bg-surface-darker rounded"
                                    ></div>
                                    <div
                                        class="w-28 h-8 bg-surface-darker rounded-lg"
                                    ></div>
                                </div>
                            </div>
                            <div
                                class="w-full h-12 bg-surface-darker rounded-lg"
                            ></div>
                        </div>

                        <!-- Info Card -->
                        <div
                            class="bg-surface-dark rounded-xl border border-border-dark p-6"
                        >
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-5 h-5 bg-surface-darker rounded"
                                    ></div>
                                    <div class="flex-1 space-y-2">
                                        <div
                                            class="w-24 h-4 bg-surface-darker rounded"
                                        ></div>
                                        <div
                                            class="w-full h-3 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-5 h-5 bg-surface-darker rounded"
                                    ></div>
                                    <div class="flex-1 space-y-2">
                                        <div
                                            class="w-20 h-4 bg-surface-darker rounded"
                                        ></div>
                                        <div
                                            class="w-full h-3 bg-surface-darker rounded"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// Skeleton component - no logic needed
</script>

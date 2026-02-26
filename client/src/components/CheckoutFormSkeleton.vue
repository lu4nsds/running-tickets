<template>
    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8 animate-pulse">
            <div class="w-80 h-9 bg-surface-dark rounded-lg mb-2"></div>
            <div class="w-96 h-5 bg-surface-dark rounded"></div>
        </div>

        <!-- Steps Indicator -->
        <div class="mb-8 flex items-center justify-center gap-2 animate-pulse">
            <div class="flex items-center">
                <div class="w-10 h-10 rounded-full bg-primary"></div>
                <div class="ml-2 w-24 h-4 bg-surface-dark rounded"></div>
            </div>
            <div class="w-16 h-0.5 bg-border-dark"></div>
            <div class="flex items-center">
                <div
                    class="w-10 h-10 rounded-full bg-surface-dark border-2 border-border-dark"
                ></div>
                <div class="ml-2 w-20 h-4 bg-surface-dark rounded"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Formulários dos Participantes -->
            <div class="lg:col-span-2 space-y-3">
                <!-- Participant Card 1 -->
                <div
                    class="bg-surface-dark rounded-xl border border-border-dark overflow-hidden animate-pulse"
                >
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-surface-darker"
                            ></div>
                            <div class="space-y-2">
                                <div
                                    class="w-40 h-5 bg-surface-darker rounded"
                                ></div>
                                <div
                                    class="w-32 h-3 bg-surface-darker rounded"
                                ></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-20 h-5 bg-surface-darker rounded-full"
                            ></div>
                            <div
                                class="w-5 h-5 bg-surface-darker rounded"
                            ></div>
                        </div>
                    </div>
                    <!-- Expanded Form -->
                    <div class="px-6 pb-6">
                        <div class="border-t border-border-dark mb-6"></div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <div
                                    class="w-32 h-4 bg-surface-darker rounded mb-2"
                                ></div>
                                <div
                                    class="w-full h-11 bg-surface-darker rounded-lg"
                                ></div>
                            </div>
                            <div class="md:col-span-2">
                                <div
                                    class="w-28 h-4 bg-surface-darker rounded mb-2"
                                ></div>
                                <div
                                    class="w-full h-11 bg-surface-darker rounded-lg"
                                ></div>
                            </div>
                            <div>
                                <div
                                    class="w-20 h-4 bg-surface-darker rounded mb-2"
                                ></div>
                                <div
                                    class="w-full h-11 bg-surface-darker rounded-lg"
                                ></div>
                            </div>
                            <div>
                                <div
                                    class="w-16 h-4 bg-surface-darker rounded mb-2"
                                ></div>
                                <div
                                    class="w-full h-11 bg-surface-darker rounded-lg"
                                ></div>
                            </div>
                            <div>
                                <div
                                    class="w-36 h-4 bg-surface-darker rounded mb-2"
                                ></div>
                                <div
                                    class="w-full h-11 bg-surface-darker rounded-lg"
                                ></div>
                            </div>
                            <div>
                                <div
                                    class="w-32 h-4 bg-surface-darker rounded mb-2"
                                ></div>
                                <div
                                    class="w-full h-11 bg-surface-darker rounded-lg"
                                ></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Participant Card 2 (Collapsed) -->
                <div
                    class="bg-surface-dark rounded-xl border border-border-dark overflow-hidden animate-pulse"
                >
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-surface-darker"
                            ></div>
                            <div class="space-y-2">
                                <div
                                    class="w-36 h-5 bg-surface-darker rounded"
                                ></div>
                                <div
                                    class="w-28 h-3 bg-surface-darker rounded"
                                ></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-20 h-5 bg-surface-darker rounded-full"
                            ></div>
                            <div
                                class="w-5 h-5 bg-surface-darker rounded"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Participant Card 3 (Collapsed) -->
                <div
                    class="bg-surface-dark rounded-xl border border-border-dark overflow-hidden animate-pulse"
                >
                    <div class="px-6 py-4 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-full bg-surface-darker"
                            ></div>
                            <div class="space-y-2">
                                <div
                                    class="w-36 h-5 bg-surface-darker rounded"
                                ></div>
                                <div
                                    class="w-28 h-3 bg-surface-darker rounded"
                                ></div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-20 h-5 bg-surface-darker rounded-full"
                            ></div>
                            <div
                                class="w-5 h-5 bg-surface-darker rounded"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumo do Pedido (Sidebar) -->
            <div class="lg:col-span-1">
                <div
                    class="bg-surface-dark rounded-xl border border-border-dark p-6 sticky top-24 animate-pulse"
                >
                    <div
                        class="w-40 h-6 bg-surface-darker rounded-lg mb-4 pb-4 border-b border-border-dark"
                    ></div>

                    <div class="space-y-3 mb-4">
                        <div class="flex justify-between">
                            <div
                                class="w-32 h-4 bg-surface-darker rounded"
                            ></div>
                            <div
                                class="w-20 h-4 bg-surface-darker rounded"
                            ></div>
                        </div>
                        <div class="flex justify-between">
                            <div
                                class="w-28 h-4 bg-surface-darker rounded"
                            ></div>
                            <div
                                class="w-20 h-4 bg-surface-darker rounded"
                            ></div>
                        </div>
                        <div class="flex justify-between">
                            <div
                                class="w-36 h-4 bg-surface-darker rounded"
                            ></div>
                            <div
                                class="w-20 h-4 bg-surface-darker rounded"
                            ></div>
                        </div>
                    </div>

                    <div
                        class="flex justify-between items-end pt-4 border-t border-border-dark mb-6"
                    >
                        <div class="w-12 h-5 bg-surface-darker rounded"></div>
                        <div
                            class="w-28 h-8 bg-surface-darker rounded-lg"
                        ></div>
                    </div>

                    <div class="pt-6 border-t border-border-dark">
                        <div
                            class="w-full h-12 bg-surface-darker rounded-lg mb-3"
                        ></div>
                        <div
                            class="w-full h-10 bg-surface-darker/50 rounded-lg"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
// Skeleton component - no logic needed
</script>

<template>
    <div
        v-if="type === 'event'"
        class="bg-card-bg border border-surface-elevated rounded-xl overflow-hidden"
    >
        <div class="animate-pulse">
            <!-- Image placeholder -->
            <div class="h-40 bg-surface"></div>

            <!-- Content placeholder -->
            <div class="p-4 space-y-3">
                <!-- Badge -->
                <div class="h-5 bg-surface rounded w-20"></div>

                <!-- Title -->
                <div class="h-6 bg-surface rounded w-3/4"></div>

                <!-- Date -->
                <div class="h-4 bg-surface rounded w-1/2"></div>

                <!-- Location -->
                <div class="h-4 bg-surface rounded w-2/3"></div>

                <!-- Divider -->
                <div class="h-px bg-surface my-3"></div>

                <!-- Footer -->
                <div class="flex justify-between items-center">
                    <div class="h-4 bg-surface rounded w-24"></div>
                    <div class="flex gap-2">
                        <div class="h-8 w-24 bg-surface rounded"></div>
                        <div class="h-8 w-24 bg-surface rounded"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div
        v-else-if="type === 'metric-card'"
        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
    >
        <div class="animate-pulse space-y-4">
            <!-- Icon + Label -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-surface rounded-lg"></div>
                <div class="h-4 bg-surface rounded w-1/2"></div>
            </div>

            <!-- Value -->
            <div class="h-8 bg-surface rounded w-3/4"></div>

            <!-- Variation -->
            <div class="h-3 bg-surface rounded w-1/3"></div>
        </div>
    </div>

    <div
        v-else-if="type === 'chart'"
        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
    >
        <div class="animate-pulse space-y-4">
            <!-- Title -->
            <div class="h-6 bg-surface rounded w-1/3"></div>

            <!-- Chart area -->
            <div class="h-64 bg-surface rounded"></div>
        </div>
    </div>

    <div
        v-else-if="type === 'table-row'"
        class="animate-pulse flex items-center gap-4 py-4"
    >
        <div class="h-4 bg-surface rounded flex-1"></div>
        <div class="h-4 bg-surface rounded w-24"></div>
        <div class="h-4 bg-surface rounded w-32"></div>
        <div class="h-4 bg-surface rounded w-20"></div>
        <div class="h-4 bg-surface rounded w-28"></div>
    </div>

    <div
        v-else-if="type === 'info-section'"
        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
    >
        <div class="animate-pulse space-y-4">
            <!-- Title -->
            <div class="h-6 bg-surface rounded w-1/3 mb-6"></div>
            <!-- Content lines -->
            <div class="space-y-3">
                <div class="h-4 bg-surface rounded w-full"></div>
                <div class="h-4 bg-surface rounded w-5/6"></div>
                <div class="h-4 bg-surface rounded w-4/6"></div>
            </div>
        </div>
    </div>

    <div
        v-else-if="type === 'table-full'"
        class="bg-card-bg border border-surface-elevated rounded-xl p-6"
    >
        <div class="animate-pulse">
            <!-- Table header -->
            <div class="flex gap-4 pb-4 mb-4 border-b border-surface">
                <div class="h-4 bg-surface rounded w-1/4"></div>
                <div class="h-4 bg-surface rounded w-1/6"></div>
                <div class="h-4 bg-surface rounded w-1/4"></div>
                <div class="h-4 bg-surface rounded w-1/6"></div>
                <div class="h-4 bg-surface rounded w-1/6"></div>
            </div>
            <!-- Table rows -->
            <div
                v-for="i in 5"
                :key="i"
                class="flex gap-4 py-4 border-b border-surface-elevated"
            >
                <div class="h-4 bg-surface rounded w-1/4"></div>
                <div class="h-4 bg-surface rounded w-1/6"></div>
                <div class="h-4 bg-surface rounded w-1/4"></div>
                <div class="h-4 bg-surface rounded w-1/6"></div>
                <div class="h-4 bg-surface rounded w-1/6"></div>
            </div>
        </div>
    </div>

    <div v-else class="animate-pulse">
        <div class="h-32 bg-surface rounded"></div>
    </div>
</template>

<script setup>
const props = defineProps({
    type: {
        type: String,
        default: "default",
        validator: (value) =>
            [
                "event",
                "metric-card",
                "chart",
                "table-row",
                "info-section",
                "table-full",
                "default",
            ].includes(value),
    },
});
</script>

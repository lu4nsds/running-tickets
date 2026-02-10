<template>
    <div class="bg-card-bg border border-surface-elevated rounded-xl p-6">
        <div class="flex items-start justify-between mb-4">
            <div class="flex-1">
                <p class="text-text-muted text-sm mb-1">{{ title }}</p>
                <p class="text-3xl font-bold text-white">{{ value }}</p>

                <!-- Trend Indicator -->
                <div v-if="trend !== null" class="flex items-center gap-1 mt-2">
                    <span
                        :class="[
                            'material-symbols-outlined text-sm',
                            trendDirection === 'up'
                                ? 'text-primary'
                                : 'text-red-400',
                        ]"
                    >
                        {{
                            trendDirection === "up"
                                ? "trending_up"
                                : "trending_down"
                        }}
                    </span>
                    <span
                        :class="[
                            'text-sm font-medium',
                            trendDirection === 'up'
                                ? 'text-primary'
                                : 'text-red-400',
                        ]"
                    >
                        {{ trend }}
                    </span>
                    <span
                        v-if="trendLabel"
                        class="text-text-muted text-sm ml-1"
                    >
                        {{ trendLabel }}
                    </span>
                </div>
            </div>

            <!-- Icon -->
            <div
                :class="[
                    'w-12 h-12 rounded-lg flex items-center justify-center',
                    iconBgColor,
                ]"
            >
                <span
                    :class="['material-symbols-outlined text-2xl', iconColor]"
                >
                    {{ icon }}
                </span>
            </div>
        </div>

        <!-- Additional Info Slot -->
        <slot></slot>
    </div>
</template>

<script setup>
const props = defineProps({
    title: {
        type: String,
        required: true,
    },
    value: {
        type: [String, Number],
        required: true,
    },
    icon: {
        type: String,
        required: true,
    },
    iconColor: {
        type: String,
        default: "text-primary",
    },
    iconBgColor: {
        type: String,
        default: "bg-primary/10",
    },
    trend: {
        type: [String, Number],
        default: null,
    },
    trendDirection: {
        type: String,
        default: "up",
        validator: (value) => ["up", "down"].includes(value),
    },
    trendLabel: {
        type: String,
        default: "",
    },
});
</script>

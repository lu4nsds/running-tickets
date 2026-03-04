<template>
    <div
        class="flex flex-wrap items-center gap-4 p-4 bg-surface-elevated rounded-xl border border-border-dark"
    >
        <!-- Preset Buttons -->
        <div class="flex items-center gap-2">
            <button
                v-for="preset in presets"
                :key="preset.value"
                @click="selectPreset(preset.value)"
                :class="[
                    'px-4 py-2 rounded-full text-sm font-semibold transition-all duration-200',
                    selectedPreset === preset.value
                        ? 'bg-primary text-black shadow-[0_0_12px_rgba(0,230,118,0.5)]'
                        : 'bg-transparent border border-border-dark text-slate-300 hover:border-primary/50 hover:text-white',
                ]"
            >
                {{ preset.label }}
            </button>
        </div>

        <!-- Divider -->
        <div class="w-px h-8 bg-border-dark hidden md:block"></div>

        <!-- Custom Date Range -->
        <div class="relative">
            <button
                @click="toggleDatePicker"
                :class="[
                    'flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200',
                    selectedPreset === 'custom' || showDatePicker
                        ? 'bg-primary/10 border border-primary text-primary'
                        : 'bg-transparent border border-border-dark text-slate-300 hover:border-primary/50 hover:text-white',
                ]"
            >
                <span class="material-symbols-outlined text-lg"
                    >calendar_today</span
                >
                <span v-if="selectedPreset === 'custom' && dateRange.start">
                    {{ formatDisplayDate(dateRange.start) }} -
                    {{ formatDisplayDate(dateRange.end) }}
                </span>
                <span v-else>Período Personalizado</span>
                <span class="material-symbols-outlined text-lg">{{
                    showDatePicker ? "expand_less" : "expand_more"
                }}</span>
            </button>

            <!-- Date Picker Popover -->
            <Transition name="fade">
                <div
                    v-if="showDatePicker"
                    class="absolute top-full left-0 mt-2 z-50 bg-card-dark border border-border-dark rounded-xl shadow-2xl p-4"
                >
                    <VDatePicker
                        v-model.range="dateRange"
                        :columns="2"
                        :rows="1"
                        :max-date="today"
                        mode="date"
                        color="green"
                        is-dark
                        @dayclick="onDayClick"
                    />
                    <div
                        class="flex items-center justify-end gap-2 mt-4 pt-4 border-t border-border-dark"
                    >
                        <button
                            @click="cancelDatePicker"
                            class="px-4 py-2 text-sm text-slate-400 hover:text-white transition-colors"
                        >
                            Cancelar
                        </button>
                        <button
                            @click="applyDateRange"
                            :disabled="!dateRange.start || !dateRange.end"
                            class="px-4 py-2 bg-primary text-black font-semibold text-sm rounded-lg hover:bg-primary/80 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            Aplicar
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from "vue";
import { setupCalendar, DatePicker as VDatePicker } from "v-calendar";
import "v-calendar/style.css";
setupCalendar({});

const emit = defineEmits(["filter-change"]);

const props = defineProps({
    initialPreset: {
        type: String,
        default: "current_month",
    },
});

// Max date is today
const today = computed(() => new Date());

const presets = [
    { value: "current_month", label: "Mês Atual" },
    { value: "current_year", label: "Ano Atual" },
    { value: "7d", label: "Últimos 7 dias" },
    { value: "30d", label: "Últimos 30 dias" },
];

const selectedPreset = ref(props.initialPreset);
const showDatePicker = ref(false);
const dateRange = ref({
    start: null,
    end: null,
});
const clickCount = ref(0);

const selectPreset = (preset) => {
    selectedPreset.value = preset;
    showDatePicker.value = false;
    dateRange.value = { start: null, end: null };
    emit("filter-change", {
        preset,
        startDate: null,
        endDate: null,
    });
};

const toggleDatePicker = () => {
    showDatePicker.value = !showDatePicker.value;
    clickCount.value = 0;
};

const onDayClick = () => {
    clickCount.value++;
    // Auto-apply after selecting both dates
    if (clickCount.value >= 2 && dateRange.value.start && dateRange.value.end) {
        setTimeout(() => {
            applyDateRange();
        }, 300);
    }
};

const cancelDatePicker = () => {
    showDatePicker.value = false;
    clickCount.value = 0;
    if (selectedPreset.value !== "custom") {
        dateRange.value = { start: null, end: null };
    }
};

const applyDateRange = () => {
    if (dateRange.value.start && dateRange.value.end) {
        selectedPreset.value = "custom";
        showDatePicker.value = false;
        clickCount.value = 0;
        emit("filter-change", {
            preset: "custom",
            startDate: formatApiDate(dateRange.value.start),
            endDate: formatApiDate(dateRange.value.end),
        });
    }
};

const formatDisplayDate = (date) => {
    if (!date) return "";
    const d = new Date(date);
    return d.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "short",
    });
};

const formatApiDate = (date) => {
    if (!date) return null;
    const d = new Date(date);
    return d.toISOString().split("T")[0];
};

// Close date picker when clicking outside
const handleClickOutside = (event) => {
    const picker = document.querySelector(".vc-container");
    const button = event.target.closest("button");
    if (
        showDatePicker.value &&
        picker &&
        !picker.contains(event.target) &&
        !button?.textContent?.includes("Período")
    ) {
        // Don't close if clicking inside the popover
        const popover = event.target.closest(".absolute");
        if (!popover) {
            showDatePicker.value = false;
        }
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

/* Override VCalendar styles for dark mode */
:deep(.vc-container) {
    --vc-bg: #1a1d23;
    --vc-border: #2a2d35;
    --vc-header-color: #e2e8f0;
    --vc-weekday-color: #94a3b8;
    --vc-day-content-color: #e2e8f0;
    --vc-day-content-disabled-color: #475569;
    --vc-accent-50: rgba(0, 230, 118, 0.1);
    --vc-accent-100: rgba(0, 230, 118, 0.2);
    --vc-accent-200: rgba(0, 230, 118, 0.3);
    --vc-accent-300: rgba(0, 230, 118, 0.4);
    --vc-accent-400: rgba(0, 230, 118, 0.6);
    --vc-accent-500: #00e676;
    --vc-accent-600: #00c853;
    --vc-accent-700: #00a844;
    --vc-accent-800: #008836;
    --vc-accent-900: #006828;
    background-color: transparent !important;
    border: none !important;
    font-family: "Manrope", sans-serif;
}

:deep(.vc-header) {
    padding: 8px 0;
}

:deep(.vc-title) {
    font-weight: 600;
}

:deep(.vc-arrow) {
    color: #94a3b8;
}

:deep(.vc-arrow:hover) {
    background-color: rgba(0, 230, 118, 0.1);
}

:deep(.vc-day-content:hover) {
    background-color: rgba(0, 230, 118, 0.2);
}

:deep(.vc-highlight) {
    background-color: #00e676 !important;
}

:deep(.vc-highlight-content-solid) {
    color: #000 !important;
    font-weight: 600;
}
</style>

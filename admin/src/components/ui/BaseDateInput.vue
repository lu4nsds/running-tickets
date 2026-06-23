<template>
    <div ref="rootRef" class="relative">
        <label
            v-if="label"
            :for="id"
            class="block text-xs font-medium text-text-muted uppercase tracking-wider mb-2"
        >
            {{ label }}
        </label>

        <!-- Input / trigger -->
        <div ref="triggerRef" class="relative">
            <span
                class="material-symbols-outlined absolute inset-y-0 left-0 pl-3 flex items-center text-text-muted text-[20px] pointer-events-none"
            >
                {{ icon }}
            </span>
            <input
                :id="id"
                type="text"
                readonly
                :value="displayValue"
                :placeholder="placeholder"
                :disabled="disabled"
                class="w-full bg-surface border border-surface-elevated rounded-lg pl-11 pr-10 py-3 text-white placeholder-text-muted focus:outline-none focus:border-primary transition-colors cursor-pointer disabled:opacity-50 disabled:cursor-not-allowed"
                :class="{ 'border-red-500': error, 'border-primary': isOpen }"
                @click="toggle"
                @keydown.enter.prevent="toggle"
                @keydown.esc="close"
            />
            <button
                v-if="displayValue && !disabled"
                type="button"
                class="material-symbols-outlined absolute inset-y-0 right-0 pr-3 flex items-center text-text-muted hover:text-white text-[18px]"
                @click.stop="clear"
            >
                close
            </button>
        </div>

        <!-- Popover teleportado para o body: evita que qualquer ancestral com overflow
             (ex.: modal/card) corte o calendário. v-show (não v-if): o <DatePicker> nunca
             é desmontado durante a interação (desmontar ao selecionar quebra o v-calendar). -->
        <Teleport to="body">
            <div
                v-show="isOpen"
                ref="popoverRef"
                :style="popoverStyle"
                class="fixed rounded-xl border border-border-dark bg-card-dark p-2 shadow-2xl"
            >
                <DatePicker
                    :model-value="parsedDate"
                    :mode="withTime ? 'dateTime' : 'date'"
                    is24hr
                    locale="pt-BR"
                    color="green"
                    is-dark
                    :min-date="parsedMinDate"
                    :max-date="parsedMaxDate"
                    :initial-page="initialPage"
                    @update:model-value="onUpdate"
                />
            </div>
        </Teleport>

        <p v-if="error" class="text-red-500 text-sm mt-1">{{ error }}</p>
    </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from "vue";
import { DatePicker } from "v-calendar";

const props = defineProps({
    modelValue: { type: String, default: "" },
    withTime: { type: Boolean, default: false },
    label: { type: String, default: "" },
    id: { type: String, required: true },
    placeholder: { type: String, default: "Selecione uma data" },
    required: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    error: { type: String, default: "" },
    minDate: { type: [String, Date], default: null },
    maxDate: { type: [String, Date], default: null },
    icon: { type: String, default: "calendar_today" },
});

const emit = defineEmits(["update:modelValue"]);

const rootRef = ref(null);
const triggerRef = ref(null);
const popoverRef = ref(null);
const isOpen = ref(false);
const popoverPos = ref({ top: 0, left: 0, width: 0 });

const toDate = (value) => {
    if (!value) return null;
    if (value instanceof Date) {
        return Number.isNaN(value.getTime()) ? null : value;
    }
    // string só-data → construir como data local (evita shift UTC)
    if (/^\d{4}-\d{2}-\d{2}$/.test(value)) {
        const [y, m, d] = value.split("-").map(Number);
        return new Date(y, m - 1, d);
    }
    const date = new Date(value);
    return Number.isNaN(date.getTime()) ? null : date;
};

const parsedDate = computed(() => toDate(props.modelValue));
// v-calendar quebra (dayIndex undefined) se min/max forem null — omitir o atributo
const parsedMinDate = computed(() => toDate(props.minDate) ?? undefined);
const parsedMaxDate = computed(() => toDate(props.maxDate) ?? undefined);

// Mês inicial do calendário quando não há valor: usa a data atual (limitada ao max),
// evitando abrir no min-date.
const initialPage = computed(() => {
    const base = parsedDate.value ?? parsedMaxDate.value ?? new Date();
    return { month: base.getMonth() + 1, year: base.getFullYear() };
});

const popoverStyle = computed(() => ({
    top: `${popoverPos.value.top}px`,
    left: `${popoverPos.value.left}px`,
    zIndex: 9999,
}));

const displayValue = computed(() => {
    const date = parsedDate.value;
    if (!date) return "";
    return date.toLocaleDateString("pt-BR", {
        day: "2-digit",
        month: "2-digit",
        year: "numeric",
        ...(props.withTime ? { hour: "2-digit", minute: "2-digit" } : {}),
    });
});

const pad = (n) => String(n).padStart(2, "0");

function updatePosition() {
    const el = triggerRef.value;
    if (!el) return;
    const r = el.getBoundingClientRect();
    popoverPos.value = { top: r.bottom + 8, left: r.left, width: r.width };
}

function open() {
    if (props.disabled) return;
    updatePosition();
    isOpen.value = true;
}

function close() {
    isOpen.value = false;
}

function toggle() {
    if (props.disabled) return;
    if (isOpen.value) close();
    else open();
}

function clear() {
    emit("update:modelValue", "");
}

function onUpdate(date) {
    if (!date) {
        emit("update:modelValue", "");
        return;
    }

    if (props.withTime) {
        emit("update:modelValue", date.toISOString());
        // Mantém aberto para o usuário ajustar a hora
        return;
    }

    // Data sem hora → YYYY-MM-DD (local, evita shift de timezone)
    emit(
        "update:modelValue",
        `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`,
    );
    close();
}

function handleClickOutside(event) {
    if (!isOpen.value) return;
    const target = event.target;
    if (rootRef.value?.contains(target)) return;
    if (popoverRef.value?.contains(target)) return;
    close();
}

function onScrollResize() {
    if (isOpen.value) updatePosition();
}

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
    window.addEventListener("scroll", onScrollResize, true);
    window.addEventListener("resize", onScrollResize);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
    window.removeEventListener("scroll", onScrollResize, true);
    window.removeEventListener("resize", onScrollResize);
});
</script>

<style scoped>
/* Override v-calendar para o tema dark do admin.
   Estilos scoped + :deep alcançam o popover teleportado (o Vue propaga o data-attr). */
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

:deep(.vc-title) {
    font-weight: 600;
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

:deep(.vc-time-picker) {
    border-color: #2d333b;
}
</style>

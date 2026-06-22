<template>
  <label
    class="flex items-start gap-2 text-sm text-slate-300"
    :class="disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer'"
  >
    <input
      type="checkbox"
      :checked="modelValue"
      :disabled="disabled"
      class="mt-0.5 h-4 w-4 shrink-0 cursor-pointer rounded accent-primary focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 focus:ring-offset-surface-dark disabled:cursor-not-allowed disabled:opacity-50"
      @change="$emit('update:modelValue', $event.target.checked)"
    />
    <span>
      <slot>{{ label }}</slot>
    </span>
  </label>
</template>

<script setup>
defineProps({
  modelValue: {
    type: Boolean,
    default: false,
  },
  disabled: {
    type: Boolean,
    default: false,
  },
  label: {
    type: String,
    default: "",
  },
});

defineEmits(["update:modelValue"]);
</script>

<template>
  <div :class="['flex items-center justify-center', paddingClasses[padding]]">
    <div class="text-center">
      <LoadingSpinner :size="size" :color="color" />
      <p v-if="message" class="text-text-muted mt-4">{{ message }}</p>
    </div>
  </div>
</template>

<script setup>
import LoadingSpinner from './LoadingSpinner.vue'

const props = defineProps({
  message: {
    type: String,
    default: 'Carregando...'
  },
  size: {
    type: String,
    default: 'md'
  },
  color: {
    type: String,
    default: 'primary'
  },
  padding: {
    type: String,
    default: 'large',
    validator: (value) => ['small', 'medium', 'large'].includes(value)
  }
})

const paddingClasses = {
  small: 'py-8',
  medium: 'py-12',
  large: 'py-20'
}
</script>

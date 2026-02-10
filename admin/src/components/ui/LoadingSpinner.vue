<template>
  <div 
    :class="[
      'inline-block border-t-transparent rounded-full animate-spin',
      sizeClasses[size],
      colorClasses[color]
    ]"
  ></div>
</template>

<script setup>
const props = defineProps({
  size: {
    type: String,
    default: 'md',
    validator: (value) => ['xs', 'sm', 'md', 'lg', 'xl'].includes(value)
  },
  color: {
    type: String,
    default: 'primary',
    validator: (value) => ['primary', 'white', 'dark'].includes(value)
  }
})

const sizeClasses = {
  xs: 'w-4 h-4 border-2',
  sm: 'w-5 h-5 border-2',
  md: 'w-8 h-8 border-4',
  lg: 'w-12 h-12 border-4',
  xl: 'w-16 h-16 border-4'
}

const colorClasses = {
  primary: 'border-primary',
  white: 'border-white',
  dark: 'border-background-dark'
}
</script>

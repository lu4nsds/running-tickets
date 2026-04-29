<script setup>
import { ref, watch, onMounted, onBeforeUnmount } from 'vue'
import { useCitySearch } from '@/composables/useCitySearch'

const props = defineProps({
    modelValue: { type: String, default: '' },
})
const emit = defineEmits(['update:modelValue'])

const { suggestions, search, clear } = useCitySearch()

const inputText = ref(props.modelValue ?? '')
const inputRef = ref(null)
const dropdownStyle = ref({})

watch(() => props.modelValue, val => {
    if (val !== inputText.value) inputText.value = val ?? ''
})

function updateDropdownPosition() {
    if (!inputRef.value) return
    const rect = inputRef.value.getBoundingClientRect()
    dropdownStyle.value = {
        position: 'fixed',
        top: `${rect.bottom + 4}px`,
        left: `${rect.left}px`,
        width: `${rect.width}px`,
        zIndex: 9999,
    }
}

function onInput(e) {
    inputText.value = e.target.value
    updateDropdownPosition()
    search(inputText.value)
}

function select(city) {
    inputText.value = city
    emit('update:modelValue', city)
    clear()
}

function onBlur() {
    setTimeout(clear, 150)
}

function onScroll() {
    if (suggestions.value.length) updateDropdownPosition()
}

onMounted(() => window.addEventListener('scroll', onScroll, true))
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll, true))
</script>

<template>
    <div class="relative">
        <input
            ref="inputRef"
            :value="inputText"
            type="text"
            autocomplete="off"
            placeholder="Ex: Natal"
            class="w-full px-4 py-3 bg-surface-darker border border-border-dark rounded-lg text-white focus:outline-none focus:border-primary transition-colors placeholder:text-slate-500"
            @input="onInput"
            @blur="onBlur"
        />
        <Teleport to="body">
            <div
                v-if="suggestions.length"
                :style="dropdownStyle"
                class="bg-surface-dark border border-border-dark rounded-lg overflow-hidden shadow-lg"
            >
                <button
                    v-for="city in suggestions"
                    :key="city"
                    type="button"
                    class="w-full text-left px-4 py-2.5 text-sm text-white hover:bg-surface-elevated transition-colors"
                    @mousedown.prevent="select(city)"
                >
                    {{ city }}
                </button>
            </div>
        </Teleport>
    </div>
</template>

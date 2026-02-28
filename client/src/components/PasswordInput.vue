<template>
    <div class="w-full">
        <label
            v-if="label"
            :for="id"
            class="block text-sm font-medium text-gray-700 mb-1"
        >
            {{ label }}
            <span v-if="required" class="text-red-500">*</span>
        </label>

        <div class="relative">
            <input
                :id="id"
                :type="showPassword ? 'text' : 'password'"
                :value="modelValue"
                @input="$emit('update:modelValue', $event.target.value)"
                :placeholder="placeholder"
                :required="required"
                :disabled="disabled"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent disabled:bg-gray-100 disabled:cursor-not-allowed pr-10 bg-background-dark text-white"
                :class="{ 'border-red-500': error }"
            />

            <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                tabindex="-1"
            >
                <svg
                    v-if="!showPassword"
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                </svg>
                <svg
                    v-else
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                    />
                </svg>
            </button>
        </div>

        <p v-if="error" class="mt-1 text-sm text-red-500">{{ error }}</p>

        <!-- Password Strength Indicator -->
        <div v-if="showStrength && modelValue" class="mt-3">
            <!-- Progress Bar -->
            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                <div
                    class="h-full transition-all duration-300"
                    :class="strength.color"
                    :style="{ width }"
                ></div>
            </div>

            <!-- Strength Label -->
            <p
                v-if="strength.label"
                class="mt-1 text-sm font-medium"
                :class="strength.textColor"
            >
                Força da senha: {{ strength.label }}
            </p>

            <!-- Requirements -->
            <div class="mt-3 space-y-1">
                <p class="text-xs font-medium text-gray-700 mb-2">
                    Sua senha deve conter:
                </p>
                <div
                    v-for="(req, key) in requirements"
                    :key="key"
                    class="flex items-center text-xs"
                    :class="req.met ? 'text-green-600' : 'text-gray-500'"
                >
                    <svg
                        v-if="req.met"
                        class="w-4 h-4 mr-1"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg
                        v-else
                        class="w-4 h-4 mr-1"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ req.label }}
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, toRef } from "vue";
import { usePasswordStrength } from "@/composables/usePasswordStrength";

const props = defineProps({
    id: {
        type: String,
        default: "password",
    },
    modelValue: {
        type: String,
        default: "",
    },
    label: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "",
    },
    required: {
        type: Boolean,
        default: false,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    error: {
        type: String,
        default: "",
    },
    showStrength: {
        type: Boolean,
        default: true,
    },
});

defineEmits(["update:modelValue"]);

const showPassword = ref(false);

const passwordRef = toRef(props, "modelValue");
const { requirements, strength, width } = usePasswordStrength(passwordRef);
</script>

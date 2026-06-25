import { defineStore } from "pinia";
import { ref, computed } from "vue";

// Aumente a versão se a política de cookies mudar e o aviso precisar reaparecer.
const CURRENT_VERSION = 1;

export const useCookieConsentStore = defineStore("cookieConsent", () => {
    // State
    const consent = ref(JSON.parse(localStorage.getItem("cookie_consent")) || null);

    // Getters
    const hasConsented = computed(
        () => consent.value?.version === CURRENT_VERSION,
    );

    // Actions
    function accept() {
        consent.value = {
            accepted: true,
            version: CURRENT_VERSION,
            timestamp: new Date().toISOString(),
        };
        localStorage.setItem("cookie_consent", JSON.stringify(consent.value));
    }

    return {
        consent,
        hasConsented,
        accept,
    };
});

<template>
    <Transition
        enter-active-class="transition-all duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition-all duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
    >
        <div
            v-if="visible && !store.hasConsented"
            class="fixed bottom-4 left-4 z-50 max-w-sm w-[calc(100%-2rem)] sm:w-96 bg-surface-darker border border-primary/20 rounded-xl p-5 shadow-2xl"
        >
            <div class="flex items-start gap-3">
                <div
                    class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center bg-primary/10"
                >
                    <svg
                        class="w-5 h-5 text-primary"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M10 1a1 1 0 01.117 1.993l-.04.005a2 2 0 001.74 2.71 1 1 0 01.94 1.21 2 2 0 002.6 1.5 1 1 0 011.31.78A9 9 0 1110 1zm-2.5 6a1 1 0 100 2 1 1 0 000-2zM7 12a1 1 0 100 2 1 1 0 000-2zm5 1a1 1 0 100 2 1 1 0 000-2zm-1.5-4.5a1 1 0 100 2 1 1 0 000-2z"
                            clip-rule="evenodd"
                        />
                    </svg>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-white font-semibold text-sm mb-1">
                        Sua privacidade
                    </h3>
                    <p class="text-slate-400 text-sm">
                        Usamos apenas cookies essenciais para manter seu login,
                        carrinho e pagamento funcionando. Não fazemos
                        rastreamento. Saiba mais na nossa
                        <router-link
                            to="/politica-de-privacidade"
                            class="text-primary hover:underline"
                        >
                            Política de Privacidade</router-link
                        >.
                    </p>
                </div>
            </div>

            <button
                @click="accept"
                class="mt-4 w-full bg-primary text-background-dark font-bold rounded-xl px-4 py-2.5 hover:bg-primary-dark transition-all"
            >
                Entendi
            </button>
        </div>
    </Transition>
</template>

<script setup>
import { ref, onMounted } from "vue";
import { useCookieConsentStore } from "@/stores/cookieConsent";

const store = useCookieConsentStore();
const visible = ref(false);

function accept() {
    store.accept();
}

onMounted(() => {
    // Aciona a animação de entrada após a montagem.
    visible.value = true;
});
</script>

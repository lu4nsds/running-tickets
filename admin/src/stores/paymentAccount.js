import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { organizerPaymentAccountApi } from "@/api/organizer/paymentAccount";

export const usePaymentAccountStore = defineStore("paymentAccount", () => {
    // State
    const account = ref(null);
    const isLoading = ref(false);
    const isConnecting = ref(false);
    const error = ref(null);

    // Getters
    const isConnected = computed(() => account.value?.connected === true);

    // Actions
    const fetchStatus = async () => {
        isLoading.value = true;
        error.value = null;
        try {
            const data = await organizerPaymentAccountApi.status();
            account.value = data.account ?? null;
            return { success: true };
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                "Erro ao carregar o status da conexão.";
            return { success: false, error: error.value };
        } finally {
            isLoading.value = false;
        }
    };

    // Retorna a URL de autorização do MP para redirecionar o navegador.
    const connect = async () => {
        isConnecting.value = true;
        error.value = null;
        try {
            const data = await organizerPaymentAccountApi.connect();
            return { success: true, url: data.authorization_url };
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                "Não foi possível iniciar a conexão com o Mercado Pago.";
            return { success: false, error: error.value };
        } finally {
            isConnecting.value = false;
        }
    };

    const disconnect = async () => {
        error.value = null;
        try {
            await organizerPaymentAccountApi.disconnect();
            account.value = null;
            return { success: true };
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                "Erro ao desconectar a conta do Mercado Pago.";
            return { success: false, error: error.value };
        }
    };

    return {
        // State
        account,
        isLoading,
        isConnecting,
        error,
        // Getters
        isConnected,
        // Actions
        fetchStatus,
        connect,
        disconnect,
    };
});

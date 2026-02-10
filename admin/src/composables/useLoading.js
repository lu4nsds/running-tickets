import { ref } from "vue";

/**
 * Composable para gerenciar estados de loading e erros
 * @param {boolean} initialState - Estado inicial do loading (default: false)
 * @returns {Object} { isLoading, error, withLoading, setLoading, clearError }
 */
export const useLoading = (initialState = false) => {
    const isLoading = ref(initialState);
    const error = ref(null);

    /**
     * Wrapper para funções assíncronas com gerenciamento automático de loading/error
     * @param {Function} asyncFn - Função assíncrona a ser executada
     * @returns {Promise} Resultado da função assíncrona
     */
    const withLoading = async (asyncFn) => {
        isLoading.value = true;
        error.value = null;

        try {
            const result = await asyncFn();
            return result;
        } catch (err) {
            error.value =
                err.response?.data?.message ||
                err.message ||
                "Erro desconhecido";
            console.error("Error in withLoading:", err);
            throw err;
        } finally {
            isLoading.value = false;
        }
    };

    /**
     * Define o estado de loading manualmente
     * @param {boolean} value - Novo valor para isLoading
     */
    const setLoading = (value) => {
        isLoading.value = value;
    };

    /**
     * Limpa o erro atual
     */
    const clearError = () => {
        error.value = null;
    };

    return {
        isLoading,
        error,
        withLoading,
        setLoading,
        clearError,
    };
};

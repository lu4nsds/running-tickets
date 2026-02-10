/**
 * Composable para formatação de moeda e números
 */
export const useCurrency = () => {
    /**
     * Converte centavos para reais formatados
     * @param {number} cents - Valor em centavos
     * @param {string} currency - Código da moeda (default: BRL)
     * @returns {string} Valor formatado (ex: R$ 185.000,00)
     */
    const formatCurrency = (cents, currency = "BRL") => {
        const value = cents / 100;

        return new Intl.NumberFormat("pt-BR", {
            style: "currency",
            currency: currency,
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(value);
    };

    /**
     * Formata números com separador de milhares
     * @param {number} value - Valor numérico
     * @returns {string} Valor formatado (ex: 1.248)
     */
    const formatNumber = (value) => {
        return new Intl.NumberFormat("pt-BR").format(value || 0);
    };

    /**
     * Formata percentual
     * @param {number} value - Valor numérico
     * @param {number} decimals - Casas decimais (default: 2)
     * @returns {string} Valor formatado (ex: 85,71%)
     */
    const formatPercentage = (value, decimals = 2) => {
        return new Intl.NumberFormat("pt-BR", {
            style: "percent",
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals,
        }).format(value / 100);
    };

    return {
        formatCurrency,
        formatNumber,
        formatPercentage,
    };
};

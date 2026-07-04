/**
 * Status do pedido (armazenado no banco)
 * Sincronizado com: app/Enums/OrderStatus.php
 */
export const ORDER_STATUS = {
    PENDING: "pending",
    PROCESSING: "processing",
    PAID: "paid",
    FAILED: "failed",
    CANCELLED: "cancelled",
    REFUNDED: "refunded",
};

/**
 * Opções para o filtro de status.
 * A opção "Todos" (value "") existe apenas no front: limpa o filtro e
 * reexecuta a busca sem enviar `status` ao backend.
 */
export const ORDER_STATUS_OPTIONS = [
    { value: "", label: "Todos" },
    { value: ORDER_STATUS.PENDING, label: "Pendente" },
    { value: ORDER_STATUS.PROCESSING, label: "Processando" },
    { value: ORDER_STATUS.PAID, label: "Pago" },
    { value: ORDER_STATUS.FAILED, label: "Falhou" },
    { value: ORDER_STATUS.CANCELLED, label: "Cancelado" },
    { value: ORDER_STATUS.REFUNDED, label: "Reembolsado" },
];

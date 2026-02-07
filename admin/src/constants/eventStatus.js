/**
 * Status manual do evento (armazenado no banco)
 * Sincronizado com: app/Enums/EventStatus.php
 */
export const EVENT_STATUS = {
    ATIVO: "ativo",
    INATIVO: "inativo",
};

/**
 * Opções para filtro de status
 */
export const EVENT_STATUS_OPTIONS = [
    { value: "", label: "Todos os status" },
    { value: EVENT_STATUS.ATIVO, label: "Ativos" },
    { value: EVENT_STATUS.INATIVO, label: "Inativos" },
];

/**
 * Status visuais calculados (não armazenados)
 * Representam o estado temporal do evento
 */
export const VISUAL_STATUS = {
    INATIVO: "INATIVO", // event.status === 'inativo'
    ATIVO: "ATIVO", // Antes de date_start
    EM_ANDAMENTO: "EM ANDAMENTO", // Entre date_start e date_end
    ENCERRADO: "ENCERRADO", // Após date_end
};

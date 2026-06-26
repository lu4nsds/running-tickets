/**
 * Status manual do evento (armazenado no banco)
 * Sincronizado com: app/Enums/EventStatus.php
 */
export const EVENT_STATUS = {
    ACTIVE: "active",
    INACTIVE: "inactive",
    FINISHED: "finished",
};

/**
 * Opções para filtro de status
 */
export const EVENT_STATUS_OPTIONS = [
    { value: "", label: "Todos os status" },
    { value: EVENT_STATUS.ACTIVE, label: "Ativos" },
    { value: EVENT_STATUS.INACTIVE, label: "Inativos" },
    { value: EVENT_STATUS.FINISHED, label: "Encerrados" },
];

/**
 * Opções para o select de status no formulário de edição
 */
export const EVENT_STATUS_FORM_OPTIONS = [
    { value: EVENT_STATUS.INACTIVE, label: "Inativo" },
    { value: EVENT_STATUS.ACTIVE, label: "Ativo" },
    { value: EVENT_STATUS.FINISHED, label: "Encerrado" },
];

/**
 * Status visuais calculados (não armazenados)
 * Representam o estado temporal do evento
 */
export const VISUAL_STATUS = {
    INATIVO: "INATIVO", // event.status === 'inactive'
    ATIVO: "ATIVO", // Antes de date_start
    EM_ANDAMENTO: "EM ANDAMENTO", // Entre date_start e date_end
    ENCERRADO: "ENCERRADO", // event.status === 'finished' ou após date_end
};

import { EVENT_STATUS, VISUAL_STATUS } from "@/constants/eventStatus";

/**
 * Composable para lógica de status de eventos
 */
export const useEventStatus = () => {
    /**
     * Calcula o status visual do evento
     * Combina status manual (ativo/inativo) com status temporal (baseado em datas)
     */
    const getVisualStatus = (event) => {
        // Status manual tem prioridade
        if (event.status === EVENT_STATUS.INATIVO) {
            return VISUAL_STATUS.INATIVO;
        }

        // Status baseado em datas
        const now = new Date();
        const start = new Date(event.date_start);
        const end = new Date(event.date_end);

        if (now < start) {
            return VISUAL_STATUS.ATIVO;
        }

        if (now >= start && now <= end) {
            return VISUAL_STATUS.EM_ANDAMENTO;
        }

        return VISUAL_STATUS.ENCERRADO;
    };

    /**
     * Retorna as classes CSS para o badge de status
     */
    const getStatusClass = (visualStatus) => {
        const classes = {
            [VISUAL_STATUS.INATIVO]: "bg-red-500/20 text-red-400",
            [VISUAL_STATUS.ATIVO]: "bg-primary/20 text-primary",
            [VISUAL_STATUS.EM_ANDAMENTO]: "bg-blue-500/20 text-blue-400",
            [VISUAL_STATUS.ENCERRADO]: "bg-gray-500/20 text-gray-400",
        };
        return classes[visualStatus] || "bg-gray-500/20 text-gray-400";
    };

    return {
        getVisualStatus,
        getStatusClass,
    };
};

import { ref, computed, onMounted, onUnmounted } from "vue";
import api from "../api/axios";

/**
 * Agrupa Tickets emitidos (orders pagos) + Orders abertos (PENDING, PROCESSING,
 * FAILED com reserva válida) por evento. Cada grupo expõe o estado relevante
 * para renderizar o botão state-driven em MyTicketsView.
 */
export function useUserOrderGroups() {
    const loading = ref(true);
    const error = ref(null);
    const tickets = ref([]);
    const orders = ref([]);
    const now = ref(Date.now());

    let tickInterval = null;

    async function load() {
        loading.value = true;
        error.value = null;
        try {
            const [ticketsResp, ordersResp] = await Promise.all([
                api.get("/tickets"),
                api.get("/orders"),
            ]);
            tickets.value = ticketsResp.data.data || [];
            orders.value = ordersResp.data.data || [];
        } catch (err) {
            console.error("Erro ao carregar tickets/orders:", err);
            error.value =
                err.response?.data?.message ||
                "Erro ao carregar seus ingressos.";
        } finally {
            loading.value = false;
        }
    }

    function reservedRemaining(order) {
        if (!order?.reserved_until) return 0;
        const ms = new Date(order.reserved_until).getTime() - now.value;
        return Math.max(0, ms);
    }

    function hasActiveReservation(order) {
        return reservedRemaining(order) > 0;
    }

    function isPayableOrder(order) {
        if (!order) return false;
        return (
            ["pending", "failed"].includes(order.status) &&
            hasActiveReservation(order)
        );
    }

    /**
     * Resolve qual botão exibir para cada grupo (event).
     * Prioridade: PAID > PROCESSING > FAILED com reserva > PENDING com reserva
     * > expirado/cancelado.
     */
    function resolveAction(group) {
        if (group.tickets.length > 0) {
            return {
                label: "Ver Comprovante",
                to: {
                    name: "event-tickets",
                    params: { eventId: group.event.id },
                },
                variant: "primary",
                disabled: false,
            };
        }

        const processing = group.openOrders.find(
            (o) => o.status === "processing",
        );
        if (processing) {
            return {
                label: "Processando...",
                to: null,
                variant: "muted",
                disabled: true,
            };
        }

        const failedPayable = group.openOrders.find(
            (o) => o.status === "failed" && hasActiveReservation(o),
        );
        if (failedPayable) {
            return {
                label: "Efetuar Pagamento",
                to: {
                    name: "payment",
                    params: { reference: failedPayable.reference },
                },
                variant: "warning",
                disabled: false,
                order: failedPayable,
            };
        }

        const pendingPayable = group.openOrders.find(
            (o) => o.status === "pending" && hasActiveReservation(o),
        );
        if (pendingPayable) {
            return {
                label: "Efetuar Pagamento",
                to: {
                    name: "payment",
                    params: { reference: pendingPayable.reference },
                },
                variant: "warning",
                disabled: false,
                order: pendingPayable,
            };
        }

        // Sem reserva válida e nada pago — pedido expirado/cancelado.
        return {
            label: "Pedido expirado",
            to: null,
            variant: "muted",
            disabled: true,
        };
    }

    function formatCountdown(ms) {
        const totalSec = Math.floor(ms / 1000);
        const m = Math.floor(totalSec / 60);
        const s = totalSec % 60;
        return `${m}:${s.toString().padStart(2, "0")}`;
    }

    const groups = computed(() => {
        const map = new Map();

        for (const ticket of tickets.value) {
            const eventId = ticket.event?.id;
            if (!eventId) continue;
            if (!map.has(eventId)) {
                map.set(eventId, {
                    event: ticket.event,
                    tickets: [],
                    openOrders: [],
                });
            }
            map.get(eventId).tickets.push(ticket);
        }

        for (const order of orders.value) {
            if (!["pending", "processing", "failed"].includes(order.status)) {
                continue;
            }
            if (order.status !== "processing" && !hasActiveReservation(order)) {
                continue;
            }
            const eventId = order.event?.id;
            if (!eventId) continue;

            if (!map.has(eventId)) {
                map.set(eventId, {
                    event: order.event,
                    tickets: [],
                    openOrders: [],
                });
            }
            map.get(eventId).openOrders.push(order);
        }

        return Array.from(map.values()).map((group) => {
            const action = resolveAction(group);
            const countdownOrder = action.order;
            return {
                ...group,
                action,
                countdownMs: countdownOrder
                    ? reservedRemaining(countdownOrder)
                    : 0,
                countdown: countdownOrder
                    ? formatCountdown(reservedRemaining(countdownOrder))
                    : null,
            };
        });
    });

    onMounted(() => {
        load();
        tickInterval = setInterval(() => {
            now.value = Date.now();
        }, 1000);
    });

    onUnmounted(() => {
        if (tickInterval) clearInterval(tickInterval);
    });

    return {
        loading,
        error,
        groups,
        reload: load,
    };
}

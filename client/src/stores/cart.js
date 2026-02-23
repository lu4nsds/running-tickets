import { defineStore } from "pinia";
import { ref, computed } from "vue";

export const useCartStore = defineStore("cart", () => {
    // State
    const items = ref(JSON.parse(localStorage.getItem("cart_items")) || []);

    // Getters
    const totalItems = computed(() => {
        return items.value.reduce((sum, item) => sum + item.quantity, 0);
    });

    const totalAmount = computed(() => {
        return items.value.reduce(
            (sum, item) => sum + item.price_cents * item.quantity,
            0,
        );
    });

    const isEmpty = computed(() => items.value.length === 0);

    // Actions
    function addItem(ticketType, event, category, quantity = 1) {
        const existingItem = items.value.find(
            (item) => item.ticket_type_id === ticketType.id,
        );

        if (existingItem) {
            existingItem.quantity += quantity;
        } else {
            items.value.push({
                ticket_type_id: ticketType.id,
                event_id: event.id,
                category_id: category.id,
                event_name: event.name,
                event_slug: event.slug,
                ticket_type_name: ticketType.name,
                category_name: category.name,
                price_cents: ticketType.price_cents,
                quantity: quantity,
                max_per_order: ticketType.max_per_order,
            });
        }

        saveToLocalStorage();
    }

    function removeItem(ticketTypeId) {
        items.value = items.value.filter(
            (item) => item.ticket_type_id !== ticketTypeId,
        );
        saveToLocalStorage();
    }

    function updateQuantity(ticketTypeId, quantity) {
        const item = items.value.find((i) => i.ticket_type_id === ticketTypeId);
        if (item) {
            item.quantity = Math.max(1, Math.min(quantity, item.max_per_order));
            saveToLocalStorage();
        }
    }

    function clearCart() {
        items.value = [];
        saveToLocalStorage();
    }

    function saveToLocalStorage() {
        localStorage.setItem("cart_items", JSON.stringify(items.value));
    }

    return {
        items,
        totalItems,
        totalAmount,
        isEmpty,
        addItem,
        removeItem,
        updateQuantity,
        clearCart,
    };
});

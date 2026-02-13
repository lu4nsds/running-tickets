<template>
    <aside
        class="fixed inset-y-0 left-0 z-50 w-64 bg-[#0F1114] border-r border-surface-elevated transform transition-transform duration-200 ease-in-out lg:translate-x-0"
        :class="{ '-translate-x-full': !isOpen }"
    >
        <!-- Logo -->
        <div class="p-6">
            <div class="flex items-center gap-2 mb-10">
                <div
                    class="w-8 h-8 bg-primary rounded-lg flex items-center justify-center shadow-[0_0_15px_rgba(0,230,119,0.5)]"
                >
                    <span
                        class="material-symbols-outlined text-background-dark font-bold"
                    >
                        bolt
                    </span>
                </div>
                <span
                    class="text-xl font-extrabold tracking-tight uppercase italic"
                >
                    Running<span class="text-primary">Tickets</span>
                </span>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto px-6 space-y-2">
            <router-link
                v-for="item in menuItems"
                :key="item.path"
                :to="item.path"
                class="flex items-center gap-3 px-4 py-3 rounded-lg transition-all group"
                :class="
                    $route.path === item.path
                        ? 'bg-primary/10 text-primary neon-border-active font-semibold'
                        : 'text-text-secondary hover:bg-white/5 hover:text-white'
                "
            >
                <span
                    class="material-symbols-outlined text-[20px] transition-colors"
                    :class="
                        $route.path === item.path
                            ? 'text-primary'
                            : 'group-hover:text-primary'
                    "
                >
                    {{ item.icon }}
                </span>
                <span class="font-medium">{{ item.label }}</span>
            </router-link>
        </nav>

        <!-- Logout -->
        <div class="p-4 border-t border-surface-elevated">
            <button
                @click="handleLogout"
                class="flex items-center gap-3 px-4 py-3 rounded-lg text-text-secondary hover:bg-surface-elevated hover:text-red-400 transition-all w-full group"
            >
                <span
                    class="material-symbols-outlined text-[20px] group-hover:text-red-400 transition-colors"
                >
                    logout
                </span>
                <span class="font-medium">Sair</span>
            </button>
        </div>
    </aside>

    <!-- Mobile Overlay -->
    <div
        v-if="isOpen"
        class="fixed inset-0 bg-black/50 z-40 lg:hidden"
        @click="emit('update:isOpen', false)"
    ></div>
</template>

<script setup>
import { computed } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["update:isOpen"]);

const router = useRouter();
const authStore = useAuthStore();

const menuItems = computed(() => {
    if (authStore.isSuperAdmin) {
        return [
            { path: "/admin/dashboard", icon: "dashboard", label: "Dashboard" },
            {
                path: "/admin/organizers",
                icon: "group",
                label: "Organizadores",
            },
            { path: "/admin/events", icon: "event", label: "Eventos" },
        ];
    } else {
        const items = [
            {
                path: "/organizer/dashboard",
                icon: "dashboard",
                label: "Dashboard",
            },
            { path: "/organizer/events", icon: "event", label: "Eventos" },
        ];

        // Adiciona configurações de pagamento apenas para ADMIN (não STAFF)
        if (authStore.canEditPaymentSettings) {
            items.push({
                path: "/organizer/payment-settings",
                icon: "account_balance",
                label: "Configurações de Pagamento",
            });
        }

        return items;
    }
});

const handleLogout = async () => {
    await authStore.logout();
    router.push("/login");
};
</script>

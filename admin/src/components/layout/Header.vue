<template>
    <header
        class="h-16 bg-card-bg border-b border-surface-elevated flex items-center justify-between px-6"
    >
        <!-- Left: Hamburger + Breadcrumb -->
        <div class="flex items-center gap-4">
            <!-- Hamburger Menu (mobile) -->
            <button
                @click="emit('toggle-sidebar')"
                class="lg:hidden text-text-secondary hover:text-white transition-colors"
            >
                <span class="material-symbols-outlined text-2xl">menu</span>
            </button>

            <!-- Page Title -->
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-semibold text-white">
                    {{ pageTitle }}
                </h1>
            </div>
        </div>

        <!-- Right: Notifications + User Menu -->
        <div class="flex items-center gap-4">
            <!-- User Menu -->
            <div class="relative">
                <button
                    @click="showUserMenu = !showUserMenu"
                    class="flex items-center gap-3 hover:bg-surface-elevated px-3 py-2 rounded-lg transition-colors"
                >
                    <!-- Avatar -->
                    <div
                        class="w-8 h-8 rounded-full bg-primary flex items-center justify-center text-background-dark font-bold text-sm"
                    >
                        {{ userInitials }}
                    </div>

                    <div class="hidden md:block text-left">
                        <p class="text-sm font-semibold text-white">
                            {{ authStore.user?.name }}
                        </p>
                        <p class="text-xs text-text-muted">{{ userRole }}</p>
                    </div>
                </button>

                <!-- Dropdown -->
                <Transition name="dropdown">
                    <div
                        v-if="showUserMenu"
                        v-click-outside="() => (showUserMenu = false)"
                        class="absolute right-0 mt-2 w-56 bg-card-bg border border-surface-elevated rounded-lg shadow-2xl py-2 z-50"
                    >
                        <a
                            href="#"
                            @click.prevent="showUserMenu = false"
                            class="flex items-center gap-3 px-4 py-2 text-text-secondary hover:bg-surface-elevated hover:text-white transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]"
                                >person</span
                            >
                            <span class="text-sm">Meu Perfil</span>
                        </a>

                        <a
                            href="#"
                            @click.prevent="showUserMenu = false"
                            class="flex items-center gap-3 px-4 py-2 text-text-secondary hover:bg-surface-elevated hover:text-white transition-colors"
                        >
                            <span class="material-symbols-outlined text-[18px]"
                                >settings</span
                            >
                            <span class="text-sm">Configurações</span>
                        </a>

                        <div
                            class="border-t border-surface-elevated my-2"
                        ></div>

                        <button
                            @click="handleLogout"
                            class="flex items-center gap-3 px-4 py-2 text-red-400 hover:bg-surface-elevated transition-colors w-full"
                        >
                            <span class="material-symbols-outlined text-[18px]"
                                >logout</span
                            >
                            <span class="text-sm">Sair</span>
                        </button>
                    </div>
                </Transition>
            </div>
        </div>
    </header>
</template>

<script setup>
import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const emit = defineEmits(["toggle-sidebar"]);

const router = useRouter();
const authStore = useAuthStore();

const showUserMenu = ref(false);

const pageTitle = computed(() => {
    if (authStore.isSuperAdmin) {
        return "Administrador";
    }
    return "Organizador";
});

const userInitials = computed(() => {
    const name = authStore.user?.name || "U";
    return name
        .split(" ")
        .map((n) => n[0])
        .slice(0, 2)
        .join("")
        .toUpperCase();
});

const userRole = computed(() => {
    if (authStore.isSuperAdmin) return "Super Admin";
    if (authStore.hasOrganizers) {
        const firstOrg = authStore.userOrganizers[0];
        const role = firstOrg?.pivot?.role;
        return role === "admin" ? "Organizador Admin" : "Organizador Staff";
    }
    return "Usuário";
});

const handleLogout = async () => {
    showUserMenu.value = false;
    await authStore.logout();
    router.push("/login");
};

// Click outside directive
const vClickOutside = {
    mounted(el, binding) {
        el.clickOutsideEvent = (event) => {
            if (!(el === event.target || el.contains(event.target))) {
                binding.value();
            }
        };
        document.addEventListener("click", el.clickOutsideEvent);
    },
    unmounted(el) {
        document.removeEventListener("click", el.clickOutsideEvent);
    },
};
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.15s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>

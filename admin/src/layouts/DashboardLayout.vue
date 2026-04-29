<template>
    <div class="flex h-screen overflow-hidden bg-background-dark">
        <!-- Sidebar -->
        <Sidebar
            :is-open="sidebarOpen"
            @update:is-open="sidebarOpen = $event"
        />

        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden lg:ml-64">
            <!-- Header -->
            <Header @toggle-sidebar="sidebarOpen = !sidebarOpen" />

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto p-4">
                <router-view />
            </main>
        </div>

        <!-- Aviso de expiração de sessão -->
        <SessionExpiryModal
            :show="showWarning && !dismissed"
            :formatted-time="formattedTime"
            :seconds-remaining="secondsRemaining"
            :can-dismiss="canDismiss"
            @dismiss="dismissed = true"
            @relogin="handleRelogin"
        />
    </div>
</template>

<script setup>
import { ref, watch } from "vue";
import { useRouter, useRoute } from "vue-router";
import Sidebar from "@/components/layout/Sidebar.vue";
import Header from "@/components/layout/Header.vue";
import SessionExpiryModal from "@/components/ui/SessionExpiryModal.vue";
import { useSessionExpiry } from "@/composables/useSessionExpiry";
import { useAuthStore } from "@/stores/auth";

const router    = useRouter();
const route     = useRoute();
const auth      = useAuthStore();
const sidebarOpen = ref(false);
const dismissed   = ref(false);

const { showWarning, canDismiss, secondsRemaining, formattedTime } = useSessionExpiry();

// Quando cai abaixo de 1 minuto, força reabrir o modal mesmo que tenha sido dispensado
watch(canDismiss, (val) => {
    if (!val) dismissed.value = false;
});

async function handleRelogin() {
    await auth.logout();
    router.push({ path: '/login', query: { redirect: route.fullPath, reason: 'expired' } });
}
</script>

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
    </div>
</template>

<script setup>
import { ref } from "vue";
import Sidebar from "@/components/layout/Sidebar.vue";
import Header from "@/components/layout/Header.vue";

const sidebarOpen = ref(false);
</script>

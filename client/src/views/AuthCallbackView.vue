<template>
    <div
        class="min-h-screen bg-background-dark text-slate-100 flex items-center justify-center"
    >
        <div class="text-center">
            <div
                class="inline-block size-10 animate-spin rounded-full border-4 border-primary border-t-transparent mb-4"
            ></div>
            <p class="text-slate-400">{{ message }}</p>
        </div>
    </div>
</template>

<script setup>
import { onMounted, ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "../stores/auth";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const message = ref("Autenticando...");

onMounted(async () => {
    const token = route.query.token;
    const error = route.query.error;

    if (error || !token) {
        message.value = "Erro na autenticação. Redirecionando...";
        setTimeout(() => router.push({ name: "login" }), 2000);
        return;
    }

    try {
        await authStore.loginWithToken(token);
        const redirect = route.query.redirect || "/";
        router.push(redirect);
    } catch {
        message.value = "Erro ao carregar dados. Redirecionando...";
        setTimeout(() => router.push({ name: "login" }), 2000);
    }
});
</script>

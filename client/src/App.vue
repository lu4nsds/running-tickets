<template>
    <RouterView :key="$route.fullPath" />
    <CookieConsentBanner />
</template>

<script setup>
// App component - renderiza o router-view (com key para forçar remontagem)
// e o aviso de cookies global, fora do RouterView para persistir entre rotas.
import CookieConsentBanner from "@/components/CookieConsentBanner.vue";
</script>

<style>
/* Estilos globais já estão em style.css */
</style>

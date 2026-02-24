<template>
    <RouterView :key="$route.fullPath" />
</template>

<script setup>
// App component - apenas renderiza o router-view com key para forçar remontagem
</script>

<style>
/* Estilos globais já estão em style.css */
</style>

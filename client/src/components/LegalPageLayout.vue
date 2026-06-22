<template>
  <div class="min-h-screen flex flex-col bg-background-dark text-slate-100">
    <Navbar :hide-search="true" />

    <main class="flex-grow">
      <!-- Header Section -->
      <section class="relative overflow-hidden border-b border-border-dark bg-surface-dark/50">
        <div
          class="absolute inset-0 bg-gradient-to-t from-background-dark via-background-dark/60 to-transparent"
        ></div>
        <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8 lg:py-16">
          <div class="max-w-3xl space-y-4">
            <div
              class="inline-flex items-center rounded-full border border-primary/20 bg-primary/20 px-3 py-1 text-sm font-medium text-primary backdrop-blur-sm"
            >
              <span class="material-symbols-outlined mr-1 text-base">gavel</span>
              Documento Legal
            </div>

            <h1
              class="text-4xl font-black leading-tight tracking-tight text-white sm:text-5xl"
            >
              {{ title }}
            </h1>

            <p v-if="lastUpdated" class="text-sm text-slate-400">
              Última atualização: {{ lastUpdated }}
            </p>
          </div>
        </div>
      </section>

      <!-- Content Section -->
      <section class="bg-background-dark py-16">
        <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
          <div class="space-y-10 text-slate-300 leading-relaxed">
            <slot />
          </div>
        </div>
      </section>
    </main>

    <Footer />
  </div>
</template>

<script setup>
import Navbar from "./Navbar.vue";
import Footer from "./Footer.vue";

defineProps({
  title: {
    type: String,
    required: true,
  },
  lastUpdated: {
    type: String,
    default: "",
  },
});
</script>

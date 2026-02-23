<template>
  <footer class="bg-background-dark pt-16 text-slate-400">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
      <div class="grid grid-cols-1 gap-12 lg:grid-cols-4">
        <!-- Brand Column -->
        <div class="space-y-4">
          <div class="flex items-center gap-2 text-white">
            <span class="material-symbols-outlined text-primary text-2xl">bolt</span>
            <span class="text-lg font-black tracking-tight">
              RUNNING <span class="text-primary">TICKETS</span>
            </span>
          </div>
          <p class="text-sm leading-relaxed">
            A plataforma líder para corredores que buscam superar seus limites. Encontre,
            inscreva-se e corra nas melhores provas do Brasil.
          </p>
          <div class="flex gap-4 pt-2">
            <a href="#" class="text-slate-400 hover:text-primary transition-colors">
              <span class="material-symbols-outlined">public</span>
            </a>
            <a href="#" class="text-slate-400 hover:text-primary transition-colors">
              <span class="material-symbols-outlined">alternate_email</span>
            </a>
            <a href="#" class="text-slate-400 hover:text-primary transition-colors">
              <span class="material-symbols-outlined">chat</span>
            </a>
          </div>
        </div>

        <!-- Links Column 1 -->
        <div>
          <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Plataforma</h3>
          <ul class="space-y-3 text-sm">
            <li>
              <router-link to="/" class="hover:text-primary transition-colors">Eventos</router-link>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Calendário</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Resultados</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Fotos</a>
            </li>
          </ul>
        </div>

        <!-- Links Column 2 -->
        <div>
          <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Empresa</h3>
          <ul class="space-y-3 text-sm">
            <li>
              <a href="#" class="hover:text-primary transition-colors">Sobre nós</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Carreiras</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Blog</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Imprensa</a>
            </li>
          </ul>
        </div>

        <!-- Links Column 3 -->
        <div>
          <h3 class="mb-4 text-sm font-bold uppercase tracking-wider text-white">Suporte</h3>
          <ul class="space-y-3 text-sm">
            <li>
              <a href="#" class="hover:text-primary transition-colors">Central de Ajuda</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Política de Privacidade</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Termos de Uso</a>
            </li>
            <li>
              <a href="#" class="hover:text-primary transition-colors">Fale Conosco</a>
            </li>
          </ul>
        </div>
      </div>

      <div class="mt-16 border-t border-border-dark py-8 text-center text-xs">
        <p>© {{ currentYear }} Running Tickets. Todos os direitos reservados.</p>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'

const currentYear = computed(() => new Date().getFullYear())
</script>

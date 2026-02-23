# Running Tickets - Admin Frontend

Backoffice administrativo para gestão de eventos, organizadores e ingressos.

## 🚀 Instalação

### 1. Usar Node.js 20.9.0

**⚠️ IMPORTANTE:** Este projeto usa **Vite 6.0.7** que é compatível com **Node.js 20.9.0**.

```bash
nvm use 20.9.0
```

Se não tiver essa versão instalada:

```bash
nvm install 20.9.0
nvm use 20.9.0
```

**Por que Node 20.9.0?**

- ✅ Compatível com Vite 6.x
- ✅ Versão estável e testada no projeto
- ✅ Funciona perfeitamente com todas as dependências
- ❌ Vite 7+ requer Node 20.19+ (não compatível com nosso setup)

### 2. Instalar Dependências

```bash
npm install
```

### 3. Configurar Variáveis de Ambiente

O arquivo `.env` já está criado com:

```
VITE_API_URL=http://localhost:8000/api
```

Para produção, crie `.env.production` com a URL da sua API.

### 4. Iniciar Servidor de Desenvolvimento

```bash
npm run dev
```

Acesse: http://localhost:5174

**Caso de erro:**
Se aparecer erro de Node.js incompatível:

```bash
node --version  # deve mostrar v20.9.0
```

## 🎨 Stack Tecnológica

- **Vue 3.5.13** (Composition API)
- **Vue Router 4.5** (Navegação)
- **Pinia 2.3** (State Management)
- **Axios 1.7** (HTTP Client)
- **Tailwind CSS 3.4** (Estilização)
- **ApexCharts 5.3** (Gráficos)
- **v-calendar 3.1** (Date pickers)
- **Vite 6.0.7** (Build Tool - compatível com Node 20.9.0)

## 📁 Estrutura

```
admin/
├── src/
│   ├── api/           # Configuração Axios e endpoints
│   ├── assets/        # CSS global e assets
│   ├── components/    # Componentes reutilizáveis
│   ├── layouts/       # Layouts (Dashboard, Auth)
│   ├── router/        # Configuração de rotas
│   ├── stores/        # Pinia stores (auth, etc)
│   ├── views/         # Páginas/Views
│   ├── App.vue        # Componente raiz
│   └── main.js        # Entry point
├── index.html
├── package.json
├── tailwind.config.js
└── vite.config.js
```

## 🔐 Autenticação

O sistema usa **Laravel Sanctum** (token-based auth):

1. Login: `POST /api/auth/login`
2. Token salvo no `localStorage`
3. Requisições incluem: `Authorization: Bearer {token}`
4. Logout: `POST /api/auth/logout`

## 👥 Tipos de Usuário

### Super Admin

- CRUD completo de organizadores
- CRUD completo de eventos
- Gerenciar usuários de organizadores
- Dashboard global da plataforma

### Organizer Admin

- Visualização de eventos (read-only)
- Configurar pagamentos dos eventos
- Dashboard do organizador

### Organizer Staff

- Apenas visualização (read-only)
- Dashboard do organizador

## 🎨 Tema Dark

O sistema usa tema escuro com verde vibrante (#00E676) como cor primária:

- Background: #0F1114
- Cards: #1A1D23
- Inputs: #1E2128
- Texto: #E1E3E6
- Primário: #00E676 (usado apenas em ações importantes e estados positivos)

## 🛠️ Desenvolvimento

```bash
# Modo desenvolvimento
npm run dev

# Build para produção
npm run build

# Preview da build
npm run preview
```

## 📝 Convenções

- **Vue 3 Composition API** (`<script setup>`)
- **Tailwind CSS** para estilização
- **Material Symbols** para ícones
- **PT-BR** em todos os textos
- **Componentização** de elementos reutilizáveis

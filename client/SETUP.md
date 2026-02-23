# Running Tickets - Client (Frontend)

Plataforma cliente para compra de ingressos de corridas e maratonas.

## 🚀 Tecnologias

- **Vue 3.5.13** (Composition API)
- **Vue Router 4.5** - Navegação entre páginas
- **Pinia 2.3** - Gerenciamento de estado
- **Tailwind CSS 3.4** - Estilização
- **Axios 1.7** - Requisições HTTP
- **date-fns 4.1** - Formatação de datas
- **Vite 6.0.7** - Build tool (compatível com Node 20.9.0)

## 📦 Instalação

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
- ✅ Mesma versão usada no projeto Admin
- ✅ Estável e testado
- ❌ Vite 7+ requer Node 20.19+ ou 22.12+ (não compatível com nosso setup)

### 2. Instalar Dependências

```bash
npm install
```

### 3. Configurar Variáveis de Ambiente

O arquivo `.env` já está criado com:

```
VITE_API_URL=http://localhost:8000/api
```

Para produção, edite `.env.production` com a URL da sua API.

### 4. Executar em Desenvolvimento

```bash
npm run dev
```

Acesse: http://localhost:5173

**Caso de erro:**
Se aparecer erro de Node.js incompatível, confirme que está usando Node 20.9.0:

```bash
node --version  # deve mostrar v20.9.0
```

### 5. Build para Produção

```bash
npm run build
```

Os arquivos serão gerados em `dist/`

## 📁 Estrutura do Projeto

```
client/
├── src/
│   ├── api/
│   │   └── axios.js          # Configuração do Axios
│   ├── components/
│   │   ├── Navbar.vue        # Barra de navegação
│   │   ├── Footer.vue        # Rodapé
│   │   └── EventCard.vue     # Card de evento
│   ├── stores/
│   │   ├── auth.js           # Store de autenticação
│   │   ├── cart.js           # Store do carrinho
│   │   ├── events.js         # Store de eventos
│   │   └── orders.js         # Store de pedidos
│   ├── views/
│   │   ├── HomeView.vue      # Página inicial
│   │   ├── EventDetailsView.vue
│   │   ├── CartView.vue
│   │   ├── MyOrdersView.vue
│   │   ├── OrderDetailsView.vue
│   │   ├── MyTicketsView.vue
│   │   ├── TicketDetailsView.vue
│   │   ├── LoginView.vue
│   │   └── RegisterView.vue
│   ├── router/
│   │   └── index.js          # Configuração de rotas
│   ├── App.vue
│   ├── main.js
│   └── style.css             # Estilos globais + Tailwind
├── public/
├── .env                      # Variáveis de ambiente (dev)
├── .env.production           # Variáveis de ambiente (prod)
├── tailwind.config.js        # Configuração do Tailwind
├── postcss.config.js
├── vite.config.js
└── package.json
```

## 🎨 Design System

### Cores

- **Primary:** `#00e677` (Verde neon)
- **Primary Dark:** `#00cc6a`
- **Background Dark:** `#0F1114`
- **Surface Dark:** `#1E212B`
- **Border Dark:** `#252B3A`

### Fontes

- **Inter** (Google Fonts) - Display e Body
- **Material Symbols Outlined** - Ícones

## 🔐 Segurança de URLs

O projeto está configurado para usar identificadores seguros:

- **Eventos:** `/eventos/{slug}` (exemplo: `/eventos/meia-maratona-2026`)
- **Pedidos:** `/meus-pedidos/{reference}` (exemplo: `/meus-pedidos/ORD-ABC123`)
- **Ingressos:** `/meus-ingressos/{code}` (exemplo: `/meus-ingressos/uuid...`)

Isso previne enumeração de IDs e aumenta a segurança.

## 📱 Funcionalidades

### Implementadas

- ✅ **Home** com hero section e grid de eventos
- ✅ **Navbar** responsiva com carrinho e autenticação
- ✅ **Footer** com links e informações
- ✅ **Event Card** com badges dinâmicos
- ✅ **Router** com rotas protegidas
- ✅ **Stores** (Auth, Cart, Events, Orders)
- ✅ **API Service** com interceptors

### A Implementar

- ⏳ **EventDetailsView** - Detalhes do evento e seleção de ingressos
- ⏳ **CartView** - Resumo e checkout
- ⏳ **MyOrdersView** - Lista de pedidos do usuário
- ⏳ **OrderDetailsView** - Detalhes e QR codes
- ⏳ **MyTicketsView** - Lista de ingressos
- ⏳ **TicketDetailsView** - QR code individual
- ⏳ **LoginView** - Autenticação
- ⏳ **RegisterView** - Cadastro

## 🔧 Desenvolvimento

### Adicionar Nova View

1. Criar arquivo em `src/views/NomeView.vue`
2. Adicionar rota em `src/router/index.js`
3. Implementar componente

### Adicionar Novo Store

1. Criar arquivo em `src/stores/nome.js`
2. Definir state, getters e actions usando `defineStore`
3. Importar e usar em componentes com `useNomeStore()`

### Estilização

Use classes do Tailwind CSS. Cores personalizadas disponíveis:

- `bg-primary`, `text-primary`, `border-primary`
- `bg-background-dark`, `bg-surface-dark`
- `shadow-neon` (efeito neon verde)

## 🚧 Status

**Versão:** 0.1.0 (Em desenvolvimento)

**Pronto:**

- ✅ Estrutura base
- ✅ Home completa
- ✅ Componentes principais
- ✅ Stores e API

**Próximo:**

- 📋 Implementar views restantes
- 📋 Integração completa com backend
- 📋 Testes
- 📋 Deploy

## 📝 Notas

- Backend deve estar rodando em `http://localhost:8000`
- Design baseado no mockup do Stitch
- Dark mode by default
- Responsivo (mobile-first)

# Running Tickets - Admin Frontend

Backoffice administrativo para gestão de eventos, organizadores e ingressos.

## 🚀 Instalação

```bash
# Instalar dependências
npm install

# Copiar variáveis de ambiente
cp .env .env.local  # (o arquivo .env já existe)

# Iniciar servidor de desenvolvimento
npm run dev
```

Acesse: http://localhost:5174

## 🎨 Stack Tecnológica

- **Vue 3** (Composition API)
- **Vue Router** (Navegação)
- **Pinia** (State Management)
- **Axios** (HTTP Client)
- **Tailwind CSS** (Estilização)
- **Vite** (Build Tool)

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

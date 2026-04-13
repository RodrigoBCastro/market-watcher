# MarketWatcher Frontend (Vue 3)

Painel web para operação do MarketWatcher com foco nos módulos V4/V5 (universos + asset master).

## Stack

- Vue 3 (`<script setup>`)
- Vue Router
- Vite
- MDI (`@mdi/js`) para ações icon-based

## Requisitos

- Node.js 20.19+ (ou 22.12+)
- npm 10+

## Setup

```bash
npm install
npm run dev
```

## Build

```bash
npm run build
npm run preview
```

## Configuração de API

A aplicação consome endpoints em `/api/*` no mesmo host.

Se necessário, configure proxy/base URL no ambiente de execução do frontend.

## Fluxo de autenticação

- Tela de login em `/login`
- Token salvo em `localStorage` (`marketwatcher.token`)
- Guard de rota redireciona para login quando não autenticado

## Telas principais

- Dashboard de gestão (`/dashboard`)
- Portfólio real (`/portfolio`)
- Risco e exposição (`/risk`)
- Performance real (`/performance`)
- Alertas inteligentes (`/alerts`)
- Módulos legados mantidos: Calls, Watchlist, Briefs
- Asset Master Registry (`/asset-master`) com sync, filtros, índices e bootstrap do Data Universe

## Padrões UI

- Tema único `dark-blue` com tokens em `src/styles/tokens.css`
- Componentes base em `src/components/ui`
- Modais reutilizáveis com bloqueio de fechamento por clique externo
- Feedbacks com toast lateral

## Observações

- O frontend espera que o backend tenha migrations/seeders V5 aplicados.
- Endpoints de gestão de trading exigem autenticação via Bearer token.

# MarketWatcher Backend

Backend Laravel 13 para análise técnica de swing trade na B3, com ingestão de dados de mercado, cálculo local de indicadores, motor de decisão (score 0-100), geração de brief diário e API REST.

## Stack

- PHP 8.3+
- Laravel 13
- PostgreSQL (preferencial)
- Queue + Scheduler
- HTTP Client (Brapi/HG Brasil)

## Módulos Implementados Nesta Base

- Arquitetura por contratos (`app/Contracts`) e DTOs (`app/DTOs`)
- Providers de mercado (`BrapiProvider`, `HgBrasilProvider`)
- Pipeline de indicadores:
  - SMA, EMA, RSI, MACD, ATR, Bollinger, ADX, Estocástico, ROC
  - métricas de volume/estrutura/volatilidade
- Motor de decisão:
  - detecção de setups obrigatórios
  - scoring por dimensão
  - regras de veto (stop, alvo, RR, ativo esticado)
- Jobs e commands operacionais
- Endpoints REST de auth, watchlist, sync, análise, oportunidades, briefs e dashboard
- Seeders básicos (admin, watchlist inicial, macro snapshot)

## Estrutura Principal

- `app/Contracts`: interfaces do domínio
- `app/DTOs`: contratos de transferência
- `app/Services/Indicators`: cálculo técnico local
- `app/Services/Scoring`: score por dimensão + composição final
- `app/Services/Analysis`: setups + decisão de trade
- `app/Services/Briefing`: geração do brief diário
- `app/Jobs`: sync, recálculo e brief
- `app/Console/Commands`: execução manual de pipeline
- `app/Http/Controllers/Api`: controllers finos

## Migrations/Tabelas

Além das tabelas padrão do Laravel, foram adicionadas:

- `user_api_tokens`
- `monitored_assets`
- `asset_quotes`
- `market_indexes`
- `macro_snapshots`
- `technical_indicators`
- `asset_analysis_scores`
- `generated_briefs`
- `generated_brief_items`
- `sync_runs`
- `sync_run_logs`

## Configuração

Adicione no `.env`:

```env
BRAPI_TOKEN=
BRAPI_BASE_URL=https://brapi.dev/api
BRAPI_TIMEOUT=10
BRAPI_RETRIES=2

HG_BRASIL_KEY=
HG_BRASIL_BASE_URL=https://api.hgbrasil.com
HG_BRASIL_TIMEOUT=10
HG_BRASIL_RETRIES=2

MARKET_SYNC_ASSET_DAYS=320
API_TOKEN_TTL_DAYS=30
```

## Execução

```bash
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

## Autenticação

- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/auth/me`

Use o token retornado no header:

```http
Authorization: Bearer <token>
```

## Endpoints Principais

- Watchlist:
  - `GET /api/assets`
  - `POST /api/assets`
  - `PATCH /api/assets/{id}`
  - `DELETE /api/assets/{id}`
- Sync:
  - `POST /api/sync/assets/{ticker}`
  - `POST /api/sync/assets`
  - `POST /api/sync/market`
  - `POST /api/sync/full`
- Indicadores/Análise:
  - `GET /api/assets/{ticker}/quotes`
  - `GET /api/assets/{ticker}/indicators`
  - `GET /api/assets/{ticker}/analysis`
  - `GET /api/opportunities/top`
  - `GET /api/opportunities/avoid`
- Brief:
  - `POST /api/briefs/generate`
  - `GET /api/briefs`
  - `GET /api/briefs/{date}`
- Dashboard:
  - `GET /api/dashboard`

## Commands

```bash
php artisan market:sync-assets {ticker?} --now
php artisan market:sync-context --now
php artisan market:recalculate-indicators {ticker?} --now
php artisan market:recalculate-scores {ticker?} --now
php artisan market:generate-brief {date?} --now
```

Sem `--now`, os jobs são enfileirados.

## Scheduler

Configurado em `routes/console.php`:

- `market:sync-assets`
- `market:sync-context`
- `market:recalculate-indicators`
- `market:recalculate-scores`
- `market:generate-brief`

## Seed Inicial

Credenciais admin:

- Email: `admin@marketwatcher.local`
- Senha: `Admin@123456`

Troque imediatamente em ambiente real.

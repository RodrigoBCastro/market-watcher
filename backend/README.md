# MarketWatcher Backend (Laravel)

Backend da plataforma MarketWatcher para análise técnica, geração de calls e gestão completa de trading (V3).

## Stack

- PHP 8.3+
- Laravel 13
- PostgreSQL (recomendado)
- Queue + Scheduler
- Providers de mercado (Brapi/HG Brasil)

## Arquitetura

- `app/Contracts`: interfaces do domínio
- `app/DTOs`: contratos de saída/entrada de serviços
- `app/Services/*`: regra de negócio desacoplada de controller
- `app/Http/Controllers/Api`: controllers finos
- `app/Jobs` + `app/Console/Commands`: execução assíncrona e operacional

## Módulos V3

- Position sizing com validação de risco e capital disponível
- Configuração de risco por usuário (`risk_settings`)
- Portfólio real (`portfolio_positions`) com eventos operacionais
- Marcação a mercado e derivados de PnL/risco/duração
- Fechamentos parciais e totais (`portfolio_closed_positions`)
- Risco global da carteira e bloqueios por regra
- Exposição por setor e ativo (`asset_sector_mappings`)
- Correlação aproximada por retornos diários
- Regime de mercado (`bull`, `neutral`, `correction`, `bear`, `high_volatility`)
- Ajuste de filtros por regime
- Confidence score operacional das calls
- Simulação de portfólio
- Curva de capital (`equity_curve_points`)
- Métricas de performance real
- Alertas inteligentes (`trading_alerts`)
- Dashboard unificado de gestão

## Principais Tabelas

Além das tabelas base do sistema:

- `risk_settings`
- `asset_sector_mappings`
- `portfolio_positions`
- `portfolio_position_events`
- `portfolio_closed_positions`
- `equity_curve_points`
- `trading_alerts`
- `trade_calls` (com campos de confiança/regime)

## Configuração (.env)

```env
BRAPI_TOKEN=
BRAPI_BASE_URL=https://brapi.dev/api
BRAPI_TIMEOUT=10
BRAPI_RETRIES=2

HG_BRASIL_KEY=
HG_BRASIL_BASE_URL=https://api.hgbrasil.com
HG_BRASIL_TIMEOUT=10
HG_BRASIL_RETRIES=2

MARKET_SYNC_ASSET_DAYS=90
API_TOKEN_TTL_DAYS=30

CALLS_MAX_PER_CYCLE=8
CALLS_MIN_SCORE=70
CALLS_MIN_RR=1.5
CALLS_MIN_HISTORY=8
CALLS_MAX_HOLDING_DAYS=20

RANKING_TECHNICAL_WEIGHT=0.6
RANKING_EXPECTANCY_WEIGHT=0.4
OPTIMIZER_MIN_RANK=55
QUANT_ALERT_DRAWDOWN_THRESHOLD=8

RISK_DEFAULT_TOTAL_CAPITAL=10000
RISK_DEFAULT_RISK_PER_TRADE_PERCENT=1
RISK_DEFAULT_MAX_PORTFOLIO_RISK_PERCENT=8
RISK_DEFAULT_MAX_OPEN_POSITIONS=8
RISK_DEFAULT_MAX_POSITION_SIZE_PERCENT=25
RISK_DEFAULT_MAX_SECTOR_EXPOSURE_PERCENT=40
RISK_DEFAULT_MAX_CORRELATED_POSITIONS=3
RISK_DEFAULT_ALLOW_PYRAMIDING=false

REGIME_BULL_MIN_SCORE=70
REGIME_BULL_MAX_CALLS=7
REGIME_NEUTRAL_MIN_SCORE=75
REGIME_NEUTRAL_MAX_CALLS=5
REGIME_CORRECTION_MIN_SCORE=80
REGIME_CORRECTION_MAX_CALLS=2
REGIME_BEAR_MIN_SCORE=80
REGIME_BEAR_MAX_CALLS=2
REGIME_HIGH_VOL_MIN_SCORE=82
REGIME_HIGH_VOL_MAX_CALLS=2

CORRELATION_LOOKBACK_DAYS=90
CORRELATION_HIGH_THRESHOLD=0.75

ALERT_NEAR_STOP_THRESHOLD_PERCENT=1.5
ALERT_NEAR_TARGET_THRESHOLD_PERCENT=2
ALERT_CONFIDENCE_DROP_THRESHOLD=12
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

Header:

```http
Authorization: Bearer <token>
```

## Endpoints V3 de Gestão de Trading

### Configurações de risco

- `GET /api/risk-settings`
- `PUT /api/risk-settings`

### Position sizing

- `POST /api/position-sizing/calculate`

### Portfólio

- `GET /api/portfolio`
- `GET /api/portfolio/open`
- `GET /api/portfolio/closed`
- `POST /api/portfolio/positions`
- `PATCH /api/portfolio/positions/{id}`
- `POST /api/portfolio/positions/{id}/close`
- `POST /api/portfolio/positions/{id}/partial-close`
- `POST /api/portfolio/simulate`

### Risco

- `GET /api/portfolio/risk`
- `GET /api/portfolio/exposure`
- `GET /api/portfolio/correlations`

### Performance

- `GET /api/performance/summary`
- `GET /api/performance/equity-curve`
- `GET /api/performance/by-setup`
- `GET /api/performance/by-asset`
- `GET /api/performance/by-sector`
- `GET /api/performance/by-regime`

### Alertas

- `GET /api/alerts`
- `POST /api/alerts/{id}/read`

## Commands de Gestão V3

```bash
php artisan market:portfolio-mark-to-market --now
php artisan market:refresh-alerts --now
php artisan market:snapshot-equity --now
php artisan market:trading-pipeline --now
```

Sem `--now`, a execução vai para fila.

## Scheduler

Jobs recorrentes de V3 em `routes/console.php`:

- `market:portfolio-mark-to-market`
- `market:refresh-alerts`
- `market:snapshot-equity`

## Seed Inicial

Credenciais admin padrão:

- Email: `admin@marketwatcher.local`
- Senha: `Admin@123456`

Troque imediatamente em ambiente real.

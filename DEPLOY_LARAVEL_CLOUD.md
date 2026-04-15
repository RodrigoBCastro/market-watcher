# Deploy no Laravel Cloud (Monorepo `backend/` + `frontend/`)

Guia para publicar o MarketWatcher no Laravel Cloud servindo API Laravel e SPA Vue no mesmo dominio.

## 1) Requisitos no repositorio

- Estrutura esperada:
  - `backend/` (Laravel)
  - `frontend/` (Vue + Vite)
- Arquivo `composer.lock` na raiz do repo (necessario para o workaround de monorepo no Cloud).

## 2) Criacao da aplicacao no Cloud

1. Crie a aplicacao a partir do repositorio no GitHub.
2. Selecione PHP `8.3` e Node `20`.
3. Crie/anexe recursos:
   - PostgreSQL
   - Redis

## 3) Variaveis de ambiente (producao)

Defina no environment:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://SEU_DOMINIO`
- `VITE_API_BASE_URL=/api`
- `QUEUE_CONNECTION=redis`
- `CACHE_STORE=redis`
- `SESSION_DRIVER=redis`
- `DB_CONNECTION=pgsql`
- `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (vindos do recurso Postgres)
- `REDIS_HOST`, `REDIS_PORT`, `REDIS_PASSWORD` (vindos do recurso Redis)
- `BRAPI_TOKEN`
- `HG_BRASIL_KEY`

Se `APP_KEY` nao estiver definido, gere uma vez via comando remoto:

```bash
php artisan key:generate --show
```

Copie o valor gerado para `APP_KEY` no environment.

## 4) Build Command (monorepo)

Use este comando no Deploy > Build Command:

```bash
set -eux
mkdir -p /tmp/monorepo
mv backend frontend /tmp/monorepo/

cp -Rf /tmp/monorepo/backend/. .

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

cd /tmp/monorepo/frontend
npm ci
npm run build

cp -Rf dist/* /var/www/html/public/

cd /var/www/html
rm -rf /tmp/monorepo

php artisan optimize
```

## 5) Deploy Command

Use este comando no Deploy > Deploy Command:

```bash
php artisan migrate --force
```

Opcional no primeiro deploy, se precisar de dados iniciais:

```bash
php artisan db:seed --force
```

## 6) Runtime (fila e scheduler)

- Ative worker de fila:
  - Comando: `php artisan queue:work --tries=3 --timeout=90`
- Ative scheduler no environment para rodar tarefas agendadas.

## 7) Roteamento SPA

O backend precisa manter fallback para rotas web da SPA (ex.: `/assets`, `/dashboard`) e ignorar `/api/*`.
Esse ajuste ja foi aplicado em `backend/routes/web.php`.

## 8) Checklist rapido

- Deploy finalizou sem erro
- Migrations aplicadas
- Login funcionando
- Rotas SPA com refresh funcionando (sem 404)
- Chamadas `/api/*` funcionando
- Worker de fila ativo
- Scheduler ativo

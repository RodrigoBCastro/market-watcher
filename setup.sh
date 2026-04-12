#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

log() {
  printf "\n[%s] %s\n" "$(date "+%H:%M:%S")" "$*"
}

fail() {
  printf "Erro: %s\n" "$*" >&2
  exit 1
}

require_command() {
  local command_name="$1"
  if ! command -v "$command_name" >/dev/null 2>&1; then
    fail "Comando '$command_name' nao encontrado."
  fi
}

prepare_docker_auth() {
  local docker_config_dir
  local docker_config_file
  local docker_config_tmp

  docker_config_dir="${DOCKER_CONFIG:-$HOME/.docker}"
  docker_config_file="${docker_config_dir}/config.json"

  if [[ ! -f "$docker_config_file" ]]; then
    return 0
  fi

  if grep -Eq '"credsStore"[[:space:]]*:[[:space:]]*"desktop\.exe"' "$docker_config_file"; then
    docker_config_tmp="$(mktemp -d)"
    printf "{}\n" >"${docker_config_tmp}/config.json"
    export DOCKER_CONFIG="$docker_config_tmp"
    log "Detectado credsStore=desktop.exe. Usando DOCKER_CONFIG temporario para evitar falha no pull/build."
  fi
}

set_env_var() {
  local file_path="$1"
  local key="$2"
  local value="$3"
  local escaped_value

  escaped_value="$(printf '%s' "$value" | sed -e 's/[|&]/\\&/g')"

  if grep -q "^${key}=" "$file_path"; then
    sed -i "s|^${key}=.*|${key}=${escaped_value}|" "$file_path"
  else
    printf "%s=%s\n" "$key" "$value" >>"$file_path"
  fi
}

compose() {
  if [[ "$COMPOSE_BIN" == "docker compose" ]]; then
    docker compose "$@"
  else
    docker-compose "$@"
  fi
}

require_command docker

if docker compose version >/dev/null 2>&1; then
  COMPOSE_BIN="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  COMPOSE_BIN="docker-compose"
else
  fail "Docker Compose nao encontrado (nem plugin 'docker compose' nem binario 'docker-compose')."
fi

prepare_docker_auth

if ! docker info >/dev/null 2>&1; then
  fail "Docker daemon nao esta ativo. Inicie o Docker e execute novamente."
fi

mkdir -p backend frontend

if [[ ! -f backend/artisan ]]; then
  log "Criando backend Laravel em ./backend"
  docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$ROOT_DIR/backend:/app" \
    -w /app \
    composer:2 \
    sh -lc "composer create-project laravel/laravel . --prefer-dist --no-interaction"
else
  log "Backend Laravel ja existe. Pulando scaffold."
fi

if [[ ! -f frontend/package.json ]]; then
  log "Criando frontend Vue 3 (Vite) em ./frontend"
  docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$ROOT_DIR/frontend:/app" \
    -w /app \
    node:20-alpine \
    sh -lc "npx --yes create-vite@latest . --template vue && npm install"
else
  log "Frontend Vue ja existe. Pulando scaffold."
fi

if [[ ! -f backend/.env ]]; then
  log "Gerando backend/.env"
  cp backend/.env.example backend/.env
fi

if [[ ! -f frontend/.env ]]; then
  log "Gerando frontend/.env"
  if [[ -f frontend/.env.example ]]; then
    cp frontend/.env.example frontend/.env
  else
    touch frontend/.env
  fi
fi

set_env_var backend/.env APP_NAME FinWise
set_env_var backend/.env APP_ENV local
set_env_var backend/.env APP_DEBUG true
set_env_var backend/.env APP_URL http://localhost:8000
set_env_var backend/.env FRONTEND_URL http://localhost:5173
set_env_var backend/.env DB_CONNECTION pgsql
set_env_var backend/.env DB_HOST postgres
set_env_var backend/.env DB_PORT 5432
set_env_var backend/.env DB_DATABASE marketwatcher
set_env_var backend/.env DB_USERNAME marketwatcher
set_env_var backend/.env DB_PASSWORD marketwatcher
set_env_var backend/.env CACHE_STORE redis
set_env_var backend/.env SESSION_DRIVER redis
set_env_var backend/.env QUEUE_CONNECTION redis
set_env_var backend/.env REDIS_HOST redis
set_env_var backend/.env REDIS_PORT 6379
set_env_var backend/.env MAIL_MAILER smtp
set_env_var backend/.env MAIL_HOST mailpit
set_env_var backend/.env MAIL_PORT 1025
set_env_var backend/.env MAIL_USERNAME null
set_env_var backend/.env MAIL_PASSWORD null
set_env_var backend/.env MAIL_ENCRYPTION null
set_env_var backend/.env MAIL_FROM_ADDRESS noreply@marketwatcher.local
set_env_var backend/.env MAIL_FROM_NAME '"${APP_NAME}"'
set_env_var backend/.env SANCTUM_STATEFUL_DOMAINS localhost:5173,127.0.0.1:5173

set_env_var frontend/.env VITE_APP_NAME FinWise
set_env_var frontend/.env VITE_API_BASE_URL /api/v1

log "Instalando dependencias do backend (Composer)"
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$ROOT_DIR/backend:/app" \
  -w /app \
  composer:2 \
  composer install --no-interaction

log "Instalando dependencias do frontend (NPM)"
docker run --rm \
  -u "$(id -u):$(id -g)" \
  -v "$ROOT_DIR/frontend:/app" \
  -w /app \
  node:20-alpine \
  npm install

log "Construindo imagens"
compose build

log "Subindo stack Docker base"
compose up -d postgres redis mailpit app frontend

log "Aguardando PostgreSQL ficar pronto"
until compose exec -T postgres pg_isready -U marketwatcher -d marketwatcher >/dev/null 2>&1; do
  sleep 2
done

log "Gerando APP_KEY"
compose exec -T app php artisan key:generate --force

log "Executando migrations"
compose exec -T app php artisan migrate --force

log "Subindo workers de fila e scheduler"
compose up -d queue scheduler

printf "\nSetup concluido.\n"
printf "API Laravel:   http://localhost:8000\n"
printf "Frontend Vue:  http://localhost:5173\n"
printf "Mailpit:       http://localhost:8025\n"

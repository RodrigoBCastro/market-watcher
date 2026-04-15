set -e

# copia backend para raiz
cp -Rf backend/. .

# remove pasta antiga (CRÍTICO)
rm -rf backend

# instala dependências
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# build frontend
cd frontend
npm ci
npm run build

# copia build
cp -Rf dist/. ../public/

cd ..

# otimiza Laravel
php artisan optimize
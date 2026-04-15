set -e

# copia backend para raiz (ESSENCIAL)
cp -Rf backend/. .

# instala Laravel
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# build frontend
cd frontend
npm ci
npm run build

# copia build pro public
cp -Rf dist/. ../public/

# otimiza
cd ..
php artisan optimize
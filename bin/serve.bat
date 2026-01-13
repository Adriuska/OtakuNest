@echo off
REM Create DB if not exists and run migrations, then start PHP built-in server
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
echo Starting PHP dev server at http://127.0.0.1:8000
php -S 127.0.0.1:8000 -t public

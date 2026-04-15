#!/usr/bin/env bash
# Migraties draaien voor approval-flow + mail accounts
set -e
cd "$(dirname "$0")/.."

echo "→ Migraties uitvoeren..."
php artisan migrate

echo "→ Route cache leegmaken..."
php artisan route:clear
php artisan config:clear
php artisan view:clear

echo "✓ Klaar! De approval-flow en mail accounts staan klaar."

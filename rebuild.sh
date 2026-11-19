#!/bin/bash
# Rebuild completo y fiable: build + reinicio limpio de SSR + limpieza profunda de cachés (Redis RAM + Vistas)
# Uso: bash rebuild.sh

set -e

echo "🔄 1/4 Deteniendo SSR anterior y liberando puerto 13714..."
pkill -f "bootstrap/ssr/ssr.js" 2>/dev/null || true
fuser -k 13714/tcp 2>/dev/null || true
sleep 1

echo "🔨 2/4 Compilando frontend y servidor SSR con Vite..."
npm run build 2>&1 | tail -5

echo "🧹 3/4 Limpiando cachés (Vistas, Configuración y Redis RAM)..."
php artisan view:clear 2>/dev/null || true
php artisan config:clear 2>/dev/null || true
php artisan cache:clear 2>/dev/null || true
php artisan tinker --execute="try { \Illuminate\Support\Facades\Cache::store('redis')->tags(['public_pages'])->flush(); echo 'Redis RAM limpiada'; } catch (\Throwable \$e) {}" 2>/dev/null || true

echo "🚀 4/4 Arrancando nuevo proceso SSR en segundo plano..."
nohup node bootstrap/ssr/ssr.js > storage/logs/ssr.log 2>&1 &
sleep 2

if pgrep -f "bootstrap/ssr/ssr.js" > /dev/null; then
    echo "✅ SSR CORRIENDO (PID: $(pgrep -f "bootstrap/ssr/ssr.js" | head -1))"
    echo "🎉 TODO LISTO Y ACTUALIZADO."
else
    echo "❌ ERROR: SSR no arrancó. Revisa storage/logs/ssr.log"
    exit 1
fi

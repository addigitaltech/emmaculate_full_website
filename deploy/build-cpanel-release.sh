#!/usr/bin/env bash
# Builds a ready-to-upload release zip for cPanel hosting.
# Run it on a computer (or Termux) that has PHP 8.3+, Composer and Node 20+.
# The server then needs no Composer or Node, which shared hosting often cannot run.
set -euo pipefail
cd "$(dirname "$0")/.."

for tool in php composer npm zip; do
  command -v "$tool" >/dev/null 2>&1 || { echo "Missing tool: $tool"; exit 1; }
done

composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
npm ci
npm run build

OUT="release-$(date +%Y%m%d-%H%M).zip"
rm -f "$OUT"
zip -qr "$OUT" . \
  -x ".git/*" "node_modules/*" "tests/*" ".env" "storage/logs/*" "storage/framework/cache/*" \
     "storage/framework/sessions/*" "storage/framework/views/*" "release-*.zip" ".github/*" \
     "docker/*" "Dockerfile" ".dockerignore"
echo "Created $OUT. Upload it to the server and follow docs/CPANEL_DEPLOYMENT.md"

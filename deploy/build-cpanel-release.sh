#!/usr/bin/env bash
# Build a ready-to-upload production ZIP for cPanel hosting.
# Run on a computer with PHP 8.3+, Composer 2, Node 20+ and zip.
# The server then needs no Composer or Node.
set -euo pipefail
cd "$(dirname "$0")/.."

for tool in php composer npm zip; do
  command -v "$tool" >/dev/null 2>&1 || { echo "Missing tool: $tool"; exit 1; }
done

composer install --no-dev --optimize-autoloader --classmap-authoritative --no-interaction
npm ci
npm run build

test -f public/build/manifest.json || { echo "Missing Vite production manifest" >&2; exit 1; }
test -f vendor/autoload.php || { echo "Missing Composer autoloader" >&2; exit 1; }

OUT="emmaculate-cpanel-release-$(date +%Y%m%d-%H%M).zip"
rm -f "$OUT"
zip -qr "$OUT" . \
  -x ".git/*" "node_modules/*" "tests/*" ".env" \
     "storage/logs/*" "storage/framework/cache/*" \
     "storage/framework/sessions/*" "storage/framework/views/*" "release-*.zip" ".github/*" \
     "emmaculate-cpanel-release-*.zip" ".phpunit.result.cache" "phpunit.xml" ".github/*" \
     "docker/*" "Dockerfile" ".dockerignore"

# Keep Laravel's writable directory placeholders; the wildcard exclusions above
# intentionally remove local cache, sessions, views and logs.
for placeholder in \
  storage/framework/cache/.gitignore \
  storage/framework/cache/data/.gitignore \
  storage/framework/sessions/.gitignore \
  storage/framework/views/.gitignore \
  storage/logs/.gitignore; do
  [ -f "$placeholder" ] && zip -q "$OUT" "$placeholder"
done

if unzip -l "$OUT" | grep -E '(^|/)(\.env$|\.git/|tests/|node_modules/)' >/dev/null; then
  echo "Release contains a forbidden secret or development file" >&2
  exit 1
fi

echo "Created $OUT. Upload it outside public_html and follow docs/CPANEL_DEPLOYMENT.md"

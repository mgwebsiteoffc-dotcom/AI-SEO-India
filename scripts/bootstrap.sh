#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# AI-SEO-India sandbox bootstrap
#
# Rebuilds everything that is intentionally NOT committed to git (.env,
# node_modules, vendor/, public/build/, database.sqlite) from scratch.
# The sandbox preview host changes on every reset, so APP_URL is derived from
# the environment instead of being hard-coded anywhere.
#
# Usage:
#   scripts/bootstrap.sh            # full restore (deps, vendor, build, seed)
#   scripts/bootstrap.sh --no-build # skip the vite build
#
# Requires network access to npmjs.org and github.com (codeload/api).
# Packagist/getcomposer are blocked in this sandbox, hence scripts/fetch-vendor.mjs.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail
cd "$(dirname "$0")/.."

DO_BUILD=1
[[ "${1:-}" == "--no-build" ]] && DO_BUILD=0

echo "▸ Frontend dependencies (npm ci — must run before the no-save php-wasm install)"
npm ci --no-audit --no-fund >/dev/null

echo "▸ PHP runtime (php-wasm, installed without --save so npm ci doesn't remove it)"
if [[ ! -x node_modules/.bin/php-wasm-cli ]]; then
  npm install --no-save --no-audit --no-fund @php-wasm/cli@3.1.52 >/dev/null
fi
PHP="$(pwd)/node_modules/.bin/php-wasm-cli"
"$PHP" -v | head -1

echo "▸ Composer packages → vendor/ (GitHub zipballs, see fetch-vendor.mjs)"
node scripts/fetch-vendor.mjs

echo "▸ .env"
if [[ ! -f .env ]]; then
  cp .env.example .env
fi
# APP_URL: derive from E2B preview host (sandbox id) or default to localhost.
SID="${E2B_SANDBOX_ID:-}"
if [[ -n "$SID" ]]; then
  APP_URL="https://8123-${SID}.e2b.app"
else
  APP_URL="http://127.0.0.1:8123"
fi
python3 - "$APP_URL" <<'PY'
import sys, re
url = sys.argv[1]
p = '.env'
s = open(p).read()
s = re.sub(r'^APP_ENV=.*$', 'APP_ENV=local', s, flags=re.M)
s = re.sub(r'^APP_DEBUG=.*$', 'APP_DEBUG=true', s, flags=re.M)
s = re.sub(r'^APP_URL=.*$', f'APP_URL={url}', s, flags=re.M)
s = re.sub(r'^DB_CONNECTION=.*$', 'DB_CONNECTION=sqlite', s, flags=re.M)
s = re.sub(r'^DB_HOST=.*$', '', s, flags=re.M)
s = re.sub(r'^DB_PORT=.*$', '', s, flags=re.M)
s = re.sub(r'^DB_DATABASE=.*$', '', s, flags=re.M)
s = re.sub(r'^DB_USERNAME=.*$', '', s, flags=re.M)
s = re.sub(r'^DB_PASSWORD=.*$', '', s, flags=re.M)
s = re.sub(r'^SESSION_DRIVER=.*$', 'SESSION_DRIVER=database', s, flags=re.M)
open(p, 'w').write(s)
PY
echo "   APP_URL=$(grep ^APP_URL .env | cut -d= -f2)"
echo "   SESSION_DRIVER=$(grep ^SESSION_DRIVER .env | cut -d= -f2)"

echo "▸ APP_KEY"
if ! grep -q '^APP_KEY=base64:' .env; then
  "$PHP" artisan key:generate --force >/dev/null
fi

echo "▸ SQLite + migrations"
touch database/database.sqlite
"$PHP" artisan migrate --force

echo "▸ Demo dataset (idempotent)"
"$PHP" artisan demo:seed

if [[ "$DO_BUILD" == "1" ]]; then
  echo "▸ Vite build"
  npm run build
fi

echo
echo "✓ Bootstrap complete."
echo "  Run the server:  node_modules/.bin/php-wasm-cli -S 0.0.0.0:8123 -t public scripts/server-router.php"
echo "  Demo:            ${APP_URL}/app?demo=1"

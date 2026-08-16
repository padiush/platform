#!/usr/bin/env sh
# Brings up a throwaway Padiush with a fictional study already in it.
#
# Everything here is idempotent, so mounting a volume over the database keeps
# your poking around between runs, and not mounting one gives you a clean
# instance every time. Both are reasonable ways to try software.
set -e

cd /var/www/html

# Laravel wants a .env to exist even when every value is already in the
# environment, and package discovery has not run yet in the image because the
# build had no application to discover against.
[ -f .env ] || : > .env
php artisan package:discover --ansi >/dev/null

# key:generate *replaces* an APP_KEY line rather than appending one, so against
# an empty file it succeeds and writes nothing — and the first thing to need
# encryption then dies. Give it a line to replace.
grep -q '^APP_KEY=' .env || printf 'APP_KEY=\n' >> .env

if [ -z "${APP_KEY:-}" ] && ! grep -q '^APP_KEY=.\+' .env; then
    php artisan key:generate --force --ansi >/dev/null
    echo "  generated an application key"
fi

[ -f "$DB_DATABASE" ] || { mkdir -p "$(dirname "$DB_DATABASE")"; : > "$DB_DATABASE"; }

php artisan migrate --force --ansi

# Only on an empty database. A mounted volume keeps whatever you did last time
# rather than silently resetting it underneath you.
if [ "$(php artisan tinker --execute='echo \App\Models\User::count();' 2>/dev/null | tail -1)" = "0" ]; then
    echo "  seeding the demonstration study…"
    php artisan db:seed --class=ProjectCapabilitySeeder --force --ansi >/dev/null
    php artisan db:seed --class=DemoProjectSeeder --force --ansi
fi

php artisan storage:link --quiet 2>/dev/null || true

cat <<'BANNER'

  Padiush is running at  http://localhost:8000

    email     demo@padiush.test
    password  demo-screenshots

  A fictional study is already loaded: invented informants, real botanical
  names. Nothing here is anyone's data.

  This is an evaluation image — SQLite in the container, a published
  password, and a development server. Not for real research.

BANNER

exec php artisan serve --host=0.0.0.0 --port=8000 --no-reload

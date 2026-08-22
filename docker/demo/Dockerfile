# A self-contained Padiush you can try in one command.
#
#   docker run --rm -p 8000:8000 ghcr.io/padiush/platform-demo
#
# SQLite, local disk, no queue worker, no object storage, no mail server — the
# application already defaults to all of those, and the test suite runs against
# SQLite, so a demo needs nothing beside it. It seeds a fictional study on first
# boot so there is something to look at.
#
# This image is for EVALUATION. It serves through `artisan serve`, keeps its
# database inside the container, and ships a published password. Do not run it
# for real research — see docs/deployment/ for that.

# ------------------------------------------------------------------- base ---
# Shared by the dependency and runtime stages so Composer's platform check runs
# against the extensions that will actually be there, rather than being waved
# through with --ignore-platform-reqs and failing later at runtime.
FROM php:8.4-cli AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends sqlite3 \
    && rm -rf /var/lib/apt/lists/*

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions bcmath exif gd intl pdo_sqlite pdo_mysql zip opcache

# ---------------------------------------------------------------- PHP deps ---
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
# --no-scripts: the post-install hook runs artisan, which wants an application
# that does not exist yet at this point in the build.
RUN composer install \
      --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# ------------------------------------------------------------------ assets ---
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY resources ./resources
COPY scripts ./scripts
COPY vite.config.mjs tailwind.config.js postcss.config.cjs ./
COPY public ./public
# Also generates public/build/licenses.json — the attribution the bundle owes.
RUN npm run build

# ----------------------------------------------------------------- runtime ---
FROM base AS runtime

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Defaults chosen so `docker run` needs no flags at all.
#
# APP_ENV is "demo" rather than "production" deliberately: the demo seeder
# refuses to run in production, which is the right guard and not one to work
# around. Debug stays off regardless.
ENV APP_ENV=demo \
    APP_DEBUG=false \
    APP_URL=http://localhost:8000 \
    LOG_CHANNEL=stderr \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/var/www/html/database/database.sqlite \
    CACHE_DRIVER=file \
    SESSION_DRIVER=file \
    QUEUE_CONNECTION=sync \
    FILESYSTEM_DISK=local \
    MAIL_MAILER=log \
    REGISTRATION_ENABLED=false \
    PUBLIC_SITE_ENABLED=false \
    TRANSCRIPTION_ENABLED=false

RUN chmod -R ugo+rw storage bootstrap/cache database

COPY docker/demo/entrypoint.sh /usr/local/bin/padiush-demo
RUN chmod +x /usr/local/bin/padiush-demo

EXPOSE 8000
ENTRYPOINT ["padiush-demo"]

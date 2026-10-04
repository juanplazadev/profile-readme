FROM php:8.5-cli AS base

# hb-subset + woff2 replace Python's fontTools for subsetting the embedded fonts; gmp backs the city PRNG
RUN apt-get update \
 && apt-get install -y --no-install-recommends libharfbuzz-bin woff2 libgmp-dev unzip \
 && docker-php-ext-install gmp \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# hb-subset can't read WOFF2, so decompress the upstream fonts to TTF once, at build time
COPY resources/fonts/*.woff2 /opt/fonts/
RUN cd /opt/fonts && for f in *.woff2; do woff2_decompress "$f"; done && rm *.woff2
ENV FONT_DIR=/opt/fonts \
    PROFILE_TARGET=/target

WORKDIR /app
COPY composer.json ./
COPY src ./src

FROM base AS prod
RUN composer install --no-dev --no-interaction --no-progress
COPY . .
ENTRYPOINT ["php", "bin/profile"]
CMD ["all"]

FROM base AS dev
RUN composer install --no-interaction --no-progress
COPY . .
ENTRYPOINT ["vendor/bin/phpunit"]

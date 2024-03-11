FROM php:8.0-cli-alpine

RUN apk add --update linux-headers \
  && apk --no-cache add pcre ${PHPIZE_DEPS} \ 
  && pecl install xdebug-3.3.1 \
  && docker-php-ext-enable xdebug \
  && apk del pcre ${PHPIZE_DEPS}

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

ENV BOTDIR="/root/discord-php"
ENV BOTSCRIPT="./bot.php"
LABEL version="10"
WORKDIR ${BOTDIR}
COPY . ${BOTDIR}

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install --no-cache --no-interaction

CMD php ${BOTSCRIPT}
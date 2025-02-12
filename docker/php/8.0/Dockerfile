FROM php:8.0-cli-alpine

RUN apk add --update linux-headers \
  && apk --no-cache add pcre ${PHPIZE_DEPS} \
  && pecl install xdebug \
  && docker-php-ext-enable xdebug \
  && apk del pcre ${PHPIZE_DEPS}

# Use the default production configuration
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

RUN wget https://raw.githubusercontent.com/composer/getcomposer.org/f3108f64b4e1c1ce6eb462b159956461592b3e3e/web/installer -O - -q | php -- --quiet
RUN mv /composer.phar /usr/local/bin/composer

ENV BOTDIR="/root/discord-php"
LABEL version="10"
WORKDIR ${BOTDIR}
COPY . ${BOTDIR}

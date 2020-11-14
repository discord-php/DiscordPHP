# Dockerfile for PHP 7.3
FROM php:7.3-cli

RUN apt-get update -y && apt-get upgrade -y
RUN apt-get install git zip unzip -y

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php
RUN chmod +x composer.phar
RUN mv composer.phar /usr/local/bin/composer

COPY entrypoint.sh /entrypoint.sh

ENTRYPOINT [ "/entrypoint.sh" ]

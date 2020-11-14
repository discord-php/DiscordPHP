# Dockerfile for PHP 7.4
FROM php:7.4-cli

RUN curl -L -O https://phpdoc.org/phpDocumentor.phar
RUN chmod +x phpDocumentor.phar
RUN mv phpDocumentor.phar /usr/local/bin/phpdoc

COPY entrypoint.sh /entrypoint.sh

ENTRYPOINT [ "/entrypoint.sh" ]

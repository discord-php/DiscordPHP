#!/bin/sh -l

composer install
./vendor/bin/phpunit

#!/usr/bin/env bash

curl -sS https://getcomposer.org/installer | php -- --filename=composer

# https://wordpress.org/plugins/wp-router/
curl -O https://downloads.wordpress.org/plugin/wp-router.zip && unzip wp-router.zip && mv wp-router ../ && rm wp-router.zip

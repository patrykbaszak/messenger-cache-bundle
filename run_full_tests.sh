#!/bin/bash

sudo chown -R $USER .

docker-compose down

docker-compose up -d

# test latest symfony and php
echo "Testing latest symfony and php";
yes | cp -rf docker/composer/composer.json composer.json
docker-compose exec app composer update -W
docker-compose exec app composer test:ci

# test symfony 6.2 and php 8.2
echo "Testing symfony 6.2 and php 8.2";
yes | cp -rf docker/composer/symfony62.composer.json composer.json
docker-compose exec app82 composer update -W
docker-compose exec app82 composer phpunit

# test symfony 6.2 and php 8.1
echo "Testing symfony 6.2 and php 8.1";
yes | cp -rf docker/composer/symfony62.composer.json composer.json
docker-compose exec app81 composer update -W
docker-compose exec app81 composer phpunit

# test symfony 5.4 and php 8.2
echo "Testing symfony 5.4 and php 8.2";
yes | cp -rf docker/composer/symfony54.composer.json composer.json
docker-compose exec app82 composer update -W
docker-compose exec app82 composer phpunit

# test symfony 5.4 and php 8.1
echo "Testing symfony 5.4 and php 8.1";
yes | cp -rf docker/composer/symfony54.composer.json composer.json
docker-compose exec app81 composer update -W
docker-compose exec app81 composer phpunit

# test symfony 5.4 and php 8.0
echo "Testing symfony 5.4 and php 8.0";
yes | cp -rf docker/composer/symfony54.composer.json composer.json
docker-compose exec app80 composer update -W
docker-compose exec app80 composer phpunit

# return to latest symfony and php
echo "Return state to the latest symfony and php version.";
yes | cp -rf docker/composer/composer.json composer.json
docker-compose exec app composer update -W
docker-compose exec app composer test:ci

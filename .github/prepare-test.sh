#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

cd $__DIR__

containerName=$1

if [[ "4.5.9-php8.0" = $SWOOLE_DOCKER_VERSION ]]; then
    containerName="php8"
fi

docker-compose up -d $containerName \
&& docker exec $containerName php -v \
&& docker exec $containerName php --ri swoole \
&& docker exec $containerName composer -V \
&& docker ps -a \
&& docker exec $containerName composer update

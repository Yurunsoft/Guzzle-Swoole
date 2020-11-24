#!/bin/bash

__DIR__=$(cd `dirname $0`; pwd)

cd $__DIR__

docker-compose up -d $1
&& docker exec $1 php -v
&& docker exec $1 php --ri swoole
&& docker exec $1 composer -V
&& docker ps -a
&& docker exec $1 composer update

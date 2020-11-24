ARG PHP_VERSION

FROM php:${PHP_VERSION}

ARG SWOOLE_VERSION
ENV SWOOLE_VERSION $SWOOLE_VERSION

RUN curl -sSfL -o swoole.tar.gz https://github.com/swoole/swoole-src/archive/$SWOOLE_VERSION.tar.gz && mkdir -p swoole && tar -xf swoole.tar.gz -C swoole --strip-components=1 && cd swoole && phpize && ./configure --enable-openssl --enable-http2 --enable-mysqlnd $macosSwooleMakeArg && make -j && make install && docker-php-ext-enable swoole;

RUN curl -sSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

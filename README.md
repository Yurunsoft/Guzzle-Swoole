# Guzzle-Swoole

[![Latest Version](https://img.shields.io/packagist/v/yurunsoft/guzzle-swoole.svg)](https://packagist.org/packages/yurunsoft/guzzle-swoole)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.0.0-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![IMI License](https://img.shields.io/github/license/Yurunsoft/Guzzle-Swoole.svg)](https://github.com/Yurunsoft/Guzzle-Swoole/blob/master/LICENSE)

## 介绍

让 Guzzle 支持 Swoole 协程，这个项目目的就是这么简单明了！

## 使用说明

Composer:`"yurunsoft/guzzle-swoole":"~1.0"`

```php
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;

go(function(){
    $handler = new SwooleHandler();
    $stack = HandlerStack::create($handler);
    $client = new Client(['handler' => $stack]);
    $response = $client->request('GET', 'http://www.baidu.com', [
        'verify'    =>  false,
    ]);
    var_dump($response->getBody()->__toString(), $response->getHeaders());
});
```

更加详细的示例代码请看`test`目录下代码。
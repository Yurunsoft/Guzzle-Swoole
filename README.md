# Guzzle-Swoole

[![Latest Version](https://img.shields.io/packagist/v/yurunsoft/guzzle-swoole.svg)](https://packagist.org/packages/yurunsoft/guzzle-swoole)
[![Php Version](https://img.shields.io/badge/php-%3E=7.1-brightgreen.svg)](https://secure.php.net/)
[![Swoole Version](https://img.shields.io/badge/swoole-%3E=4.0.0-brightgreen.svg)](https://github.com/swoole/swoole-src)
[![IMI License](https://img.shields.io/github/license/Yurunsoft/Guzzle-Swoole.svg)](https://github.com/Yurunsoft/Guzzle-Swoole/blob/master/LICENSE)

## 介绍

让 Guzzle 支持 Swoole 协程，这个项目目的就是这么简单明了！

Guzzle-Swoole 是 Guzzle 的处理器（Handler），并没有对 Guzzle 本身代码进行修改，理论上可以兼容后续版本。

支持 Ring Handler，可以用于 `elasticsearch/elasticsearch` 等包中。

QQ群：17916227 [![点击加群](https://pub.idqqimg.com/wpa/images/group.png "点击加群")](https://jq.qq.com/?_wv=1027&k=5wXf4Zq)

## 使用说明

Composer:`"yurunsoft/guzzle-swoole":"~2.0"`

### 全局设定处理器

```php
<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use GuzzleHttp\Client;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;
use GuzzleHttp\DefaultHandler;

DefaultHandler::setDefaultHandler(SwooleHandler::class);

go(function(){
    $client = new Client();
    $response = $client->request('GET', 'http://www.baidu.com', [
        'verify'    =>  false,
    ]);
    var_dump($response->getStatusCode());
});

```

### 手动指定 Swoole 处理器

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

### ElasticSearch

```php
$client = \Elasticsearch\ClientBuilder::create()->setHosts(['192.168.0.233:9200'])->setHandler(new \Yurun\Util\Swoole\Guzzle\Ring\SwooleHandler())->build();
```

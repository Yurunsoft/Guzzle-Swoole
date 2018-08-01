<?php
require dirname(__DIR__) . '/vendor/autoload.php';

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
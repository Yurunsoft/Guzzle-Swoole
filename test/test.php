<?php
require dirname(__DIR__) . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
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

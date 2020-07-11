<?php
namespace Yurun\Util\Swoole\Guzzle\Test;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;

class SwooleHandlerTest extends BaseTest
{
    public function testGet()
    {
        $this->go(function(){
            $client = new Client();
            $response = $client->request('GET', 'http://httpbin.org/get?id=1');
            $data = json_decode($response->getBody(), true);
            $this->assertEquals([
                'id'    =>  '1',
            ], $data['args'] ?? null);
        });
    }

    public function testPost()
    {
        $this->go(function(){
            $client = new Client();
            $response = $client->request('POST', 'http://httpbin.org/post?id=1', [
                'body'  =>  'abcdefg',
            ]);
            $data = json_decode($response->getBody(), true);
            $this->assertEquals([
                'id'    =>  '1',
            ], $data['args'] ?? null);
            $this->assertEquals('abcdefg', $data['data'] ?? null);
        });
    }

    public function testHeader()
    {
        $this->go(function(){
            $client = new Client();
            $response = $client->request('GET', 'http://httpbin.org/response-headers?freeform=123');
            $data = json_decode($response->getBody(), true);
            $this->assertEquals('123', $data['freeform'] ?? null);
            $this->assertEquals('application/json', $response->getHeaderLine('content-type'));
        });
    }

    public function testRedirect()
    {
        $this->go(function(){
            $client = new Client();
            $response = $client->request('GET', 'http://127.0.0.1:8899/?a=redirect302&status_code=302', [
                'allow_redirects'   =>  false,
            ]);
            $this->assertEquals(302, $response->getStatusCode());
            $this->assertEquals('/?a=info', $response->getHeaderLine('Location'));

            $response = $client->request('GET', 'http://127.0.0.1:8899/?a=redirect302&status_code=302');
            $data = json_decode($response->getBody(), true);
            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals([
                'a' =>  'info',
            ], $data['get'] ?? null);

            $this->expectException(\GuzzleHttp\Exception\TooManyRedirectsException::class);
            $response = $client->request('GET', 'http://127.0.0.1:8899/?a=redirect&count=3', [
                'allow_redirects'   =>  [
                    'max'   =>  1,
                ],
            ]);
        });
    }

    public function testConnectException()
    {
        $this->go(function(){
            $this->expectException(ConnectException::class);
            $client = new Client();
            $client->request('GET', 'http://127.0.0.256/');
        });
    }

}

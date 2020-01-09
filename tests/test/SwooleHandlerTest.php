<?php
namespace Yurun\Util\Swoole\Guzzle\Test;

use GuzzleHttp\Client;

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

}

<?php
namespace Yurun\Util\Swoole\Guzzle\Test;

use Yurun\Util\Swoole\Guzzle\Ring\SwooleHandler;

class RingSwooleHandlerTest extends BaseTest
{
    public function testGet()
    {
        $this->go(function(){
            $handler = new SwooleHandler;
            $response = $handler([
                'http_method' => 'GET',
                'uri' => '/get?id=1',
                'headers' => [
                    'host'  => ['httpbin.org'],
                ],
            ]);
            $data = json_decode(stream_get_contents($response['body']), true);
            $this->assertEquals(200, $response['transfer_stats']['http_code'] ?? null);
            $this->assertEquals([
                'id'    =>  '1',
            ], $data['args'] ?? null);
        });
    }

    public function testPost()
    {
        $this->go(function(){
            $handler = new SwooleHandler;
            $response = $handler([
                'http_method'   => 'POST',
                'uri'           => '/post?id=1',
                'body'          => 'value=abcdefg',
                'headers' => [
                    'host'  => ['httpbin.org'],
                ],
            ]);
            $data = json_decode(stream_get_contents($response['body']), true);
            $this->assertEquals(200, $response['transfer_stats']['http_code'] ?? null);
            $this->assertEquals([
                'id'    =>  '1',
            ], $data['args'] ?? null);
            $this->assertEquals('abcdefg', $data['form']['value'] ?? null);
        });
    }

    public function testHeader()
    {
        $this->go(function(){
            $handler = new SwooleHandler;
            $response = $handler([
                'http_method' => 'GET',
                'uri' => '/response-headers?freeform=123',
                'headers' => [
                    'host'  => ['httpbin.org'],
                ],
            ]);
            $data = json_decode(stream_get_contents($response['body']), true);
            $this->assertEquals(200, $response['transfer_stats']['http_code'] ?? null);
            $this->assertEquals('123', $data['freeform'] ?? null);
            $this->assertEquals('application/json', $data['Content-Type'] ?? null);
        });
    }

}

<?php
namespace Yurun\Util\Swoole\Guzzle\Test;

use GuzzleHttp\DefaultHandler;
use Yurun\Util\Swoole\Guzzle\SwooleHandler;

class DefaultHandlerHttpTest extends BaseTest
{
    public function testDefaultHandler()
    {
        $this->assertNull(DefaultHandler::getDefaultHandler());
        DefaultHandler::setDefaultHandler(SwooleHandler::class);
        $this->assertEquals(SwooleHandler::class, DefaultHandler::getDefaultHandler());
    }

}

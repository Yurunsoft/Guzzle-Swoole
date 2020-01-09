<?php
namespace Yurun\Util\Swoole\Guzzle\Test;

use PHPUnit\Framework\TestCase;
use Swoole\Coroutine;

abstract class BaseTest extends TestCase
{
    protected function go($callable, $finally = null)
    {
        $throwable = null;
        $cid = go(function() use($callable, &$throwable){
            try {
                $callable();
            } catch(\Throwable $th) {
                $throwable = $th;
            }
        });
        while(Coroutine::exists($cid))
        {
            usleep(10000);
        }
        if($finally)
        {
            $finally();
        }
        if($throwable)
        {
            throw $throwable;
        }
    }
}

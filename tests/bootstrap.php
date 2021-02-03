<?php

if(defined('SWOOLE_HOOK_ALL'))
{
    $flags = SWOOLE_HOOK_ALL;
    if(defined('SWOOLE_HOOK_NATIVE_CURL'))
    {
        $flags ^= SWOOLE_HOOK_NATIVE_CURL;
    }
}
else
{
    $flags = true;
}
\Swoole\Runtime::enableCoroutine($flags);

function testEnv($name, $default = null)
{
    $result = getenv($name);
    if (false === $result)
    {
        return $default;
    }

    return $result;
}

// Http Server
$cmd = __DIR__ . '/server/Http/start-server.sh';
echo 'Starting Http server...', \PHP_EOL;
echo `{$cmd}`, \PHP_EOL;
$serverStarted = false;
for ($i = 0; $i < 10; ++$i)
{
    if ('YurunHttp' === @file_get_contents(testEnv('HTTP_SERVER_HOST', 'http://127.0.0.1:8899/')))
    {
        $serverStarted = true;
        break;
    }
    sleep(1);
}
if ($serverStarted)
{
    echo 'Http server started!', \PHP_EOL;
}
else
{
    throw new \RuntimeException('Http server start failed');
}

register_shutdown_function(function () {
    // stop server
    $cmd = __DIR__ . '/server/Http/stop-server.sh';
    echo 'Stoping http server...', \PHP_EOL;
    echo `{$cmd}`, \PHP_EOL;
    echo 'Http Server stoped!', \PHP_EOL;
});

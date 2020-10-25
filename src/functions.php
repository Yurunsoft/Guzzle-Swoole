<?php

namespace Yurun\Util\Swoole\Guzzle
{
    use GuzzleHttp\DefaultHandler;
    use GuzzleHttp\Handler\CurlHandler;
    use GuzzleHttp\Handler\CurlMultiHandler;
    use GuzzleHttp\Handler\Proxy;
    use GuzzleHttp\Handler\StreamHandler;

    /**
     * Chooses and creates a default handler to use based on the environment.
     *
     * The returned handler is not wrapped by any default middlewares.
     *
     * @throws \RuntimeException if no viable Handler is available
     *
     * @return callable returns the best handler for the given system
     */
    function choose_handler(): callable
    {
        $handler = null;
        $defaultHandler = DefaultHandler::getDefaultHandler();
        if (\is_string($defaultHandler))
        {
            $handler = new $defaultHandler();
        }
        elseif (\is_callable($defaultHandler))
        {
            $handler = $defaultHandler();
        }
        elseif (\function_exists('curl_multi_exec') && \function_exists('curl_exec'))
        {
            $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
        }
        elseif (\function_exists('curl_exec'))
        {
            $handler = new CurlHandler();
        }
        elseif (\function_exists('curl_multi_exec'))
        {
            $handler = new CurlMultiHandler();
        }

        if (ini_get('allow_url_fopen'))
        {
            $handler = $handler
                ? Proxy::wrapStreaming($handler, new StreamHandler())
                : new StreamHandler();
        }
        elseif (!$handler)
        {
            throw new \RuntimeException('GuzzleHttp requires cURL, the ' . 'allow_url_fopen ini setting, or a custom HTTP handler.');
        }

        return $handler;
    }
}

namespace GuzzleHttp
{
    if (!\function_exists('GuzzleHttp\choose_handler') && !method_exists('GuzzleHttp\Utils', 'chooseHandler'))
    {
        /**
         * Chooses and creates a default handler to use based on the environment.
         *
         * The returned handler is not wrapped by any default middlewares.
         *
         * @throws \RuntimeException if no viable Handler is available
         *
         * @return callable returns the best handler for the given system
         */
        function choose_handler(): callable
        {
            return \Yurun\Util\Swoole\Guzzle\choose_handler();
        }
    }
}

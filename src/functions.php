<?php
namespace GuzzleHttp;

use GuzzleHttp\Handler\Proxy;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Handler\StreamHandler;
use GuzzleHttp\Handler\CurlMultiHandler;

if(!function_exists('GuzzleHttp\choose_handler'))
{
    /**
     * Chooses and creates a default handler to use based on the environment.
     *
     * The returned handler is not wrapped by any default middlewares.
     *
     * @throws \RuntimeException if no viable Handler is available.
     * @return callable Returns the best handler for the given system.
     */
    function choose_handler()
    {
        $handler = null;
        $defaultHandler = DefaultHandler::getDefaultHandler();
        if(is_string($defaultHandler)) {
            $handler = new $defaultHandler;
        } elseif (is_callable($defaultHandler)) {
            $handler = $defaultHandler();
        } elseif (function_exists('curl_multi_exec') && function_exists('curl_exec')) {
            $handler = Proxy::wrapSync(new CurlMultiHandler(), new CurlHandler());
        } elseif (function_exists('curl_exec')) {
            $handler = new CurlHandler();
        } elseif (function_exists('curl_multi_exec')) {
            $handler = new CurlMultiHandler();
        }

        if (ini_get('allow_url_fopen')) {
            $handler = $handler
                ? Proxy::wrapStreaming($handler, new StreamHandler())
                : new StreamHandler();
        } elseif (!$handler) {
            throw new \RuntimeException('GuzzleHttp requires cURL, the '
                . 'allow_url_fopen ini setting, or a custom HTTP handler.');
        }

        return $handler;
    }
}

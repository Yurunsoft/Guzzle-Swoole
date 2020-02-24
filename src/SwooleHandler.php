<?php
namespace Yurun\Util\Swoole\Guzzle;

use Yurun\Util\YurunHttp;
use Yurun\Util\YurunHttp\Attributes;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\RejectedPromise;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\Constraint\Attribute;

class SwooleHandler
{
    /**
     * Sends an HTTP request.
     *
     * @param RequestInterface $request Request to send.
     * @param array            $options Request transfer options.
     *
     * @return PromiseInterface
     */
    public function __invoke(RequestInterface $request, array $options)
    {
        $yurunRequest = new \Yurun\Util\YurunHttp\Http\Request($request->getUri(), $request->getHeaders(), (string)$request->getBody(), $request->getMethod(), $request->getProtocolVersion());
        $yurunRequest = $yurunRequest->withoutHeader('Content-Length')
                                     // 是否验证 CA
                                     ->withAttribute(Attributes::IS_VERIFY_CA, $options['verify'])
                                     // 禁止重定向
                                     ->withAttribute(Attributes::FOLLOW_LOCATION, false)
                                     // 超时
                                     ->withAttribute(Attributes::TIMEOUT, ($options['timeout'] ?? -1) * 1000)
                                     // 连接超时
                                     ->withAttribute(Attributes::CONNECT_TIMEOUT, ($options['connect_timeout'] ?? -1) * 1000);
        // 用户名密码认证处理
        $auth = isset($options['auth']) ? $options['auth'] : [];
        if(isset($auth[1]))
        {
            list($username, $password) = $auth;
            $auth = base64_encode($username . ':' . $password);
            $yurunRequest = $yurunRequest->withAddedHeader('Authorization', 'Basic ' . $auth);
        }
        if(!$yurunRequest->hasHeader('Content-Type'))
        {
            $yurunRequest = $yurunRequest->withHeader('Content-Type', '');
        }
        // 证书
        $cert = isset($options['cert']) ? (array)$options['cert'] : [];
        if(isset($cert[0]))
        {
            $yurunRequest = $yurunRequest->withAttribute(Attributes::CERT_PATH, $cert[0]);
        }
        if(isset($cert[1]))
        {
            $yurunRequest = $yurunRequest->withAttribute(Attributes::CERT_PASSWORD, $cert[1]);
        }
        // ssl_key
        $key = isset($options['ssl_key']) ? (array)$options['ssl_key'] : [];
        if(isset($key[0]))
        {
            $yurunRequest = $yurunRequest->withAttribute(Attributes::KEY_PATH, $key[0]);
        }
        if(isset($key[1]))
        {
            $yurunRequest = $yurunRequest->withAttribute(Attributes::KEY_PASSWORD, $key[1]);
        }
        // 代理
        $proxy = isset($options['proxy']) ? $options['proxy'] : [];
        if(is_string($proxy))
        {
            $proxy = [
                'http' => $proxy,
            ];
        }
        if(!(isset($proxy['no']) && \GuzzleHttp\is_host_in_noproxy($request->getUri()->getHost(), $proxy['no'])))
        {
            $scheme = $request->getUri()->getScheme();
            $proxyUri = isset($proxy[$scheme]) ? $proxy[$scheme] : null;
            if(null !== $proxyUri)
            {
                $proxyUri = new Uri($proxyUri);
                $userinfo = explode(':', $proxyUri->getUserInfo());
                if(isset($userinfo[1]))
                {
                    list($username, $password) = $userinfo;
                }
                else
                {
                    $username = $userinfo[0];
                    $password = '';
                }
                $yurunRequest = $yurunRequest->withAttribute(Attributes::PROXY_SERVER, $proxyUri->getHost())
                                             ->withAttribute(Attributes::PROXY_PORT, $proxyUri->getPort())
                                             ->withAttribute(Attributes::PROXY_USERNAME, $username)
                                             ->withAttribute(Attributes::PROXY_PASSWORD, $password);
            }
        }
        // 发送请求
        $yurunResponse = YurunHttp::send($yurunRequest, \Yurun\Util\YurunHttp\Handler\Swoole::class);
        if(($statusCode = $yurunResponse->getStatusCode()) < 0)
        {
            switch($statusCode)
            {
                case -1:
                    return new RejectedPromise(new ConnectException(sprintf('Connect failed: errorCode: %s, errorMessage: %s', $yurunResponse->errno(), $yurunResponse->error()), $request));
                case -2:
                    $message = 'Request timeout';
                    break;
                case -3:
                    $message = 'Server reset';
                    break;
                default:
                    $message = 'Unknown';
            }
            return new RejectedPromise(new ConnectException($message, $request));
        }
        else
        {
            $response = $this->getResponse($yurunResponse);
            return new FulfilledPromise($response);
        }
    }

    /**
     * 获取 Guzzle Response
     *
     * @param \Yurun\Util\YurunHttp\Http\Response $yurunResponse
     * @return \GuzzleHttp\Psr7\Response
     */
    private function getResponse($yurunResponse)
    {
        $headers = [];
        foreach($yurunResponse->getHeaders() as $name => $str)
        {
            $headers[$name] = implode(', ', $str);
        }
        $response = new \GuzzleHttp\Psr7\Response($yurunResponse->getStatusCode(), $headers, $yurunResponse->getBody());
        return $response;
    }

}
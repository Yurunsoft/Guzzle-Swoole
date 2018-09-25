<?php
namespace Yurun\Util\Swoole\Guzzle;

use Swoole\Coroutine;
use GuzzleHttp\RequestOptions;
use Swoole\Coroutine\Http\Client;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Psr7\Uri;

class SwooleHandler
{
    /**
     * Swoole 协程 Http 客户端
     *
     * @var \Swoole\Coroutine\Http\Client
     */
    private $client;

    /**
     * 配置选项
     *
     * @var array
     */
    private $settings = [];

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
        $uri = $request->getUri();
        $isLocation = false;
        $count = 0;
        do{
            $port = $uri->getPort();
            if(null === $port)
            {
                if('https' === $uri->getScheme())
                {
                    $port = 443;
                }
                else
                {
                    $port = 80;
                }
            }

            $this->client = new Client($uri->getHost(), $port, 'https' === $uri->getScheme());

            // method
            if($isLocation)
            {
                $this->client->setMethod('GET');
            }
            else
            {
                $this->client->setMethod($request->getMethod());
            }

            // body
            if(!$isLocation)
            {
                $this->client->setData((string)$request->getBody());
            }

            // headers
            $headers = [];
            foreach($request->getHeaders() as $name => $value)
            {
                $headers[$name] = implode(',', $value);
            }
            // 带有 Content-Length 时，少数奇葩服务器会无法顺利接收 post 数据
            if(isset($headers['Content-Length']))
            {
                unset($headers['Content-Length']);
            }
            $this->client->setHeaders($headers);

            // 其它处理
            $this->parseSSL($request, $options);
            $this->parseProxy($request, $options);
            $this->parseNetwork($request, $options);

            // 设置客户端参数
            if(!empty($this->settings))
            {
                $this->client->set($this->settings);
            }

            // 发送
            $path = $uri->getPath();
            if('' === $path)
            {
                $path = '/';
            }
            $query = $uri->getQuery();
            if('' !== $query)
            {
                $path .= '?' . $query;
            }

            $this->client->execute($path);

            $response = $this->getResponse();

            $statusCode = $response->getStatusCode();

            if((301 === $statusCode || 302 === $statusCode) && $options[RequestOptions::ALLOW_REDIRECTS] && ++$count <= $options[RequestOptions::ALLOW_REDIRECTS]['max'])
            {
                // 自己实现重定向
                $uri = new Uri($response->getHeaderLine('location'));
                $isLocation = true;
            }
            else
            {
                break;
            }

        } while(true);
        return new FulfilledPromise($response);
    }

    private function parseSSL(RequestInterface $request, array $options)
    {
        if(($verify = $options['verify']))
        {
            $this->settings['ssl_verify_peer'] = true;
            if(is_string($verify))
            {
                $this->settings['ssl_cafile'] = $verify;
            }
        }
        else
        {
            $this->settings['ssl_verify_peer'] = false;
        }

        $cert = isset($options['cert']) ? $options['cert'] : [];
        if(isset($cert[0]))
        {
            $this->settings['ssl_cert_file'] = $cert[0];
        }
        else if(isset($this->settings['ssl_cert_file']))
        {
            unset($this->settings['ssl_cert_file']);
        }

        $key = isset($options['key']) ? $options['key'] : [];
        if(isset($key[0]))
        {
            $this->settings['ssl_key_file'] = $key[0];
        }
        else if(isset($this->settings['ssl_key_file']))
        {
            unset($this->settings['ssl_key_file']);
        }
    }

    private function parseProxy(RequestInterface $request, array $options)
    {
        $proxy = isset($options['proxy']) ? $options['proxy'] : [];
        if(isset($proxy['no']) && \GuzzleHttp\is_host_in_noproxy($request->getUri()->getHost(), $proxy['no']))
        {
            if(isset($this->settings['http_proxy_host']))
            {
                unset($this->settings['http_proxy_host'], $this->settings['http_proxy_port'], $this->settings['http_proxy_user'], $this->settings['http_proxy_password']);
            }
            return;
        }
        $scheme = $request->getUri()->getScheme();
        $proxyUri = isset($proxy[$scheme]) ? $proxy[$scheme] : null;
        if(null === $proxyUri)
        {
            if(isset($this->settings['http_proxy_host']))
            {
                unset($this->settings['http_proxy_host'], $this->settings['http_proxy_port'], $this->settings['http_proxy_user'], $this->settings['http_proxy_password']);
            }
            return;
        }
        $proxyUri = new Uri($proxyUri);
        $userinfo = explode(':', $proxyUri->getUserInfo());
        if(isset($userinfo[1]))
        {
            list($username, $password) = $userinfo;
        }
        else
        {
            $username = $userinfo[0];
            $password = null;
        }
        $this->settings['http_proxy_host'] = $proxyUri->getHost();
        $this->settings['http_proxy_port'] = $proxyUri->getPort();
        $this->settings['http_proxy_user'] = $username;
        $this->settings['http_proxy_password'] = $password;
    }

    private function parseNetwork(RequestInterface &$request, array $options)
    {
        // 用户名密码认证处理
        $auth = isset($options['auth']) ? $options['auth'] : [];
        if(isset($auth[1]))
        {
            list($username, $password) = $auth;
            $auth = base64_encode($username . ':' . $password);
            $request = $request->withAddedHeader('Authorization', 'Basic ' . $auth);
        }
        // 超时
        if(isset($options['timeout']) && $options['timeout'] > 0)
        {
            $this->settings['timeout'] = $options['timeout'];
        }
        else if(isset($this->settings['timeout']))
        {
            $this->settings['timeout'] = -1;
        }
    }

    private function getResponse()
    {
        $headers = isset($this->client->headers) ? $this->client->headers : [];
        if(isset($headers['set-cookie']))
        {
            $headers['set-cookie'] = $this->client->set_cookie_headers;
        }
        $response = new \GuzzleHttp\Psr7\Response($this->client->statusCode, $headers, $this->client->body);
        return $response;
    }
}
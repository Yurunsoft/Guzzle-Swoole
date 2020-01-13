<?php
namespace Yurun\Util\Swoole\Guzzle\Ring;

use Swoole\Coroutine;
use GuzzleHttp\Ring\Core;
use Yurun\Util\YurunHttp;
use Yurun\Util\HttpRequest;
use Yurun\Util\YurunHttp\Http\Psr7\Uri;
use Yurun\Util\YurunHttp\Http\Response;
use GuzzleHttp\Ring\Exception\RingException;
use GuzzleHttp\Ring\Future\CompletedFutureArray;

class SwooleHandler
{
    /**
     * 选项集合
     *
     * @var array
     */
    protected $options;

    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * @param array $request
     *
     * @return CompletedFutureArray
     */
    public function __invoke(array $request)
    {
        $httpRequest = new HttpRequest;
        $yurunResponse = $this->getYurunResponse($httpRequest, $request);
        $response = $this->getResponse($httpRequest, $yurunResponse);
        return new CompletedFutureArray($response);
    }

    /**
     * 获取 YurunHttp Response
     *
     * @param \Yurun\Util\HttpRequest $httpRequest
     * @param array $request
     * @return \Yurun\Util\YurunHttp\Http\Response
     */
    protected function getYurunResponse(HttpRequest $httpRequest, array $request)
    {
        foreach ($request['client'] ?? [] as $key => $value)
        {
            switch ($key)
            {
                // Violating PSR-4 to provide more room.
                case 'verify':
                    if($httpRequest->isVerifyCA = $value && is_string($value))
                    {
                        if (!file_exists($value))
                        {
                            throw new \InvalidArgumentException(
                                "SSL CA bundle not found: $value"
                            );
                        }
                        $httpRequest->caCert = $value;
                    }
                    break;
                case 'decode_content':
                    break;
                case 'save_to':
                    break;
                case 'timeout':
                    $httpRequest->timeout = $value * 1000;
                    break;
                case 'connect_timeout':
                    $httpRequest->connectTimeout = $value * 1000;
                    break;
                case 'proxy':
                    if (!is_array($value))
                    {
                        $uri = new Uri($value);
                    }
                    else if(isset($request['scheme']))
                    {
                        $scheme = $request['scheme'];
                        if (isset($value[$scheme]))
                        {
                            $uri = new Uri($value[$scheme]);
                        }
                        else
                        {
                            break;
                        }
                    }
                    $httpRequest->proxy($uri->getHost(), $uri->getPort(), $uri->getScheme());
                    break;

                case 'cert':
                    if (is_array($value))
                    {
                        $httpRequest->certPassword = $value[1];
                        $value = $value[0];
                    }
                    if (!file_exists($value))
                    {
                        throw new \InvalidArgumentException(
                            "SSL certificate not found: {$value}"
                        );
                    }
                    $httpRequest->certType = $value;
                    break;
                case 'ssl_key':
                    if (is_array($value)) {
                        $httpRequest->keyPassword = $value[1];
                        // $options[CURLOPT_SSLKEYPASSWD] = $value[1];
                        $value = $value[0];
                    }
    
                    if (!file_exists($value)) {
                        throw new \InvalidArgumentException(
                            "SSL private key not found: {$value}"
                        );
                    }
                    $httpRequest->keyPath = $value;
                    break;
                case 'progress':
                    break;
                case 'debug':
                    break;
            }
        }
        // headers
        foreach($request['headers'] as $name => $list)
        {
            $httpRequest->header($name, implode(', ', $list));
        }
        if(isset($httpRequest->headers['Content-Length']))
        {
            unset($httpRequest->headers['Content-Length']);
        }
        // request
        $method = $request['http_method'] ?? 'GET';
        $url = Core::url($request);
        if(isset($request['client']['curl'][CURLOPT_PORT]))
        {
            $uri = new Uri($url);
            $uri = $uri->withPort($request['client']['curl'][CURLOPT_PORT]);
            $url = (string)$uri;
        }
        $body = Core::body($request);
        $httpRequest->url = $url;
        $httpRequest->requestBody((string)$body);
        $yurunRequest = $httpRequest->buildRequest(null, null, $method);
        return YurunHttp::send($yurunRequest, Coroutine::getuid() > -1 ? \Yurun\Util\YurunHttp\Handler\Swoole::class : \Yurun\Util\YurunHttp\Handler\Curl::class);
    }

    /**
     * 获取响应数组
     *
     * @param \Yurun\Util\HttpRequest $httpRequest
     * @param \Yurun\Util\YurunHttp\Http\Response $yurunResponse
     * @return array
     */
    protected function getResponse(HttpRequest $httpRequest, Response $yurunResponse)
    {
        $uri = new Uri($httpRequest->url);
        $transferStatus = [
            'url'                       =>  $httpRequest->url,
            'content_type'              =>  $yurunResponse->getHeaderLine('content-type'),
            'http_code'                 =>  $yurunResponse->getStatusCode(),
            'header_size'               =>  0,
            'request_size'              =>  0,
            'filetime'                  =>  0,
            'ssl_verify_result'         =>  true,
            'redirect_count'            =>  0,
            'total_time'                =>  $yurunResponse->totalTime(),
            'namelookup_time'           =>  0,
            'connect_time'              =>  0,
            'pretransfer_time'          =>  0,
            'size_upload'               =>  0,
            'size_download'             =>  0,
            'speed_download'            =>  0,
            'speed_upload'              =>  0,
            'download_content_length'   =>  0,
            'upload_content_length'     =>  0,
            'starttransfer_time'        =>  0,
            'redirect_time'             =>  0,
            'certinfo'                  =>  '',
            'primary_ip'                =>  $uri->getHost(),
            'primary_port'              =>  Uri::getServerPort($uri),
            'local_ip'                  =>  '127.0.0.1',
            'local_port'                =>  12345,
        ];
        if(!$yurunResponse->success)
        {
            $error = new RingException($yurunResponse->getError());
        }
        $version = $yurunResponse->getProtocolVersion();
        $status = $yurunResponse->getStatusCode();
        $reason = $yurunResponse->getReasonPhrase();
        $body = fopen('php://temp', 'r+');
        fwrite($body, (string)$yurunResponse->getBody());
        fseek($body, 0);
        $response = [
            'curl' => [
                'errno' => 0,
                'error' => '',
            ],
            'transfer_stats'    =>  $transferStatus,
            'effective_url'     =>  $transferStatus['url'],
            'headers'           =>  $yurunResponse->getHeaders(),
            'version'           =>  $version,
            'status'            =>  $status,
            'reason'            =>  $reason,
            'body'              =>  $body,
        ];
        if(isset($error))
        {
            $response['error'] = $error;
        }
        return $response;
    }

}

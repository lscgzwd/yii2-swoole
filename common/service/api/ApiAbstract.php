<?php
/**
 * Created by PhpStorm.
 * User: lusc
 * Date: 2016/5/17
 * Time: 16:21
 */

namespace common\service\api;

use apps\lib\Trace;
use yii\base\Exception;
use yii\httpclient\Client;

/**
 * 请求第三方接口抽象类
 * Class ApiAbstract
 * @package common\service\api
 */
class ApiAbstract
{
    public $key; // api签名密钥
    public $baseUrl; // api base url
    public $dataType    = 'json'; //  json, xml，返回数据格式
    public $contentType = 'urlencoded'; // 请求类容的格式 urlencoded json xml
    public $debug       = false;
    protected $config   = [];
    /**
     * curl options
     */
    public $curlOptions = [];

    protected $client;

    /**
     * BaseApi constructor.
     *
     * @param array $config
     */
    public function __construct($config)
    {
        $this->key         = isset($config['key']) ? $config['key'] : '';
        $this->baseUrl     = isset($config['baseUrl']) ? $config['baseUrl'] : '';
        $this->dataType    = isset($config['dataType']) ? $config['dataType'] : 'json';
        $this->contentType = isset($config['contentType']) ? $config['contentType'] : 'urlencoded';
        $this->debug       = isset($config['debug']) ? $config['debug'] : '';
        $this->curlOptions = isset($config['options']) ? $config['options'] : [];
        $this->config      = $config;
        if (empty($this->key)) {
            throw new \Exception('Key is empty.');
        }
        if (empty($this->baseUrl)) {
            throw new \Exception('baseUrl is empty.');
        }
        $this->client = new Client([
            'baseUrl'        => $this->baseUrl,
            'requestConfig'  => [
                'format' => $this->contentType,
            ],
            'responseConfig' => [
                'format' => $this->dataType,
            ],
        ]);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getSign(array &$data)
    {
        if (!isset($data['ts'])) {
            $data['ts'] = time();
        }
        ksort($data);
        $sign         = md5(join('|', $data) . '|' . $this->key);
        $data['sign'] = $sign;
    }

    /**
     * @param       $uri
     * @param array $data
     */
    protected function get($uri, array $data)
    {
        return $this->call($uri, $data, 'get');
    }

    /**
     * @param       $uri
     * @param array $data
     *
     * @return bool|mixed
     */
    protected function post($uri, array $data)
    {
        return $this->call($uri, $data, 'post');
    }

    protected function parseResponse($res)
    {
        return $res;
    }

    /**
     * @param        $uri
     * @param array  $param
     * @param string $method
     * @param array  $cookie
     * @param array  $header
     *
     * @return bool|mixed
     */
    protected function call($uri, $param = [], $method = 'post', $cookie = [], $header = [])
    {
        $this->getSign($param);
        try {
            Trace::addLog('common_service_api_request_begin', 'info', [
                'params'  => $param,
                'method'  => $method,
                'cookie'  => $cookie,
                'header'  => $header,
                'uri'     => $uri,
                'baseUrl' => $this->baseUrl,
            ]);
            $request = $this->client->createRequest()->setOptions($this->curlOptions)->setMethod($method)->setUrl($uri)->setData($param);
            if (!empty($cookie)) {
                $this->client->addCookies($cookie);
            }
            if (!empty($header)) {
                $this->client->addHeaders($header);
            }
            $response = $this->client->send($request);
            Trace::addLog('common_service_api_request_end', 'info', [
                'params'   => $param,
                'method'   => $method,
                'cookie'   => $cookie,
                'header'   => $header,
                'uri'      => $uri,
                'baseUrl'  => $this->baseUrl,
                'response' => $response->data,
            ]);
            if ($response->isOk && !empty($response->data)) {
                return $this->parseResponse($response->data);
            } else {
                return false;
            }
        } catch (\Exception $e) {
            Trace::addLog('common_service_api_request_exception', 'error', [
                'params'    => $param,
                'method'    => $method,
                'cookie'    => $cookie,
                'header'    => $header,
                'uri'       => $uri,
                'baseUrl'   => $this->baseUrl,
                'exception' => $e->__toString(),
            ]);
        }

        return false;
    }
}

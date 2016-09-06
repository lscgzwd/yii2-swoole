<?php
/**
 * Api request base class
 * User: lusc
 * Date: 2016/5/17
 * Time: 16:21
 */

namespace common\service\api;

use common\constants\ModelConst;
use common\helpers\Trace;
use yii\base\Exception;
use yii\httpclient\Client;

/**
 * Api request base
 * Class ApiAbstract
 * @package common\service\api
 */
class ApiAbstract
{
    public $key; // api sign key
    public $baseUrl; // api base url
    public $dataType          = 'json'; //  json, xml，Content-Type
    public $contentType       = 'urlencoded'; // request data type urlencoded json xml
    public $debug             = false;
    protected $config         = [];
    protected $noNeedSignKeys = ['datas'];
    protected $apiModuel;
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
            'transport'      => 'yii\httpclient\CurlTransport',
        ]);
    }

    /**
     * @param array $data
     *
     * @return string
     */
    protected function getSign(array &$data)
    {
        foreach ($data as $key => $item) {
            if (!in_array($key, $this->noNeedSignKeys)) {
                $tmp[$key] = $item;
            }
        }

        if (!isset($data['ts'])) {
            $tmp['ts'] = time();
        }
        ksort($tmp);
        $sign         = md5(join('|', $tmp) . '|' . $this->key);
        $data['ts']   = $tmp['ts'];
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
        if (isset($res['error']['returnCode']) && is_numeric($res['error']['returnCode']) && $res['error']['returnCode'] == 0) {
            return ['errno' => 200, 'msg' => 'ok', 'data' => isset($res['data']) ? $res['data'] : [], 'originCode' => 0];
        } else {
            $code = isset($res['error']['returnCode']) ? $res['error']['returnCode'] : 500;
            $msg  = isset($res['error']['returnUserMessage']) ? $res['error']['returnUserMessage'] : '数据请求错误';
            return ['errno' => $code, 'msg' => $msg, 'data' => isset($res['data']) ? $res['data'] : [], 'originCode' => $res['error']['returnCode']];
        }
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
            $timeStart   = microtime(true) * 1000;
            $tmpReqParam = $param;
            if (get_class($this) == 'common\service\api\PaymentNasApiService') {
                unset($tmpReqParam['datas']);
            }
            $requestLog = [
                'params'  => $tmpReqParam,
                'method'  => $method,
                'cookie'  => $cookie,
                'header'  => $header,
                'uri'     => $uri,
                'class'   => get_class($this),
                'baseUrl' => $this->baseUrl,
            ];
            Trace::addLog('common_service_api_request_begin', 'info', $requestLog);
            $request = $this->client->createRequest()->setOptions($this->curlOptions)->setMethod($method)->setUrl($uri)->setData($param);
            if (!empty($cookie)) {
                $this->client->addCookies($cookie);
            }
            if (!empty($header)) {
                $this->client->addHeaders($header);
            }
            $response = $this->client->send($request);
            $resData  = $response->getData();

            $tmpRes = $resData;
            if (get_class($this) == 'common\service\api\PaymentNasApiService') {
                unset($tmpRes['datas']);
            }
            $responseLog = [
                'params'   => $tmpReqParam,
                'method'   => $method,
                'cookie'   => $cookie,
                'header'   => $header,
                'uri'      => $uri,
                'class'    => get_class($this),
                'baseUrl'  => $this->baseUrl,
                'response' => $tmpRes,
                'timecost' => microtime(true) * 1000 - $timeStart,
            ];
            Trace::addLog('common_service_api_request_end', 'info', $responseLog);
            unset($tmpReqParam);

            if ($response->isOk && !empty($resData)) {
                return $this->parseResponse($resData);
            } else {
                return ModelConst::getResult(ModelConst::G_API_ERROR);
            }
        } catch (\Exception $e) {
            Trace::addLog('common_service_api_request_exception', 'error', [
                'params'    => $param,
                'method'    => $method,
                'cookie'    => $cookie,
                'header'    => $header,
                'class'     => get_class($this),
                'uri'       => $uri,
                'baseUrl'   => $this->baseUrl,
                'rawReturn' => isset($response) ? $response->getContent() : '',
                'exception' => $e->__toString(),
            ]);
        }
        return ModelConst::getResult(ModelConst::G_API_ERROR);
    }
}

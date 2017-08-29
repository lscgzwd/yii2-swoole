<?php
/**
 * briabear
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/1
 * Time: 18:17
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */
/**
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yiiswoole\web;

use BriarBear\Response\HttpResponse;
use BriarBear\Response\TcpResponse;
use BriarBear\Server;
use Yii;
use yii\base\InvalidConfigException;

class Response extends \yii\web\Response
{
    public $requestType = null;
    /**
     * @var HttpResponse|TcpResponse
     */
    public $response = null;

    /**
     * @return string
     */
    public function getRequestType()
    {
        return $this->requestType;
    }

    /**
     * Server::REQUEST_TYPE_HTTP
     * Server::REQUEST_TYPE_TCP
     * Server::REQUEST_TYPE_WEBSOCKET
     * @param string $requestType
     */
    public function setRequestType($requestType)
    {
        $this->requestType = $requestType;
    }
    /**
     * Sends the response to the client.
     * @return mixed
     *
     */
    public function send()
    {
        if ($this->isSent) {
            return $this->response;
        }

        $this->trigger(self::EVENT_BEFORE_SEND);

        switch ($this->requestType) {
            case Server::REQUEST_TYPE_HTTP:
                $this->prepare();
                $this->response = \BriarBear\Response\Response::getInstance('HTTP');
                $this->trigger(self::EVENT_AFTER_PREPARE);
                $this->sendHeaders();
                break;
            case Server::REQUEST_TYPE_TCP:
                $this->response = \BriarBear\Response\Response::getInstance('TCP');
                $this->content  = $this->data;
                break;
            case Server::REQUEST_TYPE_WEBSOCKET:
                //TODO not finish for websocket
                $this->response = new HttpResponse();
                break;
        }
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
        return $this->response;
    }

    /**
     * Sends the response headers to the client
     */
    protected function sendHeaders()
    {
        $headers = $this->getHeaders();
        foreach ($headers as $name => $values) {
            $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
            foreach ($values as $value) {
                $this->response->setHeader($name, $value);
            }
        }

        $statusCode = $this->getStatusCode();
        $this->response->setHttpStatus($statusCode);
        $this->sendCookies();
    }

    /**
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        $cookies = $this->getCookies();
        if (count($cookies) === 0) {
            return;
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        foreach ($cookies as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey)) {
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            $this->response->setCookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
     * Sends the response content to the client
     */
    protected function sendContent()
    {
        if ($this->xSendFilePath) {
            $this->response->sendFile($this->xSendFilePath);
            return;
        }
        if ($this->stream === null) {
            $this->response->setBody($this->content);

            return;
        }

        set_time_limit(0); // Reset time limit for big files
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk
        $this->response->setBody('');
        if (is_array($this->stream)) {
            list($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                $this->response->addBody(fread($handle, $chunkSize));
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                $this->response->setBody(fread($this->stream, $chunkSize));
            }
            fclose($this->stream);
        }
    }
    /**
     * Disable Http Range
     * Determines the HTTP range given in the request.
     * @param int $fileSize the size of the file that will be used to validate the requested HTTP range.
     * @return array|bool the range (begin, end), or false if the range request is invalid.
     */
    protected function getHttpRange($fileSize)
    {
        return [0, $fileSize - 1];

    }
    protected $xSendFilePath;
    public function xSendFile($filePath, $attachmentName = null, $options = [])
    {
        parent::xSendFile($filePath, $attachmentName, $options); // TODO: Change the autogenerated stub
        $this->xSendFilePath = $filePath;
        return $this;
    }
}

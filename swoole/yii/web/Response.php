<?php
/**
 * Yii 运行在swoole下的输出类
 * User: lusc
 * Date: 15-6-12
 * Time: 下午3:11
 */

namespace swoole\yii\web;

use yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\web\HeaderCollection;
use yii\web\ResponseFormatterInterface;

class Response extends yii\web\Response
{
    /**
     * @var \Swoole\Http\Response response
     *
     */
    public $swoole;

    /**
     * @var integer the HTTP status code to send with the response.
     */
    private $_statusCode = 200;
    /**
     * @var HeaderCollection
     */
    private $_headers;
    private $_cookies;

    /**
     * Initializes this component.
     */
    public function init()
    {
        if ($this->version === null) {
            if (isset($_SERVER['SERVER_PROTOCOL']) && $_SERVER['SERVER_PROTOCOL'] === 'HTTP/1.0') {
                $this->version = '1.0';
            } else {
                $this->version = '1.1';
            }
        }
        if ($this->charset === null) {
            $this->charset = \Yii::$app->charset;
        }
        $this->formatters = array_merge($this->defaultFormatters(), $this->formatters);
    }

    /**
     * 设置swoole输出类
     * @param \Swoole\Http\Response $response
     */
    public function setSwooleResponse(\Swoole\Http\Response $response)
    {
        $this->swoole = $response;
    }

    /**
     * 获取swoole输出类
     * @return \Swoole\Http\Response
     */
    public function getSwooleResponse()
    {
        return $this->swoole;
    }

    /**
     * Clears the headers, cookies, content, status code of the response.
     */
    public function clear()
    {
        $this->_headers    = null;
        $this->_cookies    = null;
        $this->_statusCode = 200;
        $this->statusText  = 'OK';
        $this->data        = null;
        $this->stream      = null;
        $this->content     = null;
        $this->isSent      = false;
        $this->swoole      = null;
    }

    /**
     * Returns the header collection.
     * The header collection contains the currently registered HTTP headers.
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollection;
        }
        return $this->_headers;
    }

    /**
     * Sends the response to the client.
     */
    public function send()
    {
        $this->trigger(self::EVENT_BEFORE_SEND);
        $this->prepare();
        $this->trigger(self::EVENT_AFTER_PREPARE);
        $this->sendHeaders();
        $this->sendContent();
        $this->trigger(self::EVENT_AFTER_SEND);
        $this->isSent = true;
    }

    /**
     * Prepares for sending the response.
     * The default implementation will convert [[data]] into [[content]] and set headers accordingly.
     * @throws InvalidConfigException if the formatter for the specified format is invalid or [[format]] is not supported
     */
    protected function prepare()
    {
        if ($this->stream !== null) {
            return;
        }
        if (isset($this->formatters[$this->format])) {
            $formatter = $this->formatters[$this->format];
            if (!is_object($formatter)) {
                $this->formatters[$this->format] = $formatter = \Yii::createObject($formatter);
            }
            if ($formatter instanceof ResponseFormatterInterface) {
                $formatter->format($this);
            } else {
                throw new InvalidConfigException("The '{$this->format}' response formatter is invalid. It must implement the ResponseFormatterInterface.");
            }
        } elseif ($this->format === self::FORMAT_RAW) {
            if ($this->data !== null) {
                $this->content = $this->data;
            }
        } else {
            throw new InvalidConfigException("Unsupported response format: {$this->format}");
        }

        if (is_array($this->content)) {

            throw new InvalidParamException("Response content must not be an array.");
        } elseif (is_object($this->content)) {
            if (method_exists($this->content, '__toString')) {
                $this->content = $this->content->__toString();
            } else {
                throw new InvalidParamException("Response content must be a string or an object implementing __toString().");
            }
        }
    }

    /**
     * Determines the HTTP range given in the request.
     * @param integer $fileSize the size of the file that will be used to validate the requested HTTP range.
     * @return array|boolean the range (begin, end), or false if the range request is invalid.
     */
    protected function getHttpRange($fileSize)
    {
        if (!isset($_SERVER['HTTP_RANGE']) || $_SERVER['HTTP_RANGE'] === '-') {
            return [0, $fileSize - 1];
        }
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $_SERVER['HTTP_RANGE'], $matches)) {
            return false;
        }
        if ($matches[1] === '') {
            $start = $fileSize - $matches[2];
            $end   = $fileSize - 1;
        } elseif ($matches[2] !== '') {
            $start = $matches[1];
            $end   = $matches[2];
            if ($end >= $fileSize) {
                $end = $fileSize - 1;
            }
        } else {
            $start = $matches[1];
            $end   = $fileSize - 1;
        }
        if ($start < 0 || $start > $end) {
            return false;
        } else {
            return [$start, $end];
        }
    }

    /**
     * Sends the response headers to the client
     */
    protected function sendHeaders()
    {
        if (!$this->swoole) {
            return;
        }
        if ($this->isSent) {
            return;
        }
        $statusCode = $this->getStatusCode();
        $this->swoole->status($statusCode);

        if ($this->_headers) {
            $headers = $this->getHeaders();
            foreach ($headers as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                foreach ($values as $value) {
                    $this->swoole->header($name, $value);
                }
            }
        }
        $this->sendCookies();
    }

    /**
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        if (!$this->swoole) {
            return;
        }
        if ($this->isSent) {
            return;
        }
        if ($this->_cookies === null) {
            return;
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1 && isset($validationKey)) {
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            $this->swoole->cookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }

    /**
     * Sends the response content to the client
     */
    protected function sendContent()
    {
        if (!$this->swoole) {
            print_r($this->data);
            return;
        }
        if ($this->stream === null) {
            $this->swoole->write($this->content);
            return;
        }

        set_time_limit(0); // Reset time limit for big files
        $chunkSize = 8 * 1024 * 1024; // 8MB per chunk

        if (is_array($this->stream)) {
            list($handle, $begin, $end) = $this->stream;
            fseek($handle, $begin);
            while (!feof($handle) && ($pos = ftell($handle)) <= $end) {
                if ($pos + $chunkSize > $end) {
                    $chunkSize = $end - $pos + 1;
                }
                $this->swoole->write(fread($handle, $chunkSize));
                flush(); // Free up memory. Otherwise large files will trigger PHP's memory limit.
            }
            fclose($handle);
        } else {
            while (!feof($this->stream)) {
                $this->swoole->write(fread($this->stream, $chunkSize));
                flush();
            }
            fclose($this->stream);
        }
    }

}

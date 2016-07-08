<?php
/**
 * Yii 运行在swoole下的输出类
 * User: lusc
 * Date: 15-6-12
 * Time: 下午3:11
 */

namespace swoole\yii\web;

use swoole\EndException;
use swoole\SwooleServer;
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

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
        $this->clear();
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
        SwooleServer::$swooleApp->currentSwooleResponse->end($this->content);
    }
    /**
     * Sends a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
     * @param string $filePath the path of the file to be sent.
     * @param string $attachmentName the file name shown to the user. If null, it will be determined from `$filePath`.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *
     * @return $this the response object itself
     */
    public function sendFile($filePath, $attachmentName = null, $options = [])
    {
        if (!isset($options['mimeType'])) {
            $options['mimeType'] = yii\helpers\FileHelper::getMimeTypeByExtension($filePath);
        }
        if ($attachmentName === null) {
            $attachmentName = basename($filePath);
        }
        SwooleServer::$swooleApp->currentSwooleResponse->header('Content-disposition', 'attachment; filename="' . urlencode($attachmentName) . '.xlsx"');
        SwooleServer::$swooleApp->currentSwooleResponse->header('Content-Type', $options['mimeType']);
        SwooleServer::$swooleApp->currentSwooleResponse->header('Content-Transfer-Encoding', 'binary');
        SwooleServer::$swooleApp->currentSwooleResponse->header('Cache-Control', 'must-revalidate');
        SwooleServer::$swooleApp->currentSwooleResponse->header('Pragma', 'public');
        SwooleServer::$swooleApp->currentSwooleResponse->sendfile($filePath);
        throw new EndException(); // 退出后续执行
    }
    /**
     * Sends existing file to a browser as a download using x-sendfile.
     *
     * X-Sendfile is a feature allowing a web application to redirect the request for a file to the webserver
     * that in turn processes the request, this way eliminating the need to perform tasks like reading the file
     * and sending it to the user. When dealing with a lot of files (or very big files) this can lead to a great
     * increase in performance as the web application is allowed to terminate earlier while the webserver is
     * handling the request.
     *
     * The request is sent to the server through a special non-standard HTTP-header.
     * When the web server encounters the presence of such header it will discard all output and send the file
     * specified by that header using web server internals including all optimizations like caching-headers.
     *
     * As this header directive is non-standard different directives exists for different web servers applications:
     *
     * - Apache: [X-Sendfile](http://tn123.org/mod_xsendfile)
     * - Lighttpd v1.4: [X-LIGHTTPD-send-file](http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file)
     * - Lighttpd v1.5: [X-Sendfile](http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file)
     * - Nginx: [X-Accel-Redirect](http://wiki.nginx.org/XSendfile)
     * - Cherokee: [X-Sendfile and X-Accel-Redirect](http://www.cherokee-project.com/doc/other_goodies.html#x-sendfile)
     *
     * So for this method to work the X-SENDFILE option/module should be enabled by the web server and
     * a proper xHeader should be sent.
     *
     * **Note**
     *
     * This option allows to download files that are not under web folders, and even files that are otherwise protected
     * (deny from all) like `.htaccess`.
     *
     * **Side effects**
     *
     * If this option is disabled by the web server, when this method is called a download configuration dialog
     * will open but the downloaded file will have 0 bytes.
     *
     * **Known issues**
     *
     * There is a Bug with Internet Explorer 6, 7 and 8 when X-SENDFILE is used over an SSL connection, it will show
     * an error message like this: "Internet Explorer was not able to open this Internet site. The requested site
     * is either unavailable or cannot be found.". You can work around this problem by removing the `Pragma`-header.
     *
     * **Example**
     *
     * ~~~
     * Yii::$app->response->xSendFile('/home/user/Pictures/picture1.jpg');
     * ~~~
     *
     * @param string $filePath file name with full path
     * @param string $attachmentName file name shown to the user. If null, it will be determined from `$filePath`.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. If not set, it will be guessed based on `$filePath`
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *  - xHeader: string, the name of the x-sendfile header. Defaults to "X-Sendfile".
     *
     * @return $this the response object itself
     */
    public function xSendFile($filePath, $attachmentName = null, $options = [])
    {
        $this->sendFile($filePath, $attachmentName, $options);
    }
    /**
     * Sends the specified stream as a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
     * @param resource $handle the handle of the stream to be sent.
     * @param string $attachmentName the file name shown to the user.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *  - `fileSize`: the size of the content to stream this is useful when size of the content is known
     *    and the content is not seekable. Defaults to content size using `ftell()`.
     *    This option is available since version 2.0.4.
     *
     * @return $this the response object itself
     * @throws yii\web\HttpException if the requested range cannot be satisfied.
     */
    public function sendStreamAsFile($handle, $attachmentName, $options = [])
    {
        throw new yii\base\Exception('Not Support:' . __METHOD__);
    }

    /**
     * Sends the specified content as a file to the browser.
     *
     * Note that this method only prepares the response for file sending. The file is not sent
     * until [[send()]] is called explicitly or implicitly. The latter is done after you return from a controller action.
     *
     * @param string $content the content to be sent. The existing [[content]] will be discarded.
     * @param string $attachmentName the file name shown to the user.
     * @param array $options additional options for sending the file. The following options are supported:
     *
     *  - `mimeType`: the MIME type of the content. Defaults to 'application/octet-stream'.
     *  - `inline`: boolean, whether the browser should open the file within the browser window. Defaults to false,
     *    meaning a download dialog will pop up.
     *
     * @return $this the response object itself
     * @throws yii\web\HttpException if the requested range is not satisfiable
     */
    public function sendContentAsFile($content, $attachmentName, $options = [])
    {
        throw new yii\base\Exception('Not Support:' . __METHOD__);
    }

}

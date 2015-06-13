<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\vendor\yiisoft\yii2\web;

use Yii;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;
use yii\helpers\Url;
use yii\helpers\FileHelper;
use yii\helpers\StringHelper;

/**
 * The web Response class represents an HTTP response
 *
 * It holds the [[headers]], [[cookies]] and [[content]] that is to be sent to the client.
 * It also controls the HTTP [[statusCode|status code]].
 *
 * Response is configured as an application component in [[\yii\web\Application]] by default.
 * You can access that instance via `Yii::$app->response`.
 *
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 *
 * ~~~
 * 'response' => [
 *     'format' => yii\web\Response::FORMAT_JSON,
 *     'charset' => 'UTF-8',
 *     // ...
 * ]
 * ~~~
 *
 * @property CookieCollection $cookies The cookie collection. This property is read-only.
 * @property string $downloadHeaders The attachment file name. This property is write-only.
 * @property HeaderCollection $headers The header collection. This property is read-only.
 * @property boolean $isClientError Whether this response indicates a client error. This property is
 * read-only.
 * @property boolean $isEmpty Whether this response is empty. This property is read-only.
 * @property boolean $isForbidden Whether this response indicates the current request is forbidden. This
 * property is read-only.
 * @property boolean $isInformational Whether this response is informational. This property is read-only.
 * @property boolean $isInvalid Whether this response has a valid [[statusCode]]. This property is read-only.
 * @property boolean $isNotFound Whether this response indicates the currently requested resource is not
 * found. This property is read-only.
 * @property boolean $isOk Whether this response is OK. This property is read-only.
 * @property boolean $isRedirection Whether this response is a redirection. This property is read-only.
 * @property boolean $isServerError Whether this response indicates a server error. This property is
 * read-only.
 * @property boolean $isSuccessful Whether this response is successful. This property is read-only.
 * @property integer $statusCode The HTTP status code to send with the response.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
class SwooleResponse extends \yii\web\Response
{
    /**
     * @var CookieCollection Collection of request cookies.
     */
    private $_cookies;
    /**
     * @var integer the HTTP status code to send with the response.
     */
    private $_statusCode = 200;
    /**
     * @var array the headers in this collection (indexed by the header names)
     */
    private $_headers;

    private $swooleHttpResponse = null;
    /**
     * Sends the response headers to the client
     */
    protected function sendHeaders()
    {
        if (headers_sent($filename, $linenum)) {
            return;
        }
        $swoole_http_response = $this->swooleHttpResponse;
        $statusCode = $this->getStatusCode();
        $swoole_http_response->status($statusCode);
        // header("HTTP/{$this->version} $statusCode {$this->statusText}");
        if ($this->getHeaders()) {
            $headers = $this->getHeaders();
            foreach ($headers as $name => $values) {
                $name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $name)));
                // set replace for first occurrence of header but false afterwards to allow multiple
                $replace = true;
                foreach ($values as $value) {
                    // header("$name: $value", $replace);
                    $swoole_http_response->header($name, $value);
                    $replace = false;
                }
            }
        }
        $this->sendCookies();
    }

    public function setSwooleHttpResponse($response)
    {
        $this->swooleHttpResponse = $response;
    }

    /**
     * Sends the cookies to the client.
     */
    protected function sendCookies()
    {
        if ($this->getCookies() === null) {
            return;
        }
        $request = Yii::$app->getRequest();
        if ($request->enableCookieValidation) {
            if ($request->cookieValidationKey == '') {
                throw new InvalidConfigException(get_class($request) . '::cookieValidationKey must be configured with a secret key.');
            }
            $validationKey = $request->cookieValidationKey;
        }
        $swoole_http_response = $this->swooleHttpResponse;
        foreach ($this->getCookies() as $cookie) {
            $value = $cookie->value;
            if ($cookie->expire != 1  && isset($validationKey)) {
                $value = Yii::$app->getSecurity()->hashData(serialize([$cookie->name, $value]), $validationKey);
            }
            // setcookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
            $swoole_http_response->cookie($cookie->name, $value, $cookie->expire, $cookie->path, $cookie->domain, $cookie->secure, $cookie->httpOnly);
        }
    }
}

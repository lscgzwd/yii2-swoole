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

namespace yiiswoole\traits;

trait SessionTrait
{
    private $_hasSessionId = null;
    /**
     * @var array parameter-value pairs to override default session cookie parameters that are used for session_set_cookie_params() function
     * Array may have the following possible keys: 'lifetime', 'path', 'domain', 'secure', 'httponly'
     * @see http://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    private $_cookieParams = ['httponly' => true];

    public function clear()
    {
        $this->setHasSessionId(null);
        $this->setCookieParams(['httponly' => true]);
    }
    public function open()
    {
        $ret = parent::open();
        if ($this->getIsActive() && $this->getUseCookies() === true && $this->getHasSessionId() === false) {
            $data = $this->getCookieParams();
            extract($data);
            if (isset($lifetime, $path, $domain, $secure, $httponly)) {
                $cookies = \Yii::$app->getResponse()->cookies;
                $cookies->add(new \yii\web\Cookie([
                    'name'     => $this->getName(),
                    'value'    => $this->getId(),
                    'domain'   => $domain,
                    'path'     => $path,
                    'expire'   => $lifetime ? time() + $lifetime : 0,
                    'secure'   => $secure,
                    'httpOnly' => $httponly,
                ]));
            } else {
                $cookies = \Yii::$app->getResponse()->cookies;
                $cookies->add(new \yii\web\Cookie([
                    'name'  => $this->getName(),
                    'value' => $this->getId(),
                ]));
            }
        }
        return $ret;
    }
}

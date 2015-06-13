<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\vendor\yiisoft\yii2\web;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidValueException;
use yii\web\User;

/**
 * User is the class for the "user" application component that manages the user authentication status.
 *
 * You may use [[isGuest]] to determine whether the current user is a guest or not.
 * If the user is a guest, the [[identity]] property would return null. Otherwise, it would
 * be an instance of [[IdentityInterface]].
 *
 * You may call various methods to change the user authentication status:
 *
 * - [[login()]]: sets the specified identity and remembers the authentication status in session and cookie.
 * - [[logout()]]: marks the user as a guest and clears the relevant information from session and cookie.
 * - [[setIdentity()]]: changes the user identity without touching session or cookie.
 *   This is best used in stateless RESTful API implementation.
 *
 * Note that User only maintains the user authentication status. It does NOT handle how to authenticate
 * a user. The logic of how to authenticate a user should be done in the class implementing [[IdentityInterface]].
 * You are also required to set [[identityClass]] with the name of this class.
 *
 * User is configured as an application component in [[\yii\web\Application]] by default.
 * You can access that instance via `Yii::$app->user`.
 *
 * You can modify its configuration by adding an array to your application config under `components`
 * as it is shown in the following example:
 *
 * ~~~
 * 'user' => [
 *     'identityClass' => 'app\models\User', // User must implement the IdentityInterface
 *     'enableAutoLogin' => true,
 *     // 'loginUrl' => ['user/login'],
 *     // ...
 * ]
 * ~~~
 *
 * @property string|integer $id The unique identifier for the user. If null, it means the user is a guest.
 * This property is read-only.
 * @property IdentityInterface|null $identity The identity object associated with the currently logged-in
 * user. `null` is returned if the user is not logged in (not authenticated).
 * @property boolean $isGuest Whether the current user is a guest. This property is read-only.
 * @property string $returnUrl The URL that the user should be redirected to after login. Note that the type
 * of this property differs in getter and setter. See [[getReturnUrl()]] and [[setReturnUrl()]] for details.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class JzUser extends User
{
    private $_access = [];

    private $_identity = false;

    /**
     * Logs in a user by cookie.
     *
     * This method attempts to log in a user using the ID and authKey information
     * provided by the [[identityCookie|identity cookie]].
     */
    protected function loginByCookie()
    {
        $value = Yii::$app->getRequest()->getCookies()->getValue($this->identityCookie['name']);
        if ($value === null) {
            return;
        }

        $data = json_decode($value, true);
        if (count($data) !== 3 || !isset($data[0], $data[1], $data[2])) {
            return;
        }

        list ($id, $duration) = $data;
        /* @var $class IdentityInterface */
        $class = $this->identityClass;
        $identity = $class::findIdentity($id);
        if ($identity === null) {
            return;
        } elseif (!$identity instanceof IdentityInterface) {
            throw new InvalidValueException("$class::findIdentity() must return an object implementing IdentityInterface.");
        }

        if ($this->beforeLogin($identity, true, $duration)) {
            $this->switchIdentity($identity, $this->autoRenewCookie ? $duration : 0);
            $ip = Yii::$app->getRequest()->getUserIP();
            Yii::info("User '$id' logged in from $ip via cookie.", __METHOD__);
            $this->afterLogin($identity, true, $duration);
        }
    }

    /**
     * Sends an identity cookie.
     * This method is used when [[enableAutoLogin]] is true.
     * It saves [[id]], [[IdentityInterface::getAuthKey()|auth key]], and the duration of cookie-based login
     * information in the cookie.
     * @param IdentityInterface $identity
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * @see loginByCookie()
     */
    protected function sendIdentityCookie($identity, $duration)
    {
        $cookie = new Cookie($this->identityCookie);
        $cookie->value = json_encode([
            $identity->getId(),
            $duration,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $cookie->expire = time() + $duration;
        Yii::$app->getResponse()->getCookies()->add($cookie);
    }

    /**
     * Switches to a new identity for the current user.
     *
     * When [[enableSession]] is true, this method may use session and/or cookie to store the user identity information,
     * according to the value of `$duration`. Please refer to [[login()]] for more details.
     *
     * This method is mainly called by [[login()]], [[logout()]] and [[loginByCookie()]]
     * when the current user needs to be associated with the corresponding identity information.
     *
     * @param IdentityInterface|null $identity the identity information to be associated with the current user.
     * If null, it means switching the current user to be a guest.
     * @param integer $duration number of seconds that the user can remain in logged-in status.
     * This parameter is used only when `$identity` is not null.
     */
    public function switchIdentity($identity, $duration = 0)
    {
        $this->setIdentity($identity);

        if (!$this->enableSession) {
            return;
        }

        $session = Yii::$app->redis;

        $session->del($this->idParam);
        $session->del($this->authTimeoutParam);

        if ($identity) {
            $session->set($this->idParam, $identity->getId());
            if ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
            if ($this->absoluteAuthTimeout !== null) {
                $session->set($this->absoluteAuthTimeoutParam, time() + $this->absoluteAuthTimeout);
            }
            if ($duration > 0 && $this->enableAutoLogin) {
                $this->sendIdentityCookie($identity, $duration);
            }
        } elseif ($this->enableAutoLogin) {
            Yii::$app->getResponse()->getCookies()->remove(new Cookie($this->identityCookie));
        }
    }

    /**
     * Updates the authentication status using the information from session and cookie.
     *
     * This method will try to determine the user identity using the [[idParam]] session variable.
     *
     * If [[authTimeout]] is set, this method will refresh the timer.
     *
     * If the user identity cannot be determined by session, this method will try to [[loginByCookie()|login by cookie]]
     * if [[enableAutoLogin]] is true.
     */
    protected function renewAuthStatus()
    {
        $session = Yii::$app->redis;
        $id = $session->get($this->idParam);

        if ($id === null) {
            $identity = null;
        } else {
            /* @var $class IdentityInterface */
            $class = $this->identityClass;
            $identity = $class::findIdentity($id);
        }

        $this->setIdentity($identity);

        if ($identity !== null && ($this->authTimeout !== null || $this->absoluteAuthTimeout !== null)) {
            $expire = $this->authTimeout !== null ? $session->get($this->authTimeoutParam) : null;
            $expireAbsolute = $this->absoluteAuthTimeout !== null ? $session->get($this->absoluteAuthTimeoutParam) : null;
            if ($expire !== null && $expire < time() || $expireAbsolute !== null && $expireAbsolute < time()) {
                $this->logout(false);
            } elseif ($this->authTimeout !== null) {
                $session->set($this->authTimeoutParam, time() + $this->authTimeout);
            }
        }

        if ($this->enableAutoLogin) {
            if ($this->getIsGuest()) {
                $this->loginByCookie();
            } elseif ($this->autoRenewCookie) {
                $this->renewIdentityCookie();
            }
        }
    }
}

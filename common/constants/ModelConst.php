<?php
/**
 * 模型常量定义
 * User: lusc
 * Date: 2016/5/13
 * Time: 18:06
 */

namespace common\constants;

class ModelConst
{
    // success
    const SUCCESS = 0;
    const OK      = 200;

    // common error number
    const G_SYS_ERR   = 10000; // system error
    const SIGN_ERROR  = 401; // wrong sign
    const G_API_ERROR = 10007; // api request error

    const G_METHOD      = 10001; // wrong action
    const G_PARAM       = 10002; // wrong params
    const G_NO_LOGIN    = 10003; // need login
    const G_ROLE_ERR    = 10004; // no privilege
    const G_REQUEST_ERR = 10005; // wrong request method

    protected static $defaultMsg = 'unknown error.';

    protected static $defaultUserMsg = 'unknown error';

    // error message
    protected static $returnMessage = array(
        // success
        self::SUCCESS       => 'success',
        self::OK            => 'OK',

        // common error
        self::G_SYS_ERR     => 'internal server error.',
        self::G_METHOD      => 'wrong action.',
        self::G_PARAM       => 'wrong params.',
        self::G_NO_LOGIN    => 'need login.',
        self::G_ROLE_ERR    => 'no privilege.',
        self::G_REQUEST_ERR => 'wrong request method.',
        self::SIGN_ERROR    => 'wrong api sign.',
        self::G_API_ERROR   => 'API request fail.',
    );

    /**
     * get common return for error
     * @param        $errorNo
     * @param string $msg
     * @param string $userMsg
     * @return array
     */
    public static function getError($errorNo, $msg = '', $userMsg = '')
    {
        if ($msg == '') {

            if (isset(self::$returnMessage[$errorNo])) {
                $userMsg = self::$returnMessage[$errorNo];
            } else {
                $userMsg = self::$defaultUserMsg;
            }
        }

        if ($userMsg == '') {
            if (isset(self::$returnMessage[$errorNo])) {
                $userMsg = self::$returnMessage[$errorNo];
            } else {
                $userMsg = self::$defaultUserMsg;
            }
        }

        return array(
            'returnCode'        => $errorNo,
            'returnMessage'     => $msg,
            'returnUserMessage' => $userMsg,
        );
    }

    /**
     * get format common return with code number
     * @param int    $errorNo
     * @param string $userMsg
     * @param array  $data
     * @return array
     */
    public static function getResult($errorNo = 200, $userMsg = '', $data = [])
    {
        if ($userMsg == '') {
            if (isset(self::$returnMessage[$errorNo])) {
                $userMsg = self::$returnMessage[$errorNo];
            } else {
                $userMsg = self::$defaultUserMsg;
            }
        }

        return ['errno' => $errorNo, 'msg' => $userMsg, 'data' => $data];
    }

}

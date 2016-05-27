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
    // 成功
    const SUCCESS = 0;
    const OK = 200;

    // 通用错误号
    const G_SYS_ERR = 10000; // 系统错误

    const G_METHOD      = 10001; // 请求方法错误
    const G_PARAM       = 10002; // 参数错误，包含缺失和格式错误
    const G_NO_LOGIN    = 10003; // 用户未登录
    const G_ROLE_ERR    = 10004; // 用户权限不足
    const G_REQUEST_ERR = 10005; //访问姿势不正确

    //发工资

    //员工

    //充值
    const G_CHARGE_EXCEED_LENGTH = 12001;

    //企业信息
    const G_PERSON_CARD_REJECT = 13001;
    
    protected static $defaultMsg = '未知错误';

    protected static $defaultUserMsg = '未知错误';

    // 错误信息
    protected static $returnMessage = array(
        // 成功
        self::SUCCESS       => 'success',
        self::OK       => 'OK',

        // 通用错误
        self::G_SYS_ERR     => '内部系统错误',
        self::G_METHOD      => '请求方法错误',
        self::G_PARAM       => '请求参数不合法',
        self::G_NO_LOGIN    => '用户未登录',
        self::G_ROLE_ERR    => '用户权限不足',
        self::G_REQUEST_ERR => '访问姿势不对',
        self::G_PERSON_CARD_REJECT => '企业版不支持个人银行卡号',
    );

    // 返回给用户的错误信息
    protected static $returnUserMessage = array(
        // 成功
        self::SUCCESS       => '操作成功',
        self::OK       => 'OK',

        // 通用错误
        self::G_SYS_ERR     => '系统错误，请稍后再试',
        self::G_METHOD      => '非法访问',
        self::G_PARAM       => '参数错误',
        self::G_NO_LOGIN    => '未登录或登录过期，请重新登录后再试',
        self::G_ROLE_ERR    => '当前用户不是管理员,或者操作员',
        self::G_REQUEST_ERR => '访问姿势不对',
        self::G_PERSON_CARD_REJECT => '企业版不支持个人银行卡号',
    );

    public static function getError($no, $msg = '', $userMsg = '')
    {
        if ($msg == '') {
        	
            if (isset(self::$returnMessage[$no])) {
                $msg = self::$returnMessage[$no];
            } else {
                $msg = self::$defaultMsg;
            }
        }

        if ($userMsg == '') {
            if (isset(self::$returnUserMessage[$no])) {
                $userMsg = self::$returnUserMessage[$no];
            } else {
                $userMsg = self::$defaultUserMsg;
            }
        }

        return array(
            'returnCode'        => $no,
            'returnMessage'     => $msg,
            'returnUserMessage' => $userMsg,
        );
    }
    public static function getResult($errno = 200, $userMsg = '', $data = [])
    {
        if ($userMsg == '') {
            if (isset(self::$returnUserMessage[$errno])) {
                $userMsg = self::$returnUserMessage[$errno];
            } else {
                $userMsg = self::$defaultUserMsg;
            }
        }

        return ['errno' => $errno, 'msg' => $userMsg, 'data' => $data];
    }

}

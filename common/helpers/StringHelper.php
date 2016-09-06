<?php
/**
 * Common helper.
 * User: lusc
 * Date: 2016/5/6
 * Time: 19:57
 */

namespace common\helpers;

class StringHelper
{
    /**
     * get random id
     * @return string
     */
    public static function uuid()
    {
        return strval(str_replace('.', '', microtime(true))) . strval(mt_rand(1000, 9999));
    }

    /**
     * check if it is mobile number in China
     * @param $str
     * @return int
     */
    public static function checkMobile($str)
    {
        return preg_match('/^1[345789]{1}\d{9}$/', $str);
    }

    /**
     * get random number
     *
     * @param  int $num length for random
     * @return string $str
     */
    public static function genRandNum($num)
    {
        $str = '';
        for ($i = 0; $i < $num; $i++) {
            $str .= rand(1, 9);
        }

        return $str;
    }
}

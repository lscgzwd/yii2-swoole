<?php
/**
 * Created by PhpStorm.
 * User: lusc
 * Date: 2016/5/6
 * Time: 19:57
 */

namespace common\helpers;

class StringHelper
{
    public static function uuid()
    {
        return strval(str_replace('.', '', microtime(true))) . strval(mt_rand(1000, 9999));
    }

    public static function checkMobile($str)
    {
        return preg_match('/^1[345789]{1}\d{9}$/', $str);
    }

    /**
     * 获取随机6位数字
     *
     * @param  int $num 数字串长度
     * @param  string $str 随机6位数字
     */
    public static function genRandNum($num)
    {
        $str = '';
        for ($i = 0; $i < $num; $i++) {
            $str .= rand(1, 9);
        }

        return $str;
    }

    /**
     * 获取随机打款金额
     * @param int $min
     * @param int $max
     */
    public static function getRandAmount()
    {
        $num = mt_rand(12, 299);
        if (in_array($num, [20, 22, 30, 33, 40, 44, 50, 55, 60, 66, 70, 77, 80, 88, 90, 99, 100, 111, 200, 222])) {
            return static::getRandAmount();
        }
        return $num;
    }

    /**
     *  将一个字串中含有全角的数字字符、字母、空格或'%+-()'字符转换为相应半角字符
     * @access    public
     * @param     string $str     待转换字串
     * @return    string  $str    处理后字串
     */
    public static function makeSemiangle($str) {
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4','５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9', 'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E','Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J', 'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O','Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T','Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y','Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd','ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i','ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n','ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x', 'ｙ' => 'y', 'ｚ' => 'z','（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[','】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']','‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<','》' => '>','％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-','：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',     '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"','　' => ' ', '．' =>'.');
        return strtr($str, $arr);
    }

    /**
     * 将以分位为单位的数字 转化为带小数点的元
     * @param $moneyNum
     * @return int|string
     */
    public static function getYuan($moneyNum)
    {
        if (strlen($moneyNum) == 1) { //1位数字
            return '0.0' . $moneyNum;
        } else if (strlen($moneyNum) == 2) {
            return '0.' . $moneyNum;
        } else if (strlen($moneyNum) == 3) {
            return substr($moneyNum, 0, 1) . "." . substr($moneyNum, 1);
        } else {
            return 0;
        }
    }

}

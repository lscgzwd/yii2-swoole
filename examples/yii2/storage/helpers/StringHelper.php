<?php
/**
 * storage
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/6
 * Time: 15:49
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */

namespace storage\helpers;

class StringHelper
{
    /**
     * ID 生成器，生成long型唯一数字
     * @return string
     */
    public static function uuid($url = '')
    {
        $opts = array(
            'http' => array(
                'method'  => 'GET',
                'timeout' => 1,
            ),
        );
        $context = stream_context_create($opts);
        if ($url == '') {
            $url = \Yii::$app->params['idGen']['host'];
        }
        return file_get_contents($url, false, $context);
    }
}

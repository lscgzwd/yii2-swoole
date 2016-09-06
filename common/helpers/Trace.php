<?php
/**
 * Common function to add log
 * User: lusc
 * Date: 2016/9/6
 * Time: 13:58
 */

namespace common\helpers;

use Yii;

class Trace
{
    /**
     * common method to add a log
     * @param string $message log message
     * @param string $security log level
     * @param array  $context  params to log
     * @param string $category log category
     * @return bool
     */
    public static function addLog($message, $security, $context = array(), $category = 'default')
    {
        // check category
        if ($category == 'default' && Yii::$app->controller) {
            $category = Yii::$app->controller->id . '-' . Yii::$app->controller->action->id;
        }

        $info = [
            '@timestamp' => date('Y-m-d H:i:s'),
            '@message'   => $message,
            'context'    => $context,
            'level'      => $security,
            'category'   => $category,
        ];
        $category = 'activity-' . $category;

        switch ($security) {
            case 'info':
            case 'error':
            case 'warning':
                Yii::$security($info, $category);
                break;
            default:
                Yii::trace($info, $category);
                break;
        }

        return true;
    }
}

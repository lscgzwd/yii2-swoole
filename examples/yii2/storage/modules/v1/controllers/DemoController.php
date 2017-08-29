<?php
/**
 * storage
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/6
 * Time: 20:18
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */

namespace storage\modules\v1\controllers;

class DemoController extends BaseController
{
    public function actionIndex()
    {
        \Yii::$app->getSession()->open();
        \Yii::$app->getResponse()->format = 'json';
        $_SESSION['abc']                  = microtime(true);
        return $_SESSION;
    }
}

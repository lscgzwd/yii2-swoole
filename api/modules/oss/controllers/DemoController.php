<?php
/**
 * Created by PhpStorm.
 * User: lusc
 * Date: 2016/5/18
 * Time: 12:25
 */

namespace api\modules\oss\controllers;

use common\controllers\AppBaseController;

class DemoController extends AppBaseController
{
    public function actionIndex()
    {
        return [
            'hello' => 'a',
            'world' => 'b',
        ];
    }
}

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
namespace yiiswoole\web;

use yii\base\InlineAction;

class Controller extends \yii\web\Controller
{
    protected static $actionInstances = [];
    public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }
        $key = $this->id . $id;
        if (isset(static::$actionInstances[$key])) {
            $action             = clone static::$actionInstances[$key];
            $action->controller = $this;
            return $action;
        }

        $action    = null;
        $actionMap = $this->actions();
        // 重写正则加入i参数，URL支持驼峰，大小写
        if (isset($actionMap[$id])) {
            $action = \Yii::createObject($actionMap[$id], [$id, null]);
        } elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    $action = new InlineAction($id, null, $methodName);
                }
            }
        }
        if ($action) {
            static::$actionInstances[$key] = $action;
            $action                        = clone static::$actionInstances[$key];
            $action->controller            = $this;
        }
        return $action;
    }
}

<?php
/**
 * 预加载所有类，针对swoole提供性能
 * User: lusc
 * Date: 2016/5/12
 * Time: 20:45
 */
defined('YII2_PATH') or define('YII2_PATH', dirname(__DIR__) . '/vendor/yiisoft/yii2');
$classMaps = require __DIR__ . '/classes.php';

foreach ($classMaps as $class => $classFile) {
    Yii::autoload($class);
}

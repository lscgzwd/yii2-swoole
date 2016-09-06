<?php
/**
 * preload all classes , speed it
 * User: lusc
 * Date: 2016/5/12
 * Time: 20:45
 */
defined('YII2_PATH') or define('YII2_PATH', dirname(__DIR__) . '/vendor/yiisoft/yii2');
$classMaps = require __DIR__ . '/classes.php';

foreach ($classMaps as $class => $classFile) {
    Yii::autoload($class);
}

<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace bweb\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle {
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $jsOptions = ['position' => \yii\web\View::POS_HEAD];
    public $cssOptions = ['position' => \yii\web\View::POS_HEAD];
    public $css = [        
        'css/kendo/kendo.common.min.css',
        'css/kendo/kendo.metro.min.css', 
        'css/kendo/kendo.dataviz.metro.min.css',
        'css/site.css',
    ];
    public $js = [
        'js/kendo/kendo.all.min.js',
        'js/kendo/messages/kendo.messages.zh-CN.new.min.js',
        'js/kendo/cultures/kendo.culture.zh-CN.min.js',
    ];
    public $depends = [
        'yii\web\JqueryAsset',
    ];
}
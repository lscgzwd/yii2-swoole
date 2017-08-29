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
 * Yii core class map.
 *
 * This file contains all classes your want load before server start.
 * Cache all classes can get more performance by reduce the file io.
 *
 */

return [
    'BriarBear\Request'                            => BRIARBEAR_PATH . 'Request.php',
    'BriarBear\Response\HttpResponse'              => BRIARBEAR_PATH . 'Response/HttpResponse.php',
    'BriarBear\Response\TcpResponse'               => BRIARBEAR_PATH . 'Response/TcpResponse.php',
    'BriarBear\Buffer'                             => BRIARBEAR_PATH . 'Buffer.php',
    'BriarBear\Exception\InvalidCallException'     => BRIARBEAR_PATH . 'Exception/InvalidCallException.php',
    'BriarBear\Exception\InvalidConfigException'   => BRIARBEAR_PATH . 'Exception/InvalidConfigException.php',
    'BriarBear\Exception\InvalidParamException'    => BRIARBEAR_PATH . 'Exception/InvalidParamException.php',
    'BriarBear\Exception\UnknownClassException'    => BRIARBEAR_PATH . 'Exception/UnknownClassException.php',
    'BriarBear\Exception\UnknownMethodException'   => BRIARBEAR_PATH . 'Exception/UnknownMethodException.php',
    'BriarBear\Exception\UnknownPropertyException' => BRIARBEAR_PATH . 'Exception/UnknownPropertyException.php',
    'BriarBear\Log\FileLogger'                     => BRIARBEAR_PATH . 'Log/FileLogger.php',
    'yiiswoole\web\Controller'                     => VENDOR_PATH . '/briarbear/yii2/src/web/Controller.php',
    'yiiswoole\web\ErrorHandler'                   => VENDOR_PATH . '/briarbear/yii2/src/web/ErrorHandler.php',
    'yiiswoole\web\Request'                        => VENDOR_PATH . '/briarbear/yii2/src/web/Request.php',
    'yiiswoole\web\Response'                       => VENDOR_PATH . '/briarbear/yii2/src/web/Response.php',
    'yiiswoole\web\Session'                        => VENDOR_PATH . '/briarbear/yii2/src/web/Session.php',
    'yiiswoole\web\User'                           => VENDOR_PATH . '/briarbear/yii2/src/web/User.php',
    'yiiswoole\db\Command'                         => VENDOR_PATH . '/briarbear/yii2/src/db/Command.php',
    'yiiswoole\db\Connection'                      => VENDOR_PATH . '/briarbear/yii2/src/db/Connection.php',
    'yiiswoole\redis\Connection'                   => VENDOR_PATH . '/briarbear/yii2/src/redis/Connection.php',
    'yiiswoole\redis\Session'                      => VENDOR_PATH . '/briarbear/yii2/src/redis/Session.php',
    'yiilog\EmailTarget'                           => VENDOR_PATH . '/briarbear/yii2-log/src/EmailTarget.php',
    'yiilog\RedisTarget'                           => VENDOR_PATH . '/briarbear/yii2-log/src/RedisTarget.php',
    'yiilog\LogstashFileTarget'                    => VENDOR_PATH . '/briarbear/yii2-log/src/LogstashFileTarget.php',
    'yii\redis\Connection'                         => VENDOR_PATH . '/yiisoft/yii2-redis/Connection.php',
    'yii\redis\Cache'                              => VENDOR_PATH . '/yiisoft/yii2-redis/Cache.php',
    'yii\redis\Session'                            => VENDOR_PATH . '/yiisoft/yii2-redis/Session.php',
    'yii\redis\ActiveQuery'                        => VENDOR_PATH . '/yiisoft/yii2-redis/ActiveQuery.php',
    'yii\redis\ActiveRecord'                       => VENDOR_PATH . '/yiisoft/yii2-redis/ActiveRecord.php',
    'yii\redis\LuaScriptBuilder'                   => VENDOR_PATH . '/yiisoft/yii2-redis/LuaScriptBuilder.php',
    'yii\httpclient\Client'                        => VENDOR_PATH . '/yiisoft/yii2-httpclient/Client.php',
    'yii\httpclient\CurlTransport'                 => VENDOR_PATH . '/yiisoft/yii2-httpclient/CurlTransport.php',
    'yii\httpclient\FormatterInterface'            => VENDOR_PATH . '/yiisoft/yii2-httpclient/FormatterInterface.php',
    'yii\httpclient\JsonFormatter'                 => VENDOR_PATH . '/yiisoft/yii2-httpclient/JsonFormatter.php',
    'yii\httpclient\JsonParser'                    => VENDOR_PATH . '/yiisoft/yii2-httpclient/JsonParser.php',
    'yii\httpclient\Message'                       => VENDOR_PATH . '/yiisoft/yii2-httpclient/Message.php',
    'yii\httpclient\ParserInterface'               => VENDOR_PATH . '/yiisoft/yii2-httpclient/ParserInterface.php',
    'yii\httpclient\Request'                       => VENDOR_PATH . '/yiisoft/yii2-httpclient/Request.php',
    'yii\httpclient\Response'                      => VENDOR_PATH . '/yiisoft/yii2-httpclient/Response.php',
    'yii\httpclient\StreamTransport'               => VENDOR_PATH . '/yiisoft/yii2-httpclient/StreamTransport.php',
    'yii\httpclient\Transport'                     => VENDOR_PATH . '/yiisoft/yii2-httpclient/Transport.php',
    'yii\httpclient\UrlEncodedFormatter'           => VENDOR_PATH . '/yiisoft/yii2-httpclient/UrlEncodedFormatter.php',
    'yii\httpclient\UrlEncodedParser'              => VENDOR_PATH . '/yiisoft/yii2-httpclient/UrlEncodedParser.php',
    'yii\httpclient\XmlFormatter'                  => VENDOR_PATH . '/yiisoft/yii2-httpclient/XmlFormatter.php',
    'yii\httpclient\XmlParser'                     => VENDOR_PATH . '/yiisoft/yii2-httpclient/XmlParser.php',
];

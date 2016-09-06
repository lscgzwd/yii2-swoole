<?php
/**
 * @link      https://github.com/common\vendor\log/yii2-log
 * @copyright Copyright (c) 2014 Roman Levishchenko <index.0h@gmail.com>
 * @license   https://raw.github.com/common\vendor\log/yii2-log/master/LICENSE
 */

namespace common\vendor\log\base;

use swoole\SwooleServer;
use yii\helpers\ArrayHelper;
use yii\log\Logger;

/**
 * @property string[]    categories     List of message categories that this target is interested in.
 * @property string[]    except         List of message categories that this target is NOT interested in
 * @property int         exportInterval How many messages should be accumulated before they are exported.
 * @property string[]    logVars        List of the PHP predefined variables that should be logged in a message.
 * @property array       messages       The messages that are retrieved from the logger so far by this log target.
 *
 * @method int getLevels() The message levels that this target is interested in.
 * @method array filterMessages(array $messages, int $levels, array $categories, array $except)
 *     Filters the given messages according to their categories and levels.
 * @method void export Exports log [[messages]] to a specific destination.
 *
 * @author Roman Levishchenko <index.0h@gmail.com>
 */
trait TargetTrait
{
    /** @var bool Whether to log a message containing the current user name and ID. */
    public $logUser = false;

    /** @var array Add more context */
    public $context = [];

    /**
     * Processes the given log messages.
     *
     * @param array $messages Log messages to be processed.
     * @param bool  $final    Whether this method is called at the end of the current application
     */
    public function collect($messages, $final)
    {
        $this->messages = array_merge(
            $this->messages,
            $this->filterMessages($messages, $this->getLevels(), $this->categories, $this->except)
        );
        $count = count($this->messages);
        if (($count > 0) && (($final == true) || ($this->exportInterval > 0) && ($count >= $this->exportInterval))) {
            $this->addContextToMessages();
            $this->export();
            $this->messages = [];
        }
    }

    /**
     * Formats a log message.
     *
     * @param array $message The log message to be formatted.
     *
     * @return string
     */
    public function formatMessage($message)
    {
        return json_encode($this->prepareMessage($message), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Updates all messages if there are context variables.
     */
    protected function addContextToMessages()
    {
        $context = $this->getContextMessage();

        if ($context === []) {
            return;
        }

        foreach ($this->messages as &$message) {
            $message[0] = ArrayHelper::merge($context, $this->parseText($message[0]));
        }
    }

    /**
     * Generates the context information to be logged.
     *
     * @return array
     */
    protected function getContextMessage()
    {
        $context = $this->context;

        if (($this->logUser === true) && ($user = \Yii::$app->get('user', false)) !== null) {
            /** @var \yii\web\User $user */
            $context['userId'] = $user->getId();
        }

        foreach ($this->logVars as $name) {
            if (empty($GLOBALS[$name]) === false) {
                $context[$name] = &$GLOBALS[$name];
            }
        }

        return $context;
    }

    /**
     * Convert's any type of log message to array.
     *
     * @param mixed $text Input log message.
     *
     * @return array
     */
    protected function parseText($text)
    {
        $type = gettype($text);
        switch ($type) {
            case 'array':
                return $text;
            case 'string':
                return ['@message' => $text];
            case 'object':
                return get_object_vars($text);
            default:
                return ['@message' => \Yii::t('log', "Warning, wrong log message type '{$type}'")];
        }
    }

    /**
     * Transform log message to assoc.
     *
     * @param array $message The log message.
     *
     * @return array
     */
    protected function prepareMessage($message)
    {
        list($text, $level, $category, $timestamp) = $message;

        $level     = Logger::getLevelName($level);
        $timestamp = date('c', $timestamp);

        $result = ArrayHelper::merge(
            $this->parseText($text),
            ['level' => $level, 'category' => $category, '@timestamp' => $timestamp]
        );

        if (isset($message[4]) === true) {
            $result['trace'] = $message[4];
        }
        // 定义trackid , 方便跟踪请求日志链条
        $trackId = null;
        if (defined('IN_SWOOLE')) {
            $trackId = SwooleServer::$swooleApp->logTraceId;
        } else {
            $trackId = YII_TRACK_ID;
        }
        $result['trackId'] = $trackId;
        // 加入服务器名称
        $result['server'] = gethostname();

        return $result;
    }
}

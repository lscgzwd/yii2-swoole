<?php
/**
 * @link      https://github.com/common\vendor\log/yii2-log
 * @copyright Copyright (c) 2014 Roman Levishchenko <index.0h@gmail.com>
 * @license   https://raw.github.com/common\vendor\log/yii2-log/master/LICENSE
 */

namespace common\vendor\log;

use common\vendor\log\base\TargetTrait;
use Yii;
use yii\base\InvalidConfigException;

/**
 * @author Roman Levishchenko <index.0h@gmail.com>
 */
class LogstashFileTarget extends \yii\log\FileTarget
{
    use TargetTrait;

    public $logPath;
    public $logFileSuffix         = '';
    public $logFilePrefix         = '';
    public $logFileExt            = '.log';
    public $logFileNameDateFormat = 'Ymd';

    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     */
    public function init()
    {
        $this->logPath = rtrim(Yii::getAlias($this->logPath), '/');
        $this->logFile = $this->logPath . '/' . $this->logFilePrefix . date($this->logFileNameDateFormat) . $this->logFileSuffix . $this->logFileExt;

        parent::init();
    }

    /**
     * Writes log messages to a file.
     * @throws InvalidConfigException if unable to open the log file for writing
     */
    public function export()
    {
        $text          = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        $this->logFile = $this->logPath . '/' . $this->logFilePrefix . date($this->logFileNameDateFormat) . $this->logFileSuffix . $this->logFileExt;
        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }
        @flock($fp, LOCK_EX);
        if ($this->enableRotation) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }
}

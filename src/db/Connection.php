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

namespace yiiswoole\db;

class Connection extends \yii\db\Connection
{
    /**
     * @var string the class used to create new database [[Command]] objects. If you want to extend the [[Command]] class,
     * you may configure this property to use your extended version of the class.
     * @see createCommand
     * @since 2.0.7
     */
    public $commandClass = 'yiiswoole\db\Command';

    protected $errorCount = 0;
    public $maxErrorTimes = 2;

    /**
     * Starts a transaction.
     * @param string|null $isolationLevel The isolation level to use for this transaction.
     * See [[Transaction::begin()]] for details.
     * @return \yii\db\Transaction the transaction initiated
     */
    public function beginTransaction($isolationLevel = null)
    {
        try {
            return parent::beginTransaction($isolationLevel);
        } catch (\Throwable $exception) {
            if ($this->isConnectionError($exception) && $this->errorCount < $this->maxErrorTimes) {
                $this->close();
                $this->open();
                $this->errorCount++;
                return $this->beginTransaction($isolationLevel);
            }
            $this->errorCount = 0;
            throw  $exception;
        }
    }
    /**
     * 检查指定的异常是否为可以重连的错误类型
     *
     * @param \Exception $exception
     * @return bool
     */
    public function isConnectionError($exception)
    {
        if ($exception instanceof \PDOException) {
            $errorCode = $exception->getCode();
            if ($errorCode == 70100 || $errorCode == 2006 || $errorCode == 2013) {
                return true;
            }
        }
        $message = $exception->getMessage();
        if (strpos($message, 'Error while sending QUERY packet.') !== false) {
            return true;
        }
        // Error reading result set's header
        if (strpos($message, 'Error reading result set\'s header') !== false) {
            return true;
        }
        // MySQL server has gone away
        if (strpos($message, 'MySQL server has gone away') !== false) {
            return true;
        }
        return false;
    }
}

<?php
/**
 * @link      https://github.com/common\vendor\log/yii2-log
 * @copyright Copyright (c) 2014 Roman Levishchenko <index.0h@gmail.com>
 * @license   https://raw.github.com/common\vendor\log/yii2-log/master/LICENSE
 */

namespace common\vendor\log;

use common\vendor\log\base\EmergencyTrait;
use common\vendor\log\base\TargetTrait;
use yii\log\Target;

/**
 * @author Roman Levishchenko <index.0h@gmail.com>
 */
class RedisTarget extends Target
{
    use TargetTrait;
    use EmergencyTrait;

    /** @var string Redis list key. */
    public $key = 'yii_log';

    /** @var string Yii redis component name. */
    public $componentName = 'redis';

    /**
     * @inheritdoc
     */
    public function export()
    {
        try {
            $messages = array_map([$this, 'formatMessage'], $this->messages);
            foreach ($messages as &$message) {
                \Yii::$app->{$this->componentName}->lpush($this->key, $message);
            }
        } catch (\Exception $error) {
            $this->emergencyExport(
                [
                    'key'         => $this->key,
                    'error'       => $error->getMessage(),
                    'errorNumber' => $error->getCode(),
                    'trace'       => $error->getTraceAsString(),
                ]
            );
        }
    }
}

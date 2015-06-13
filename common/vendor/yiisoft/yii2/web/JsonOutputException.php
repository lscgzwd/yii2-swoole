<?php
namespace common\vendor\yiisoft\yii2\web;

use yii\base\UserException;

class JsonOutputException extends UserException
{
    /**
     * @var integer HTTP status code, such as 403, 404, 500, etc.
     */
    public $statusCode;


    /**
     * Constructor.
     * @param integer $status HTTP status code, such as 404, 500, etc.
     * @param string $message error message
     * @param integer $code error code
     * @param \Exception $previous The previous exception used for the exception chaining.
     */
    public function __construct($status = 200, $message = null, $code = 0, \Exception $previous = null)
    {
        $this->statusCode = $status;
        parent::__construct($message, $code, $previous);
    }
}
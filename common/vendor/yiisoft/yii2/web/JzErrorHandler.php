<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace common\vendor\yiisoft\yii2\web;

use Yii;
use yii\web\Response;
use yii\base\Exception;
use yii\base\ErrorException;
use yii\base\UserException;
use yii\helpers\VarDumper;
use yii\web\ErrorHandler;

/**
 * ErrorHandler handles uncaught PHP errors and exceptions.
 *
 * ErrorHandler displays these errors using appropriate views based on the
 * nature of the errors and the mode the application runs at.
 *
 * ErrorHandler is configured as an application component in [[\yii\base\Application]] by default.
 * You can access that instance via `Yii::$app->errorHandler`.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Timur Ruziev <resurtm@gmail.com>
 * @since 2.0
 */
class JzErrorHandler extends \yii\web\ErrorHandler
{
    /**
     * Renders the exception.
     * @param \Exception $exception the exception to be rendered.
     */
    protected function renderException($exception)
    {
        if (Yii::$app->has('response')) {
            $response = Yii::$app->getResponse();
            $response->isSent = false;
        } else {
            $response = new Response();
        }

        $useErrorView = $response->format === Response::FORMAT_HTML && (!YII_DEBUG || $exception instanceof UserException);
        if($exception instanceof JsonOutputException) {
            $response->content = $exception->getMessage();
            $response->format = yii\web\Response::FORMAT_JSON;
        } elseif ($useErrorView && $this->errorAction !== null) {
            $result = Yii::$app->runAction($this->errorActison);
            if ($result instanceof Response) {
                $response = $result;
            } else {
                $response->data = $result;
            }
        } elseif ($response->format === Response::FORMAT_HTML) {
            if (YII_ENV_TEST || isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                // AJAX request
                $response->data = '<pre>' . $this->htmlEncode($this->convertExceptionToString($exception)) . '</pre>';
            } else {
                // if there is an error during error rendering it's useful to
                // display PHP error in debug mode instead of a blank screen
                if (YII_DEBUG) {
                    ini_set('display_errors', 1);
                }
                $file = $useErrorView ? $this->errorView : $this->exceptionView;
                $response->data = $this->renderFile($file, [
                    'exception' => $exception,
                ]);
            }
        } elseif ($response->format === Response::FORMAT_RAW) {
            $response->data = $exception;
        } else {
            $response->data = $this->convertExceptionToArray($exception);
        }

        if ($exception instanceof HttpException || $exception instanceof JsonOutputException) {
            $response->setStatusCode($exception->statusCode);
        } else {
            $response->setStatusCode(500);
        }

        $response->send();
    }
}

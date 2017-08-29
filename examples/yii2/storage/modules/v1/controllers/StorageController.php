<?php
/**
 * storage
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/7
 * Time: 11:54
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */

namespace storage\modules\v1\controllers;

use storage\service\UploadService;
use yiiswoole\web\Response;

class StorageController extends BaseController
{
    public function actionPut()
    {
        $uri                              = str_replace('/v1', '', $_SERVER['REQUEST_URI']);
        $headers                          = \Yii::$app->getRequest()->getHeaders();
        $authorization                    = $headers['Authorization'] ?? '';
        $method                           = $_SERVER['REQUEST_METHOD'];
        $date                             = $headers['date'] ?? '';
        $contentMD5                       = $headers['Content-MD5'] ?? '';
        $putTempFile                      = \Yii::$app->getRequest()->request->getPutRawTempFile();
        \Yii::$app->getResponse()->format = 'json';
        try {
            $config = [
                'uri'           => urldecode($uri),
                'authorization' => $authorization,
                'method'        => $method,
                'date'          => $date,
                'contentMD5'    => $contentMD5,
                'putTempFile'   => $putTempFile,
                'uploadDir'     => \Yii::$app->params['storage']['directory'],
            ];
            $service = new UploadService($config);
            $service->doUpload();
            return ['status' => 200];
        } catch (\Throwable $e) {
            $code = $e->getCode();
            if (array_key_exists($code, Response::$httpStatuses)) {
                \Yii::$app->getResponse()->setStatusCode($code);
            } else {
                \Yii::$app->getResponse()->setStatusCode(500);
            }

            return $e->__toString();
        }
    }
    public function actionPost()
    {
        $uri                              = str_replace('/v1', '', $_SERVER['REQUEST_URI']);
        $headers                          = \Yii::$app->getRequest()->getHeaders();
        $authorization                    = $headers['Authorization'] ?? '';
        $method                           = $_SERVER['REQUEST_METHOD'];
        $date                             = $headers['date'] ?? '';
        $contentMD5                       = $headers['Content-MD5'] ?? '';
        \Yii::$app->getResponse()->format = 'json';
        try {
            $config = [
                'uri'           => $uri,
                'authorization' => $authorization,
                'method'        => $method,
                'date'          => $date,
                'contentMD5'    => $contentMD5,
                'uploadDir'     => \Yii::$app->params['storage']['directory'],
            ];
            $service = new UploadService($config);
            $service->doUpload();
            return ['status' => 200];
        } catch (\Throwable $e) {
            $code = $e->getCode();
            if (array_key_exists($code, Response::$httpStatuses)) {
                \Yii::$app->getResponse()->setStatusCode($code);
            } else {
                \Yii::$app->getResponse()->setStatusCode(500);
            }

            return $e->__toString();
        }
    }
    public function actionDelete()
    {
        $uri                              = str_replace('/v1', '', $_SERVER['REQUEST_URI']);
        $headers                          = \Yii::$app->getRequest()->getHeaders();
        $authorization                    = $headers['Authorization'] ?? '';
        $method                           = $_SERVER['REQUEST_METHOD'];
        $date                             = $headers['date'] ?? '';
        \Yii::$app->getResponse()->format = 'json';
        try {
            $config = [
                'uri'           => $uri,
                'authorization' => $authorization,
                'method'        => $method,
                'date'          => $date,
                'uploadDir'     => \Yii::$app->params['storage']['directory'],
            ];
            $service = new UploadService($config);
            $service->doDelete();
            return ['status' => 200];
        } catch (\Throwable $e) {
            $code = $e->getCode();
            if (array_key_exists($code, Response::$httpStatuses)) {
                \Yii::$app->getResponse()->setStatusCode($code);
            } else {
                \Yii::$app->getResponse()->setStatusCode(500);
            }

            return $e->__toString();
        }
    }

    /**
     * @return string
     */
    public function actionGet()
    {
        $uri = str_replace('/v1', '', $_SERVER['REQUEST_URI']);
        $uri = parse_url($uri, PHP_URL_PATH);
        file_put_contents('/data/logs/lusc.txt', $uri, FILE_APPEND);
        $uri = urldecode($uri);
        try {
            $service = new UploadService([
                'uri'       => $uri,
                'uploadDir' => \Yii::$app->params['storage']['directory'],
            ]);
            $file = $service->doGet();
            \Yii::$app->getResponse()->xSendFile($file);
        } catch (\Throwable $e) {
            \Yii::$app->getResponse()->setStatusCode($e->getCode());
            return $e->getMessage();
        }
    }
    /**
     * @return string
     */
    public function actionHead()
    {
        $uri = str_replace('/v1', '', $_SERVER['REQUEST_URI']);
        $uri = parse_url($uri, PHP_URL_PATH);
        try {
            $service = new UploadService([
                'uri'       => $uri,
                'uploadDir' => \Yii::$app->params['storage']['directory'],
            ]);
            $file     = $service->doGet();
            $finfo    = new \finfo(FILEINFO_MIME_TYPE);
            $mimeType = $finfo->file($file);
            clearstatcache(true, $file);
            $info    = stat($file);
            $headers = \Yii::$app->getResponse()->getHeaders();
            $headers->add('x-mime-type', $mimeType);
            $headers->add('x-create-time', $info['ctime']);
            $headers->add('x-size', $info['size']);
        } catch (\Throwable $e) {
            \Yii::$app->getResponse()->setStatusCode($e->getCode());
            return $e->getMessage();
        }
    }
}

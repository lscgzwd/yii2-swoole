<?php
/**
 * storage
 * User: lushuncheng<admin@lushuncheng.com>
 * Date: 2017/3/7
 * Time: 11:12
 * @link https://github.com/lscgzwd
 * @copyright Copyright (c) 2017 Lu Shun Cheng (https://github.com/lscgzwd)
 * @licence http://www.apache.org/licenses/LICENSE-2.0
 * @author Lu Shun Cheng (lscgzwd@gmail.com)
 */

namespace storage\service;

use BriarBear\Exception\InvalidCallException;
use BriarBear\Exception\InvalidParamException;
use BriarBear\Helpers\FileHelper;
use yii\base\ErrorException;
use yii\base\Object;

class UploadService extends Object
{
    public $method;
    public $date;
    public $authorization;
    public $uri;
    public $contentMD5;
    public $bucket;
    public $operator;
    public $password;
    public $putTempFile;
    public $uploadDir = '/data/glusterfs';
    public $authCallbackUrl;
    public $authParamName;

    public function doUpload()
    {
        $this->initBucket()->checkDate()->checkSign();
        $target = rtrim($this->uploadDir, '/') . '/' . ltrim($this->uri, '/');
//        if (is_file($target)) {
        //            throw new InvalidCallException('File already exist', 400);
        //        }
        $dir = dirname($target);
        if (!is_dir($dir)) {
            FileHelper::createDirectory($dir);
        }
        switch ($this->method) {
            case 'PUT':
                $ret = rename($this->putTempFile, $target);
                if ($ret === false) {
                    throw new ErrorException('Save file failed', 500);
                }
                break;
            case 'POST':
                if (empty($_FILES) || !isset($_FILES['file']) || count($_FILES) > 1) {
                    throw new InvalidCallException('Upload file needed, Only one file allowed each request', 400);
                }
                $ret = rename($_FILES['file']['tmp_name'], $target);
                if ($ret === false) {
                    throw new ErrorException('Save file failed', 500);
                }
                break;
            default:
                throw new InvalidCallException('Method not allowed', 405);
                break;
        }
        return true;
    }
    public function doDelete()
    {
        $this->initBucket()->checkDate()->checkSign();
        $target = rtrim($this->uploadDir, '/') . '/' . ltrim($this->uri, '/');
        if (!is_file($target)) {
            throw new InvalidCallException('File not exist', 404);
        }
        unlink($target);
        return true;
    }
    public function doGet()
    {
        $this->initBucket();
        if ($this->checkAuth() === false) {
            throw new \yii\base\InvalidCallException('Forbidden', 403);
        }
        $file = rtrim($this->uploadDir, '/') . '/' . ltrim($this->uri, '/');
        if (!is_file($file)) {
            throw new ErrorException('File Not Exist', 404);
        }
        return $file;
    }
    protected function checkAuth()
    {
        if ($this->authParamName && $this->authCallbackUrl) {
            $auth = $_GET[$this->authParamName] ?? '';
            if ($auth) {
                $postData = [
                    $this->authParamName => $auth,
                    'uri'                => $this->uri,
                ];
                $data = http_build_query($postData);
                $opts = array(
                    'http' => array(
                        'method'  => 'POST',
                        'timeout' => 1,
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n" .
                        "Content-length:" . strlen($data) . "\r\n" .
                        "\r\n",
                        'content' => $data,
                    ),
                );
                $context = stream_context_create($opts);
                $ret     = file_get_contents($this->authCallbackUrl, false, $context);
                $ret     = json_decode($ret, true);
                if (isset($ret['error']['returnCode']) && $ret['error']['returnCode'] == 0) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return true;
    }
    protected function checkSign()
    {
        if (!$this->authorization) {
            throw new InvalidParamException('Authorization header required', 400);
        }
        $arr = explode(':', $this->authorization);
        if (count($arr) !== 2) {
            throw new InvalidParamException('Authorization invalid');
        }
        $str = trim($arr[0]);

        $operator = trim(substr($str, strrpos($str, ' ')));
        if ($operator != $this->operator) {
            throw new InvalidParamException('Operator invalid', 400);
        }
        $sign = $arr[1];
        if ($sign != $this->getSign()) {
            throw new InvalidParamException('Sign invalid', 400);
        }
    }
    protected function getSign(): String
    {
        $data = array(
            $this->method,
            $this->uri,
            $this->date,
        );
        if ($this->contentMD5) {
            $data[] = $this->contentMD5;
        }
        file_put_contents('/data/logs/lusc.txt', json_encode([
            'str'      => implode('&', $data),
            'password' => $this->password,
        ]) . "\r\n", FILE_APPEND);
        return base64_encode(hash_hmac('sha1', implode('&', $data), $this->password, true));
    }
    protected function checkDate()
    {
        $date = date_parse_from_format('D, d M Y H:i:s T', $this->date);
        if (!empty($date['errors'])) {
            throw new InvalidParamException('Date incorrect', 400);
        }
        return $this;
    }
    protected function initBucket()
    {
        $this->uri    = ltrim($this->uri, '/');
        $this->bucket = substr($this->uri, 0, strpos($this->uri, '/'));
        $buckets      = \Yii::$app->params['buckets'];
        if (!isset($buckets[$this->bucket])) {
            throw new InvalidParamException('Bucket not exist', 400);
        }
        $bucket                = $buckets[$this->bucket];
        $this->operator        = $bucket['operator'];
        $this->password        = md5($bucket['password']);
        $this->authParamName   = $bucket['authParamName'] ?? '';
        $this->authCallbackUrl = $bucket['authCallbackUrl'] ?? '';
        return $this;
    }
}

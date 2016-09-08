yii2 swoole verion
============================

Yii2 Advanced based on [swoole](https://github.com/swoole/swoole-src/)

Directory description
-------------------

      api/                  api entry with version control
      console/              crontab 
      common/               common logic,config,models,helpers
      vendor/               Yii Framework
      common/config         common config
      common/models         common models
      common/controllers    common base controllers
      common/helpers        common helpers
      common/service        common service
      common/vendor         third party
      swoole/               swoole server class, and yii class reload



Requirements
------------

- PHP5.4+
- MYSQL5.6+
- php extensions
* curl
* libxml
* mbstring
* mcrypt
* mysqli
* mysqlnd
* openssl
* pcre
* PDO
* pdo_mysql
* posix
* readline
* redis
* Reflection
* session
* SimpleXML
* sockets
* swoole
* sysvmsg
* sysvsem
* sysvshm
* tokenizer
* wddx
* xml
* xmlreader
* xmlwriter
* xsl
* yaml
* zlib


Usage
------------

- build swoole extension for php
- set swoole.use_namespace = On
- clone and download the code
- add runtime.php to api/web with content 
``` 
<?php
'local';
```
 local is the environment keyword, maybe dev or prod, nor beta, etc. You can copy main-dev.php to main-local.php , and params-dev.php to params-local.php.
- if you environment keyword is local, you need and main-local.php and params-local.php to common/config and api/config, if keyword is dev or other, make sure you have main-keyword.php and params-keyword.php in api/config and common/config.
- setting your environment config like mysql and redis connection information in your params-local.php'
```
<?php
return [
    'components' => [
        'db'       => [
            // must set charset for security
            'dsn'      => 'mysql:host=127.0.0.1;dbname=demo;charset=utf8',
            'username' => 'root',
            'password' => 'ENTER@123.com',
        ],
        'passport' => [
            // must set charset for security
            'dsn'      => 'mysql:host=127.0.0.1;dbname=user;port=3310;charset=utf8',
            'username' => 'user',
            'password' => 'user',
        ],
        'log'      => [
            'targets' => [
                'email' => [
                    'message' => [
                        'subject' => 'beta log alert',
                    ],
                ],
            ],
        ],
        'redis'    => [
            'hostname' => '127.0.0.1',
            'port'     => 6379,
            'database' => 0,
        ],
    ],
];

```
- default, Yii2 runtime path set to /data/logs , change it in common/config/main.php
```
'runtimePath'    => '/data/logs',
```
- cd YOUR_PATH/api/web , exec php swoole.php start
- open web browser, visit http://127.0.0.1:9501/v01/demo/index
- you can change the swoole config in params-*.php, * is your environment keyword.
- if you manually end the Swoole\Http\Response, you should throw the EndException to make sure no more output
- if you php7, you can and try catch for throwable in SwooleServer::onRequest and then remove the set_error_handler logic
- session, session class for swoole: swoole/yii/web/Session, default I have rewrite redis session class: swoole/yii/redis/Session. If you have self session handler, you can modify your code, manually add session id cookie. if you use Yii2 session component like: Yii::$app->getSession->get($key) or Yii::$app->getSession->set($key, $value) , session will automate add session id cookie. If not, you must open session manually by call Yii::$app->getSession()->open(). Note: $_SESSION['XXX'] = 'XXX'; do not start the session automate. 

Contribution
------------
Your contribution to Yii2-swoole development is very welcome!

You may contribute in the following ways:

- Repost issues and feedback
- Submit fixes, features via Pull Request
- Write/polish documentation

License
------------
Apache License Version 2.0


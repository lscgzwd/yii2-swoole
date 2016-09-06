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
* bcmath
* bz2
* calendar
* Core
* ctype
* curl
* date
* dom
* exif
* fileinfo
* filter
* ftp
* gd
* gettext
* hash
* iconv
* igbinary
* json
* libxml
* mbstring
* mcrypt
* mysqli
* mysqlnd
* openssl
* pcntl
* pcre
* PDO
* pdo_mysql
* pdo_sqlite
* Phar
* posix
* readline
* redis
* Reflection
* session
* shmop
* SimpleXML
* soap
* sockets
* SPL
* sqlite3
* standard
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
 local is the environment keyword, maybe dev or prod, nor beta, etc.
- if you environment keyword is local, you need and main-local.php and params-local.php to common/config and api/config, if keyword is dev or other, you must make sure have main-keyword.php and params-keyword.php in api/config and common/config.
- setting your environment config like mysql and redis connection information in your params-local.php
- cd YOU_PATH/api/web , exec php swoole.php start
- goto your web browser, visit http://127.0.0.1:9501/v01/demo/index
- you can change the swoole config in params-*.php
- if you manually end the Swoole\Http\Response, you should throw the EndException to make sure no more output
- if you php7, you can and try catch for throwable in SwooleServer::onRequest and then remove the set_error_handler logic

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


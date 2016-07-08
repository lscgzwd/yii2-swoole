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
- php扩展列表：
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
* Zend OPcache
* zlib


Usage
------------

- clone and download the code
- add runtime.php to api/web with content return 'local';// local is the enviroment keyword, maybe dev or prod, etc.
- if you enviroment is local, you need and main-local.php and params-local.php to common/config and api/config
- cd YOU_PATH/api/web , exec php swoole.php start
- goto your web browser, visit http://127.0.0.1:9501/v01/demo/index
- you can change the swoole config in params-*.php

Contribution
------------
Your contribution to Swoole development is very welcome!

You may contribute in the following ways:

-Repost issues and feedback
-Submit fixes, features via Pull Request
-Write/polish documentation

License
------------
Apache License Version 2.0


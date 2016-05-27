企业版swoole版本
============================

基于原来的目录结构把所有的入口都放在了一起，并且逻辑分层不清楚，特重新整理了新的目录结构，并且在部分业务上引入了swoole扩展支持

目录结构说明
-------------------

      api/                  端，交易，支付，客服等第三方接口入口
      app/                  企业版网站入口
      backend/              超级管理后台入口
      console/              定时任务，命令行入口
      common/               公用逻辑层，Model层，服务请求层
      vendor/               Yii Framework
      common/config         公共配置
      common/models         公共Model
      common/controllers    控制器基类
      common/libraries      公共逻辑层
      common/helpers        公共助手类
      common/service        公共第三方请求层，比如我们请求支付，交易的服务封装，
      common/vendor         公共第三方类库，比如PHPExcel
      common/mail           公共邮件模板



系统需求
------------

- PHP5.4
- MYSQL5.6
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

[Zend Modules]
Zend OPcache


安装运行
------------


- fork一份本代码到自己的命名空间
- 添加自己的ssh key到git
- git clone 检出自己命名空间的代码到本地，比如enterpise-swoole目录
- 配置nginx,增加server节点，每个入口一个server节点，
- nginx root指向每个节点的web目录，比如app的，则为  PATH_TO_CODE\enterprise-swoole\app\web;
- 在web目录下新建runtime.php文件，并且返回local字符串，比如 return 'local';
- 分别在common\config目录和项目目录\config下建立main-local.php params-local.php文件
- 重启nginx,输入你配置的域名即可以访问对应项目
- 请注意，不能直接修改dev,beta,prod,stress,docker等配置文件，除了新加项，本地需要改配置的，统一走local文件 ，local文件已经加入git忽略，大家随便改，不会提交



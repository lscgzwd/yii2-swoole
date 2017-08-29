# [yiiswoole](https://github.com/lsccgzwd/yii2-swoole)
An example for yii2 run with swoole
# Requirements
* swoole 1.9.18
* briarbear 1.0.0
* yiisoft/yii2 2.0.8
* php 7.0

# Feature：
* Elastic Job 
* Dubbo (developing)
* Compute Cluster (developing)

# Usage
* composer install briarbear/yii2
* modify the examples\yii2\storage\web\start.php, correct the path. You can give $config to the run method or set the property of Start class.
* $config must have server and logger key. view examples\yii2\storage\config\params.php 
* if your want to use crontab, must config the zookeeperHost, otherwise set START_CRONTAB to false
* by default we use config separate, you should mkdir -p /data/conf/qiye then cp the ini file to that directory
* run php examples/yii2/storage/web/start.php restart to start the service 
* curl -d "" http://127.0.0.1:9502/v1/demo/index

# 使用方法
* 使用composer安装本程序，package:  briarbear/yii2
* 修改examples里面的试例
* Start类支持直接配置属性，自动加载Yii2的配置文件，也可以通过run方法的参数传入配置数组
* 如果要使用定时任务功能，需要配置zookeeper, 否则把常量START_CRONTAB 设置成false
* 我们线上使用了配置分离，你可能需要将ini文件夹中的配置文件拷贝到对应目录
* 执行php start.php restart 启动服务

 
# Upgrade
* 2017-08-29 support composer

# Contact
* QQ: 1474212
* EMAIL: lscgzwd@gmail.com
* 欢迎直接提issues, welcome pull request and submit issues


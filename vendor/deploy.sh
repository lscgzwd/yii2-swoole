#########################################################################
# File Name :    deploy.sh
# Author :       panyu
# mail :         panyu@jiedaibao.com
# Last_Modified: 2016-05-10 17:58:12
#########################################################################
#!/bin/bash
#FE部分
set -x
docker-compose down
replaceport=`get-port.sh`
hostname=$1
#################fe build##################
cd fe
npm install
bower install --allow-root
gulp dest
cd ..
cp ./fe/dest/index.html ./rd/app/views/site
cp ./fe/dest/registrationAgreement.html ./rd/app/web
cp ./fe/dest/payAgreement.html ./rd/app/web
if [ ! -d ./rd/app/views/static ];then
  mkdir -p ./rd/app/views/static
fi
cp ./fe/dest/*.css ./rd/app/views/static/
cp ./fe/dest/*.js ./rd/app/views/static/
cp -r ./fe/dest/images ./rd/app/views/static/
cp -r ./fe/dest/fonts ./rd/app/views/static/
#################   rd file ###############
echo "<?php
        return \"dev\";" > ./rd/app/web/runtime.php
#################nginx file ###############
cp /etc/nginx/dev.d/env.base /etc/nginx/dev.d/${hostname}.conf
sed -i "s/env_server_name/${hostname}/g" `grep env_server_name -rl /etc/nginx/dev.d/${hostname}.conf`
sed -i "s/env_port/${replaceport}/g" `grep env_port -rl /etc/nginx/dev.d/${hostname}.conf`
echo "port:"${replaceport}
/usr/sbin/nginx -s reload
#################docker file###############
mkdir -p logs
chmod -R 0777 logs
chmod -R 0777 rd
echo "enterprise:
  restart: always
  image: jdocker.me/centos-nginx-php-qiye
  ports:
    - "$replaceport:80"
  volumes:
    - ./rd:/data/apps
    - ./logs:/data/logs" > docker-compose.yml
docker-compose up -d
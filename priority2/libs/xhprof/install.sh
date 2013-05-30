#!/bin/bash

ln -s `pwd`/xhprof_html /var/www/xhprof_html

mkdir /tmp/xhprof/
cd /tmp/xhprof/
pecl download xhprof-0.9.2
tar -xvf xhprof-0.9.2.tgz
cd xhprof-0.9.2/extension
phpize
#./configure --with-php-config=
./configure

make
make install

mkdir /var/log/xhprof/
chmod 0777 /var/log/xhprof/

rm -rf /tmp/xhprof/

invoke-rc.d apache2 restart
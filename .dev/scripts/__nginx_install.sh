#!/bin/bash

host="$1"
if [ -z $host ]; then
    echo "== ERROR: please provide host as first argument to this script";
    exit;
else
    echo "== Starting install for "$host
fi
# -2 means using protocol v2 only, -4 IPv4
myssh="ssh -2 -4 root@$host"

nginx_version="1.5.3"
if [ "$override_nginx_version" ]; then
	nginx_version="$override_nginx_version"
fi

$myssh '
nginx_version='$nginx_version'

function require_packages {
	packages="$1"
	if [ -z "$packages" ]; then
		echo "No packages given to ";
	fi
	to_install=""
	installed_packages="@"$(dpkg-query -W -f "\${status} \${package}\n" $packages 2>/dev/null | grep "install ok installed" | cut -c 22- | tr "\n" "@")"@"
	for package in $packages; do
		exists=$(echo $installed_packages | fgrep "@$package@")
		if [ -z "$exists" ]; then
			to_install="$to_install $package"
		else
			echo "package exists: $package"
		fi
	done
	if [ ! -z "$to_install" ]; then
		(echo "y" | apt-get install -q -m -y $to_install)
	fi
}

echo "== Checking if ubuntu installed=="
ver=`cat /etc/lsb-release`;
if [[ $ver != *[Uu]buntu* ]]; then
    echo "== ERROR: No Ubuntu, stopping"
    exit;
fi

echo "== Upgrade software"
( apt-get update && apt-get upgrade -y )
( apt-get autoclean -y )

echo "== Install needed software"
packages="
curl
wget
htop
tcpick
bwm-ng
tree
libpcre3-dev
libssl-dev
build-essential
libgeoip-dev
php-pear
php5-dev
php5-cli
php5-cgi
php5-xcache
php5-sybase
php5-mysql
php5-curl
php5-memcache
php5-memcached
php5-imagick
memcached
unzip
git-core
ack-grep
colordiff
automake
libtool
lua5.1
liblua5.1-dev
luajit
libluajit-5.1-dev
luarocks
python-software-properties
libprotobuf-c0
libprotobuf-dev
protobuf-compiler
libcurl4-openssl-dev
libboost-dev
libboost-all-dev
uuid-dev
libpam0g-dev
gettext
intltool
"
require_packages "$packages"

mkdir /root/install -p
cd /root/install

echo "== Downloading nginx=="

source="nginx-"$nginx_version".tar.gz"
if [ ! -f "$source" ]; then
	wget -r -nd http://nginx.org/download/nginx-$nginx_version.tar.gz
fi
tar -xvzf nginx-$nginx_version.tar.gz
rm -rf /root/install/nginx
mv nginx-$nginx_version nginx
rm -f ./config

echo "== Adding nginx modules"

git_repos="
https://github.com/simpl/ngx_devel_kit.git
https://github.com/giom/nginx_accept_language_module.git
https://github.com/FRiCKLE/ngx_cache_purge.git
https://github.com/FRiCKLE/ngx_coolkit.git
https://github.com/chaoslawful/lua-nginx-module.git
#https://github.com/chaoslawful/drizzle-nginx-module.git
https://github.com/calio/form-input-nginx-module.git
https://github.com/agentzh/set-misc-nginx-module.git
https://github.com/agentzh/echo-nginx-module.git
https://github.com/agentzh/memc-nginx-module.git
https://github.com/agentzh/rds-json-nginx-module.git
https://github.com/agentzh/redis2-nginx-module.git
https://github.com/agentzh/srcache-nginx-module.git
https://github.com/agentzh/headers-more-nginx-module.git
https://github.com/agentzh/xss-nginx-module.git
https://github.com/agentzh/array-var-nginx-module.git
https://github.com/agentzh/encrypted-session-nginx-module.git
"
# Example with tag: https://github.com/chaoslawful/lua-nginx-module.git;v0.4.1
for git_repo in $git_repos; do
    echo $git_repo
    dest=$(basename "$git_repo" | awk -F ";" "{print \$1}" | sed "s/.git//")
    tag=$(basename "$git_repo" | awk -F ";" "{print \$2}")
    git_repo=$(echo "$git_repo" | awk -F ";" "{print \$1}")
    if [ -f "./$dest/.git/config" ]; then
        (cd $dest && git pull origin master)
    else
        rm -rf $dest
        mkdir -p $dest
        git clone $git_repo $dest
    fi
    if [ -n "$tag" ]; then
        (cd $dest && git checkout $tag; git checkout -b $tag)
    else
        (cd $dest && git reset --hard && git checkout master)
    fi
done

dest="./sticky-upstream-nginx-module"
if [ -d $dest ]; then
	svn up $dest
else
	rm -rf $dest && mkdir -p $dest
	svn co http://nginx-sticky-module.googlecode.com/svn/trunk/ $dest
fi

echo "== Installing Lua modules =="

#lua_modules_path="/usr/share/lua/5.1"
#mkdir -p $lua_modules_path
#lua_modules="
#https://raw.github.com/agentzh/lua-resty-mysql/master/lib/resty/mysql.lua;resty/
#https://raw.github.com/agentzh/lua-resty-memcached/master/lib/resty/memcached.lua;resty/
#https://raw.github.com/agentzh/lua-resty-redis/master/lib/resty/redis.lua;resty/
#https://raw.github.com/agentzh/lua-resty-upload/master/lib/resty/upload.lua;resty/
#https://raw.github.com/agentzh/lua-resty-dns/master/lib/resty/dns/resolver.lua;resty/dns/
#"
#for lm in $lua_modules; do
#	echo $lm
#	dest=$lua_modules_path"/"$(echo $lm | awk -F ";" "{print \$2}")
#	mkdir -p $dest
#	lm_url=$(echo $lm | awk -F ";" "{print \$1}")
#	lm_name=$(basename $lm_url)
#	wget "$lm_url" -O $dest""$lm_name
#done

#rocks_modules="
#luabitop
#lua-cjson
#"
#for rlm in $rocks_modules; do
#    exists=$(luarocks list | fgrep "$rlm")
#    if [ -z "$exists" ]; then
#        echo "luarocks install "$rlm
#        luarocks install $rlm
#    fi
#done

add-apt-repository ppa:nginx-openresty/development
apt-get update && apt-get install -y liblua5.1-resty-* liblua5.1-cjson0

echo "== Configuring nginx=="

#### These modules adding order is IMPORTANT! For filter modules position in filtering chain affects a lot. The correct configure adding order is:.
# ngx_devel_kit
# set-misc-nginx-module
# ngx_http_auth_request_module
# echo-nginx-module
# memc-nginx-module
# lua-nginx-module (i.e. this module)
# headers-more-nginx-module
# srcache-nginx-module
# drizzle-nginx-module
# rds-json-nginx-module

# tell nginx build system where to find LuaJIT:
# Temporary off for development, but should be enabled for production
#export LUAJIT_LIB=/usr/lib
#export LUAJIT_INC=/usr/include/luajit-2.0

cd /root/install/nginx
./configure \
--with-http_realip_module \
--with-http_gzip_static_module \
--with-http_stub_status_module \
--with-http_geoip_module \
--with-http_ssl_module \
--with-http_sub_module \
--with-file-aio \
--add-module=/root/install/ngx_devel_kit \
--add-module=/root/install/nginx_accept_language_module \
--add-module=/root/install/set-misc-nginx-module \
--add-module=/root/install/echo-nginx-module \
--add-module=/root/install/memc-nginx-module \
--add-module=/root/install/ngx_cache_purge \
--add-module=/root/install/lua-nginx-module \
--add-module=/root/install/headers-more-nginx-module \
--add-module=/root/install/xss-nginx-module \
--add-module=/root/install/redis2-nginx-module \
--add-module=/root/install/form-input-nginx-module \
--add-module=/root/install/encrypted-session-nginx-module \
--add-module=/root/install/srcache-nginx-module \
--add-module=/root/install/rds-json-nginx-module

##--add-module=/root/install/drizzle-nginx-module \

echo "== Compiling nginx=="

make && make install

mkdir /usr/local/nginx -p

mkdir /usr/local/nginx/client_body_temp -p
mkdir /usr/local/nginx/proxy_temp -p
mkdir /usr/local/nginx/logs -p
chmod 777 /usr/local/nginx/logs
chmod 777 /usr/local/nginx/client_body_temp
chmod 777 /usr/local/nginx/proxy_temp

echo "== Nginx upstart job setup"

echo "# nginx

description \"nginx http daemon\"

start on (filesystem and net-device-up IFACE=lo)
stop on runlevel [!2345]

env DAEMON=/usr/local/nginx/sbin/nginx
env PID=/usr/local/nginx/logs/nginx.pid

console output

expect fork
respawn
respawn limit 10 5
#oom never
#normal exit 0

pre-start script
	\$DAEMON -t
	if [ \$? -ne 0 ]
		then exit \$?
	fi
end script

exec \$DAEMON
" > /etc/init/nginx.conf

ln -s  /lib/init/upstart-job -T /etc/init.d/nginx
touch /etc/default/nginx

 /usr/local/nginx/sbin/nginx -v

# Start the servie if not done yet
service nginx start
# Add nginx service to startup
update-rc.d nginx defaults
'

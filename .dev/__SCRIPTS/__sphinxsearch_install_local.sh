#!/bin/bash

sphinxsearch_version="2.0.8-release"

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

require_packages "libmysqlclient16-dev g++"

mkdir -p /root/install/sphinx
cd /root/install/sphinx

rm -rf ./sphinx-$sphinxsearch_version.tar.gz
wget http://sphinxsearch.com/files/sphinx-$sphinxsearch_version.tar.gz
tar -xvzf ./sphinx-$sphinxsearch_version.tar.gz

rm -rf ./libstemmer_c.tgz
rm -rf ./libstemmer_c/
wget http://snowball.tartarus.org/dist/libstemmer_c.tgz
tar -xvzf ./libstemmer_c.tgz
cp -rf ./libstemmer_c/* ./sphinx-$sphinxsearch_version/libstemmer_c/

cd ./sphinx-$sphinxsearch_version
./configure --prefix=/usr/local/sphinx --with-libstemmer --enable-id64
make
make install

cd /usr/local/sphinx/
mkdir ./data
chmod 777 ./data
mkdir ./log
chmod 777 ./log

echo "
date >> /usr/local/sphinx/log/indexer.log
/usr/local/sphinx/bin/indexer --config /usr/local/sphinx/etc/sphinx.conf --all --rotate >> /usr/local/sphinx/log/indexer.log
" > /usr/local/sphinx/bin/index_all.sh
chmod +x /usr/local/sphinx/bin/index_all.sh

#	cp /home/toggle3/scripts/nginx/sphinx/start.sh /usr/local/sphinx/bin/start.sh
#	cp /home/toggle3/scripts/nginx/sphinx/sphinx.conf /usr/local/sphinx/etc/sphinx.conf

ln -sT /usr/local/sphinx/ /root/sphinx

#( /usr/local/sphinx/bin/indexer --config /usr/local/sphinx/etc/sphinx.conf --all --rotate ) &

echo "== Upstart job setup"

echo "# SphinxSearch Service

description     \"SphinxSearch Daemon\"

start on (filesystem and net-device-up IFACE=lo)
stop on runlevel [!2345]

respawn
respawn limit 10 35

# The default of 5 seconds is too low if we have rt indices and have to flush them
kill timeout 30

pre-start script
    if [ ! -f /usr/local/sphinx/etc/sphinx.conf ]; then
        logger "Please create an /usr/local/sphinx/etc/sphinx.conf configuration file."
        logger "Templates are in the /usr/local/sphinx/etc/ directory."
        exit 0
    fi
end script

exec /usr/local/sphinx/bin/searchd --nodetach

" > /etc/init/sphinxsearch.conf

ln -s  /lib/init/upstart-job -T /etc/init.d/sphinxsearch
touch /etc/default/sphinxsearch


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

echo "== Copy authorized keys"
cat ./_authorized_keys.txt | $myssh "mkdir -p /root/.ssh/ && chmod 700 /root/.ssh/; cat > /root/.ssh/authorized_keys; chmod 400 /root/.ssh/authorized_keys"
echo "== Copy SSH key"
cat ../yfix_team.pem | $myssh "mkdir -p /root/.ssh/ && chmod 700 /root/.ssh/; cat > /root/.ssh/yfix_team.pem; chmod 400 /root/.ssh/yfix_team.pem"

$myssh '

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
# needed to add support for apt-add-repository
require_packages "python-software-properties"

echo "== MOTD (Message of the day), thus speeding up ssh and common login"
regex="s/^session\s+optional/#session optional/gi;"
perl -pi -e "$regex" /etc/pam.d/login && perl -pi -e "$regex" /etc/pam.d/sshd

echo "== Remove landscape monitoring"
( apt-get remove landscape-client landscape-common -y )

echo "== Midnight Commander fixes"
require_packages "mc dpkg-dev"
( cd /tmp && apt-get source mc && cp mc-4.7.0/misc/filehighlight.ini /etc/mc && rm -rf mc* )

echo "==Change params inside /root/.mc/ini"
if [ ! -e /root/.mc/ ]; then mkdir -p /root/.mc/; fi
touch /root/.mc/ini
params_to_change="
editor_word_wrap_line_length=72
editor_tab_spacing=4
editor_fill_tabs_with_spaces=0
editor_return_does_auto_indent=0
editor_backspace_through_tabs=0
editor_fake_half_tabs=0
editor_option_save_mode=0
editor_option_save_position=1
editor_option_auto_para_formatting=0
editor_option_typewriter_wrap=0
editor_edit_confirm_save=1
editor_syntax_highlighting=1
editor_persistent_selections=1
editor_cursor_beyond_eol=1
editor_visible_tabs=0
editor_visible_spaces=0
editor_line_state=0
editor_simple_statusbar=0
editor_check_new_line=0
"
for p in $params_to_change; do
    # trick: change internal field separator (IFS)
    IFS="=" read -a ARRAY <<< "$p" # one-line solution
    k="${ARRAY[0]}"
    v="${ARRAY[1]}"
	echo $k"="$v
    sed "s/\("$k"\)=.*/\1="$v"/g" -i /root/.mc/ini
done;

echo "== Apparmor remove"
( aa-complain /etc/apparmor.d/* ; invoke-rc.d apparmor stop ; /etc/init.d/apparmor teardown stop ; update-rc.d -f apparmor remove )

echo "== NTP daemon setup"
require_packages "ntp"
( service ntp stop && ntpdate pool.ntp.org && service ntp restart )

echo "== Completely disable SWAP"
swapoff -a

echo "== xtrabackup install"
echo "deb http://repo.percona.com/apt precise main
deb-src http://repo.percona.com/apt precise main
" > /etc/apt/sources.list.d/percona.list;
gpg --keyserver  hkp://keys.gnupg.net --recv-keys 1C4CBDCDCD2EFD2A;
gpg -a --export CD2EFD2A | sudo apt-key add -;
( apt-get update && apt-get upgrade -y )
require_packages "xtrabackup percona-toolkit"

echo "== Enable bash completion"
sed "/if \[ -f \/etc\/bash_completion/,/fi/ d" -i /root/.bashrc
echo "if [ -f /etc/bash_completion ] && ! shopt -oq posix; then
    . /etc/bash_completion
fi" >> /root/.bashrc

echo "== GeoIP install"
require_packages "geoip-bin geoip-database"
echo "
UserId 55696
LicenseKey eMXdncDRUSTi
ProductIds 106
" > /etc/GeoIP.conf
geoipupdate
ls -l /usr/share/GeoIP/GeoIP.dat

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
geoip-bin
geoip-database
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
php5-geoip
php5-imagick
php5-fpm
memcached
unzip
git-core
ack-grep
colordiff
lzop
gawk
sysv-rc-conf
"
require_packages "$packages"
'

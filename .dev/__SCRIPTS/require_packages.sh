#!/bin/bash

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
	if [ ! $? -eq 0 ]; then
		echo "Catched error, trying one by one"
		installed_packages="@"$(dpkg-query -W -f "\${status} \${package}\n" $packages 2>/dev/null | grep "install ok installed" | cut -c 22- | tr "\n" "@")"@"
		for package in $to_install; do
			exists=$(echo $installed_packages | fgrep "@$package@")
			if [ -z "$exists" ]; then
				(echo "y" | apt-get install -q -m -y $package)
			fi
		done
	fi
}

# Example usage of programs that were installed automatically by require_packages:
# apt-get remove -y aria2 pbzip2 apt-fast
# require_packages "aria2 pbzip2 apt-fast"
# aria2c -v
# echo "#####"
# pbzip2 -h

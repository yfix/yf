docker-compose exec php bash -c '
converter_path="/var/www/default/yf/.dev/useful/php-short-array-syntax-converter/convert.php"
for f in $(find /var/www/default/{admin_modules,classes,config,plugins,scripts,share,tests} -name "*.php"); do
	echo $f;
	php $converter_path -w $f;
done
'
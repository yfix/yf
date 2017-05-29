converter_path="/www/yf/.dev/useful/php-short-array-syntax-converter/convert.php"
for f in $(find ../{admin_modules,classes,config,plugins,scripts,share,tests} -name "*.php"); do
	echo $f;
	php $converter_path -w $f;
done

composer-install:
	composer install -vvv

composer-update:
	composer update -vvv

composer-install-prod:
	composer install --no-dev -vvv

composer-update-prod:
	composer update --no-dev -vvv

parallel-lint:
	./vendor/bin/parallel-lint -e php --exclude vendor --exclude .dev .

phpunit-tests:
	(cd ./.dev/tests/ && ../../vendor/bin/phpunit ./)

paratests:
	(cd ./.dev/tests/ && ../../vendor/bin/paratest -p4 --colors --stop-on-failure --configuration ./phpunit.xml --log-junit ./reports/logfile.xml ./)

php-cs-fixer:
	./vendor/bin/php-cs-fixer --verbose --show-progress=estimating fix --verbose --config=.php_cs ./

test: parallel-lint php-cs-fixer phpunit-tests

SHELL  := /bin/bash
PHP_CONTAINER := yfixnet-php80-1
DIR_YF := /var/www/vendor/yf31
DIR_YF_TESTS := $(DIR_YF)/.dev/tests/
DE := docker exec -it -e "TERM=xterm-256color" $(PHP_CONTAINER)

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
php-cs-fixer-check:
	./vendor/bin/php-cs-fixer --verbose --show-progress=dots check
php-cs-fixer-fix:
	./vendor/bin/php-cs-fixer --verbose --show-progress=dots fix

php-bash: docker-bash
docker-bash:
	$(DE) bash
docker-composer-install:
	$(DE) bash -c 'cd $(DIR_WWW) && make composer-install'
docker-phpunit:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-all'
docker-phpunit-unit:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-unit'
docker-phpunit-functional:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-functional'
docker-phpunit-help:
	$(DE) bash -c 'phpunit -h'
docker-parallel-lint:
	$(DE) bash -c 'cd $(DIR_YF) && make parallel-lint'
docker-php-cs-fixer-check:
	$(DE) bash -c 'cd $(DIR_YF) && make php-cs-fixer-check'
docker-php-cs-fixer-fix:
	$(DE) bash -c 'cd $(DIR_YF) && make php-cs-fixer-fix'

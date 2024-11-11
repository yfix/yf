SHELL  := /bin/bash
PHP_CONTAINER := yfixnet-php80-1
DIR_YF := /var/www/vendor/yf31
DIR_YF_TESTS := $(DIR_YF)/.dev/tests/

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
	./vendor/bin/php-cs-fixer --verbose --show-progress=dots check
test: parallel-lint php-cs-fixer phpunit-tests

php-bash: docker-bash
docker-bash:
	docker exec -it $(PHP_CONTAINER) bash
docker-phpunit:
	docker exec $(PHP_CONTAINER) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-all'
docker-phpunit-unit:
	docker exec $(PHP_CONTAINER) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-unit'
docker-phpunit-functional:
	docker exec $(PHP_CONTAINER) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-functional'
docker-parallel-lint:
	docker exec $(PHP_CONTAINER) bash -c 'cd $(DIR_YF) && make parallel-lint'
docker-php-cs-fixer:
	docker exec $(PHP_CONTAINER) bash -c 'cd $(DIR_YF) && make php-cs-fixer'
docker-test:
	docker exec $(PHP_CONTAINER) bash -c 'cd $(DIR_YF) && make test'

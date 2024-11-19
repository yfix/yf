SHELL  := /bin/bash
PHP_CONTAINER := yfixnet-php80-1
DIR_TESTS := .dev/tests/
DOCKER_DIR_YF := /var/www/vendor/yf31
DOCKER_DIR_YF_TESTS := $(DOCKER_DIR_YF)/$(DIR_TESTS)
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
	$(DE) bash -c 'cd $(DOCKER_DIR_YF) && make composer-install'
docker-phpunit:
	cd $(DIR_TESTS) && make docker-phpunit-all
docker-phpunit-unit:
	cd $(DIR_TESTS) && make docker-phpunit-unit
docker-phpunit-functional:
	cd $(DIR_TESTS) && make docker-phpunit-functional
docker-phpunit-help:
	$(DE) bash -c 'phpunit -h'
docker-parallel-lint:
	$(DE) bash -c 'cd $(DOCKER_DIR_YF) && make parallel-lint'
docker-php-cs-fixer-check:
	$(DE) bash -c 'cd $(DOCKER_DIR_YF) && make php-cs-fixer-check'
docker-php-cs-fixer-fix:
	$(DE) bash -c 'cd $(DOCKER_DIR_YF) && make php-cs-fixer-fix'

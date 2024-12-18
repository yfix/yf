PHPUNIT := phpunit
# PHPUNIT := ../../vendor/bin/phpunit
PARALINT := ../../vendor/bin/parallel-lint
# PHPUNIT_OPTS := -d memory_limit=1024M
SHELL  := /bin/bash
PHP_CONTAINER := yfixnet-php80-1
DIR_YF := /var/www/yf38
DOCKER_TESTS := $(DIR_YF)/.dev/tests/
DE := docker exec -it -e "TERM=xterm-256color" -w "$(DOCKER_TESTS)" $(PHP_CONTAINER)

tests: phpunit-all
phpunit-all:
	$(PHPUNIT) $(PHPUNIT_OPTS) ./
phpunit-one-by-one:
	for f in $$(find . -type f -name "*_test.Test.php"); do echo $$f; $(PHPUNIT) $$f; done
phpunit-all-no-integration:
	$(PHPUNIT) --testsuite all $(PHPUNIT_OPTS)
phpunit-unit-all:
	$(PHPUNIT) --testsuite unit-all $(PHPUNIT_OPTS)
phpunit-unit-db:
	$(PHPUNIT) ./unit/db/ $(PHPUNIT_OPTS)
phpunit-functional:
	$(PHPUNIT) ./functional/ $(PHPUNIT_OPTS)
phpunit-functional-cache:
	$(PHPUNIT) ./functional/cache/ $(PHPUNIT_OPTS)
phpunit-functional-db:
	$(PHPUNIT) ./functional/db/ $(PHPUNIT_OPTS)
phpunit-functional-model:
	$(PHPUNIT) ./functional/model/ $(PHPUNIT_OPTS)
phpunit-functional-no-db:
	$(PHPUNIT) --testsuite functional-no-db $(PHPUNIT_OPTS)
phpunit-integration:
	$(PHPUNIT) ./integration/ $(PHPUNIT_OPTS)
phpunit-cache:
	$(PHPUNIT) --testsuite functional-only-cache $(PHPUNIT_OPTS)
phpunit-db:
	$(PHPUNIT) --testsuite db-all $(PHPUNIT_OPTS)
phpunit-tpl:
	$(PHPUNIT) --testsuite unit-only-tpl $(PHPUNIT_OPTS)
phpunit-functions:
	$(PHPUNIT) --testsuite unit-only-functions $(PHPUNIT_OPTS)
phpunit-plugins:
	$(PHPUNIT) --testsuite plugins-all $(PHPUNIT_OPTS)
phpunit-rewrite:
	$(PHPUNIT) ./rewrite/ $(PHPUNIT_OPTS)
phpunit-tpl-compiled:
	for f in $$(find . -type f -name "*_compiled_test.Test.php"); do echo $$f; $(PHPUNIT) $$f; done

php-lint-all:
	cd ../../ && for f in $$(find . -type f -name "*.php" | grep -v /vendor/); do php -l $$f; done
php-lint-tests:
	for f in $$(find . -type f -name "*_test.Test.php"); do php -l $$f; done

php-parallel-lint-all:
	$(PARALINT) -e php --colors --exclude ../../vendor ../../
php-parallel-lint-tests:
	$(PARALINT) -e php --colors --exclude ../../vendor --exclude .tmp .

php-bash: docker-bash
docker-bash:
	$(DE) bash
docker-phpunit-help:
	$(DE) bash -c 'phpunit -h'
docker-phpunit-all:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-all'
docker-phpunit-all-no-integration:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-all-no-integration'
docker-phpunit-unit-all:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-unit-all'
docker-phpunit-unit-db:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-unit-db'
docker-phpunit-functional:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-functional'
docker-phpunit-functional-cache:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-functional-cache'
docker-phpunit-functional-db:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-functional-db'
docker-phpunit-functional-model:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-functional-model'
docker-phpunit-functional-no-db:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-functional-no-db'
docker-phpunit-integration:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-integration'
docker-phpunit-cache:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-cache'
docker-phpunit-db:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-db'
docker-phpunit-tpl:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-tpl'
docker-phpunit-tpl-compiled:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-tpl-compiled'
docker-phpunit-functions:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-functions'
docker-phpunit-plugins:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-plugins'
docker-phpunit-rewrite:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-rewrite'
docker-phpunit-one-by-one:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make phpunit-one-by-one'
docker-php-lint-all:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make php-lint-all'
docker-php-parallel-lint-all:
	$(DE) bash -c 'cd $(DOCKER_TESTS) && make php-parallel-lint-all'

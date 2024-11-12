PHPUNIT := ../../vendor/bin/phpunit
PARALINT := ../../vendor/bin/parallel-lint
# PHPUNIT_OPTS := -d memory_limit=1024M
SHELL  := /bin/bash
PHP_CONTAINER := yfixnet-php80-1
DIR_YF := /var/www/vendor/yf31
DIR_YF_TESTS := $(DIR_YF)/.dev/tests/
DE := docker exec -it -e "TERM=xterm-256color" $(PHP_CONTAINER)

tests: phpunit-all
phpunit-all:
	$(PHPUNIT) $(PHPUNIT_OPTS) ./
phpunit-each:
	for f in $$(find . -type f -name "*_test.Test.php"); do echo $$f; $(PHPUNIT) $$f; done
phpunit-unit:
	$(PHPUNIT) --testsuite unit-all $(PHPUNIT_OPTS)
#	$(PHPUNIT) ./unit/db/ $(PHPUNIT_OPTS)
phpunit-functional:
	$(PHPUNIT) ./functional/ $(PHPUNIT_OPTS)
#	$(PHPUNIT) ./functional/cache/ $(PHPUNIT_OPTS)
#	$(PHPUNIT) ./functional/db/ $(PHPUNIT_OPTS)
#	$(PHPUNIT) ./functional/model/ $(PHPUNIT_OPTS)
#	$(PHPUNIT) --testsuite functional-all $(PHPUNIT_OPTS)
phpunit-integration:
	$(PHPUNIT) ./integration/ $(PHPUNIT_OPTS)
phpunit-cache:
	$(PHPUNIT) --testsuite functional-only-cache $(PHPUNIT_OPTS)
phpunit-db:
	$(PHPUNIT) --testsuite db-all $(PHPUNIT_OPTS)
phpunit-tpl:
	$(PHPUNIT) --testsuite unit-only-tpl $(PHPUNIT_OPTS)
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
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-all'
docker-phpunit-unit:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-unit'
docker-phpunit-functional:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-functional'
docker-phpunit-integration:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-integration'
docker-phpunit-cache:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-cache'
docker-phpunit-db:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-db'
docker-phpunit-tpl:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make phpunit-tpl'
docker-php-lint-all:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make php-lint-all'
docker-php-lint-all:
	$(DE) bash -c 'cd $(DIR_YF_TESTS) && make php-lint-all'

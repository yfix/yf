PHPUNIT := ../../vendor/bin/phpunit
CODECEPT := ../../vendor/bin/codecept
PARALINT := ../../vendor/bin/parallel-lint
PHPUNIT_OPTS := "-d memory_limit=1024M"

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
phpunit-plugins:
	$(PHPUNIT) --testsuite plugins-all $(PHPUNIT_OPTS)
phpunit-tpl:
	$(PHPUNIT) --testsuite unit-only-tpl $(PHPUNIT_OPTS)
phpunit-tpl-compiled:
	for f in $$(find . -type f -name "*_compiled_test.Test.php"); do echo $$f; $(PHPUNIT) $$f; done

codecept-all:
	$(CODECEPT) run
codecept-acceptance:
	$(CODECEPT) run acceptance
codecept-functional:
	$(CODECEPT) run functional
codecept-unit:
	$(CODECEPT) run unit
codecept-each: codecept-acceptance-each codecept-functional-each codecept-unit-each
codecept-acceptance-each:
	for f in $$(find ./acceptance -type f -name '*Cept.php'); do echo $$f; $(CODECEPT) run acceptance $$f; done
codecept-functional-each:
	for f in $$(find ./functional -type f -name '*Cest.php'); do echo $$f; $(CODECEPT) run functional $$f; done
codecept-unit-each:
	for f in $$(find ./unit -type f -name '*Test.php'); do echo $$f; $(CODECEPT) run unit $$f; done

php-lint-all:
	cd ../../ && for f in $$(find . -type f -name "*.php" | grep -v /vendor/); do php -l $$f; done
php-lint-tests:
	for f in $$(find . -type f -name "*_test.Test.php"); do php -l $$f; done

php-parallel-lint-all:
	$(PARALINT) -e php --colors --exclude ../../vendor ../../
php-parallel-lint-tests:
	$(PARALINT) -e php --colors --exclude ../../vendor --exclude .tmp .

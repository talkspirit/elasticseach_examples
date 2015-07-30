
tests:
	@vendor/bin/phpunit tests/

ci_tests:
	composer.phar install
	make tests

.PHONY: tests

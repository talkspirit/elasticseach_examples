
tests:
	@vendor/bin/phpunit tests/

ci_tests:
	composer install
	make tests

.PHONY: tests

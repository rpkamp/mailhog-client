# List available commands
help:
	@awk '/^#/{c=substr($$0,3);next}c&&/^[[:alpha:]][[:alnum:]_-]+:/{print substr($$1,1,index($$1,":")),c}1{c=0}' $(MAKEFILE_LIST) | column -s: -t

# Run all tests
test: lint code-style unit-tests phpstan composer-require-checker

# Lint all php files
lint:
	vendor/bin/parallel-lint --exclude vendor/ .

# Check code for style problems
code-style: phpmd phpcs

# Check code for design problems
phpmd:
	vendor/bin/phpmd src/ xml phpmd.xml --suffixes php

# Check code adheres to PSR-2
phpcs:
	vendor/bin/phpcs

# Run unit tests
unit-tests:
ifeq ($(CI),true)
	vendor/bin/phpunit --testdox -v --coverage-clover=coverage.xml
else
	vendor/bin/phpunit --testdox -v
endif

phpstan:
	vendor/bin/phpstan analyze -c phpstan.neon

composer-require-checker:
	vendor/bin/composer-require-checker

.PHONY: help test lint code-style phpmd phpcs unit-tests phpstan
.PHONY: composer-require-checker

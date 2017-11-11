# List available commands
help:
	@awk '/^#/{c=substr($$0,3);next}c&&/^[[:alpha:]][[:alnum:]_-]+:/{print substr($$1,1,index($$1,":")),c}1{c=0}' $(MAKEFILE_LIST) | column -s: -t

# Run all tests
test: lint code-style unit-tests

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
	vendor/bin/phpcs --standard=PSR2 src/

# Run unit tests
unit-tests:
	vendor/bin/phpunit

.PHONY: help test lint code-style phpmd phpcs unit-tests

# Mailhog API Client for PHP [![CircleCI](https://circleci.com/gh/rpkamp/mailhog-client/tree/master.svg?style=svg)](https://circleci.com/gh/rpkamp/mailhog-client/tree/master)

A simple PHP (7.1+) client for [Mailhog][mailhog].

## Design Goals

- As little dependencies as possible
- Simple single client for both Mailhog V1 API as well as Mailhog V2 API
- Integration tests on all endpoints, both happy path and failure paths

## Installation

This package does not require any specific HTTP client implementation, but is based on [HTTPlug][httplug], so you can inject your own HTTP client of choice. So you when you install this library make sure you either already have an HTTP client installed, or install one at the same time as installing this library, otherwise installation will fail.

```bash
composer require rpkamp/mailhog-client <your-http-client-of-choice>
```

For more information please refer to the [HTTPlug documentation for Library Users][httplug-docs].

## Usage

```php
<?php

use rpkamp\Mailhog\MailhogClient;

$client = new MailhogClient(new SomeHttpClient(), new SomeRequestFactory(), 'http://my.mailhog.host:port/');
```

Where `SomeHttpClient` is a class that implements `Http\Client\HttpClient` from HTTPlug and `SomeMessageFactory` is a class that implements `Http\Message\RequestFactory` from HTTPlug, and `my.mailhog.host` is the hostname (or IP) where mailhog is running, and `port` is the port where the mailhog API is running (by default 8025).

## Run tests

Make sure you have Mailhog running and run:

```bash
make test
```

### Running Mailhog for tests

You can either run your own instance of Mailhog or use the provided Dockerfile to run one for you.
To run Mailhog with Docker make sure you have Docker insalled and run:

```bash
docker-compose up -d
```

### Mailhog ports for tests

To prevent port collissions with any other Mailhog instances while testing the tests expect Mailhog to listen to SMTP on port 2025 (instead of the default 1025) and to HTTP traffic on port 9025 (instead of the default 8025).

If you want different ports you can copy `phpunit.xml.dist` to `phpunit.xml` and change the port numbers in the environment variables therein.

[mailhog]: https://github.com/mailhog/MailHog
[httplug]: https://github.com/php-http/httplug
[httplug-docs]: http://docs.php-http.org/en/latest/httplug/users.html

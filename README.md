# Mailhog API Client for PHP

_work in progress_

## Design Goals

- As little dependencies as possible
- Simple single client for both Mailhog V1 API as well as Mailhog V2 API
- Integration tests on all endpoints, both happy path and failure paths

## Run tests

Make sure you have Mailhog running and run:

```bash
make test
```

## Running Mailhog

You can either run your own instance of Mailhog or use the provided Dockerfile to run one for you.
To run Mailhog with Docker make sure you have Docker insalled and run:

```bash
docker-compose up -d
```

## Mailhog ports

To prevent port collissions with any other Mailhog instances while testing the tests expect Mailhog to listen to SMTP on port 2025 (instead of the default 1025) and to HTTP traffic on port 9025 (instead of the default 8025).

If you want different ports you can copy `phpunit.xml.dist` to `phpunit.xml` and change the port numbers in the environment variables therein.


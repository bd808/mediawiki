#!/usr/bin/env bash
set -ex
eval hhvm ${HHVM_ARGS:-} tests/phpunit/phpunit.php tests/phpunit/includes/RequestContextTest.php

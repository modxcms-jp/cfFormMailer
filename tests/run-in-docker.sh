#!/bin/sh
# Docker でテストを流す（ローカルに PHP が無くてもよい）。
#
#   tests/run-in-docker.sh                 PHP 7.4・8.3・8.5 で流す
#   tests/run-in-docker.sh 8.5             指定した版だけ流す
#   UPDATE_SNAPSHOTS=1 tests/run-in-docker.sh 8.5   スナップショットを書き直す
#
# PHPUnit に渡したい引数は PHPUNIT_ARGS で渡す（例: PHPUNIT_ARGS="--filter Input"）。
set -eu
cd "$(dirname "$0")/.."

uid="$(id -u):$(id -g)"

if [ ! -d vendor ]; then
    docker run --rm -u "$uid" -e COMPOSER_HOME=/tmp/composer -v "$PWD":/app -w /app composer:2 \
        install --no-interaction --no-progress
fi

versions="${*:-7.4 8.3 8.5}"
status=0
for v in $versions; do
    echo "== PHP $v =="
    docker run --rm -u "$uid" -e UPDATE_SNAPSHOTS="${UPDATE_SNAPSHOTS:-}" -v "$PWD":/app -w /app "php:$v-cli" \
        php vendor/bin/phpunit ${PHPUNIT_ARGS:-} || status=1
done
exit $status

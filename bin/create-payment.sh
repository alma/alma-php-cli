#!/bin/bash
# Little script to demonstrate how alma:payment:create commmand works

default_env="faketest"
default_amount="66900"
env="${1:-$default_env}"
shift
amount="${1:-$default_amount}"
shift
default_opt="--output-payload --format-payload dump --verbose --dry-run $@"
echo
echo "default_opt:'$default_opt'"
echo "edit the $0 script if you want change options & console arguments"
echo

if [[ ! -e ".env.$env.local" ]] ; then
    echo ".env.$env.local : file not found. Give me a valid argument as first arg (default is '$default_env')"
    exit 1
fi
console --env $env alma:payment:create $amount $default_opt \
    --origin "online" \
    --payment-locale "es_ES" \
    --first-name "John" \
    --last-name "Doe" \
    --email "john-doe@yopmail.fr" \
    --phone "06 12 34 56 78" \
    --customer-first-name "John" \
    --customer-last-name "Doe" \
    --customer-email "john-doe@yopmail.fr" \
    --customer-phone "06 12 34 56 78" \
    --shipping-first-name "John" \
    --shipping-last-name "Doe" \
    --shipping-email "john-doe@yopmail.fr" \
    --shipping-line1 "1 rue de Rome" \
    --shipping-postal-code "75001" \
    --shipping-city "Paris" \
    --shipping-country "FR" \
    --billing-first-name "John" \
    --billing-last-name "Doe" \
    --billing-email "john-doe@yopmail.fr" \
    --billing-line1 "1 rue de Rome" \
    --billing-postal-code "75001" \
    --billing-city "Paris" \
    --billing-country "FR"

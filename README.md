# ALMA PHP CLI

This project based on [alma-php-client](https://github.com/alma/alma-php-client) allow you to contact Alma API endpoints
with different API keys for different **environments** (e.g. `live` & `test`) or **projects** - just like
[stripe-cli](https://stripe.com/docs/stripe-cli) does.

Tags: payments, payment gateway, ecommerce, e-commerce, alma, monthly payments, split payments, api, cli, alma-cli,
command-line-interface

## Installation

* `git clone`
* `composer install` (@see [get composer](https://getcomposer.org/download/))

## Usage

1. Put your `ALMA_API_KEY` in a `.env.*.local` file  
   Where `*` define your environment & can be every word with only `[a-Z]` characters
1. Optionally change alma api target by declaring `ALMA_API_MODE` in your `.env.*.local` file (default is `test`)  
   You can find all default values in the `.env` file
1. Launch command as described bellow  
   Where `myenvtest` => `.env.myenvtest.local`  
   (the file containing `ALMA_API_KEY="sk_test_xxxxx"` for example) 
   ```
   php bin/console --env myenvtest alma:merchant:get
   ```

## Available commands

* `alma:merchant:get`
* `alma:eligibility:get`
* `alma:payment:create`
* `alma:payment:get`
* `alma:accounting-transactions:get`

You can found more about options & arguments with `--help` option (ex: `console alma:<command> --help`)

## TODO

* Auto-wiring commands into `Kernel.php`  
  based on [Alma api doc](https://docs.getalma.eu/reference) / `\Alma\API\Client` available endpoints.

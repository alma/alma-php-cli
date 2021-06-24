# ALMA PHP CLI

This project based on [alma-php-client](https://github.com/alma/alma-php-client) allow you to contact Alma API endpoints
with different API keys for different **environments** (e.g. `live` & `test`) or **projects** - just like
[stripe-cli](https://stripe.com/docs/stripe-cli) does.

Tags: payments, payment gateway, ecommerce, e-commerce, alma, monthly payments, split payments, api, cli, alma-cli,
command-line-interface


_**WIP:** (Only dev outputs with `dump()` are availables)_ => `env=dev` is required

## Installation

* `git clone`
* `composer install` (@see [get composer](https://getcomposer.org/download/))

## Usage

1. Put your `ALMA_API_KEY` in a `.env.*.local` file
   
   where `*` define your environment & can be every word with only `[a-Z]` characters
2. Launch command as described bellow  
   where `myenvtest` => `.env.myenvtest.local`  
   (the file containing `ALMA_API_KEY="sk_test_xxxxx"` for example) 
   ```
   php bin/console --env myenvtest alma:merchant:get
   ```

## Available commands

* `alma:merchant:get`

## TODO

* Command abstraction
* Auto-wiring commands into `Kernel.php`  
  based on [Alma api doc](https://docs.getalma.eu/reference) / `\Alma\API\Client` available endpoints.
* Allow `env=prod` (remove `dump()` for default output / or require var-dumper as non-dev requirement)

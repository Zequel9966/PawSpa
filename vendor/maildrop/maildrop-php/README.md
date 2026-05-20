# Maildrop PHP client

## Installation

The SDK is available on [Packagist](https://packagist.org/packages/maildrop/maildrop-php). To install it, you will need to be using [Composer](http://getcomposer.org/)
in your project.

```bash
composer require maildrop/maildrop-php
```

## Usage

You should always use Composer autoloader in your application to automatically load your dependencies. All the examples below assume you've already included this in your file:

```php
require 'vendor/autoload.php';
use Maildrop\Maildrop;
```

Here's how to add a contact to one of your contact list using the SDK:

```php
// First, instantiate the SDK with the ClientApiKey of one of your account
// See https://doc.maildrop.fr/-t44.html#Trouver_ClientApiKey to find the right ClientApiKey
Maildrop::setDefaultClientApiKey("client-apikey");

$md = new Maildrop();

// Now, add a new contact to a contact list
$md->subscribers()->add([
    'listid' => 'a7fb155366727c500406344b5f196f3a',
    'email'  => 'jack@example.com'
]);

```


### All usage examples

You will find more examples in the [/examples](examples/) directory in this repo.

You will find more documentation at [/doc](doc/index.md) and on
[https://doc.maildrop.fr](https://doc.maildrop.fr/?c=2).

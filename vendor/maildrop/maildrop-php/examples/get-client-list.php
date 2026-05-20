<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultPartnerApiKey(getenv("apikey"));

$md = new Maildrop();

// Also, you can use the "client_api_key" parameter to get only one client.
$r = $md->clients()->get();

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Your partner account manages {$r->getTotal()} clients.\n";

    foreach ($r->getData() as $client) {
        echo $client->name." / ".$client->client_api_key, "\n";
    }
}

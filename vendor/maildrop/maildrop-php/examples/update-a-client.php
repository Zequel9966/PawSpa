<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultPartnerApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->clients()->update([
    "client_api_key" => "8e64a5e1c758ab545b72e6a35f03c143",
    "company_name" => "ACME Company",
    "emails" => [
        "main" => "hello@acmeco.com"
    ]
]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    var_dump($r->getData());
}

<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->tracking_domains()->verify_ownership([
        "id" => 455
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {

    $tracking_domain = $r->getData();

    if (1 == $tracking_domain->status->ownership_status) {
        echo "Ownership of domain {$tracking_domain->domain} is verifed !\n";
    } else {
        echo "Ownership of domain {$tracking_domain->domain} is not verifed !\n";

        echo "Please, create a DNS TXT record like: {$tracking_domain->dns_records->txt->host} 3600 IN TXT \"{$tracking_domain->dns_records->txt->value}\"";
    }
}

<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->tracking_domains()->verify_cname([
        "id" => 455
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {

    $tracking_domain = $r->getData();

    if ('OK' == $tracking_domain->status->cname_status) {
        echo "CNAME record for domain {$tracking_domain->domain} is OK !\n";
    }

    if ('ERROR' == $tracking_domain->status->cname_status) {
        echo "CNAME record for domain {$tracking_domain->domain} is not ok (Error: {$tracking_domain->status->cname_error}) !\n";

        echo "Please, create a DNS CNAME record like: {$tracking_domain->dns_records->cname->host} 3600 IN CNAME {$tracking_domain->dns_records->cname->value}";
    }
}

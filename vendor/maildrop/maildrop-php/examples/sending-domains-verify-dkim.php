<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->sending_domains()->verify_dkim([
        "id" => 1335
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";

    $sending_domain = $r->getData();

    if ('OK' == $sending_domain->status->dkim_status) {
        echo "Sending domain \"{$sending_domain->domain}\" is ready to send your emails with DKIM.\n";
    }

    if ('ERROR' == $sending_domain->status->dkim_status) {
        echo "Please, create a DNS TXT record like:\n{$sending_domain->dns_records->dkim_txt->host} 3600 IN TXT \"{$sending_domain->dns_records->dkim_txt->value}\"\n";
    }

    var_dump($sending_domain);
}

<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->sending_domains()->verify_returnpath([
        "id" => 1335
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";

    $sending_domain = $r->getData();

    if ('OK' == $sending_domain->status->returnpath_status) {
        echo "Sending domain \"{$sending_domain->domain}\" is ready to send your emails with a custom Return-Path address.\n";
    }

    if ('ERROR' == $sending_domain->status->returnpath_status) {
        echo "Please, create a DNS CNAME record like:\n{$sending_domain->dns_records->returnpath_cname->host} 3600 IN CNAME {$sending_domain->dns_records->returnpath_cname->value}\n";
    }

    var_dump($sending_domain);
}

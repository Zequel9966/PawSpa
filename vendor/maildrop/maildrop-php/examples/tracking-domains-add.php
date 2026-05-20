<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->tracking_domains()->add([
        "domain" => "emails.maildrop.fr",
        "type" => "TRACKING",
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";

    $tracking_domain = $r->getData();

    echo "Tracking domain \"{$tracking_domain->domain}\" (id:{$tracking_domain->id}) :\n";
    var_dump($tracking_domain);
}

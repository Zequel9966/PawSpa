<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->tracking_domains()->get([
        "start" => 0,
        "limit" => 50
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "{$r->getTotal()} tracking domains.\n";

    foreach($r->getData() as $tracking_domain){
        echo "Tracking domain \"{$tracking_domain->domain}\" (id:{$tracking_domain->id}) :\n";
        var_dump($tracking_domain);
    }
}

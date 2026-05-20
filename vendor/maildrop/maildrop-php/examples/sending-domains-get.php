<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->sending_domains()->get([
        "start" => 0,
        "limit" => 50
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "{$r->getTotal()} sending domains.\n";

    foreach($r->getData() as $sending_domain){
        echo "Sending domain \"{$sending_domain->domain}\" uses {$sending_domain->returnpath_domain} as return-path :\n";

        var_dump($sending_domain);
    }
}

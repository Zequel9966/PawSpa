<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->reports()->clicks_details([
        "cid" => 919154,
        "lid" => 689822,
        "limit" => 10
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "{$r->getTotal()} clicks on this url\n";

    foreach($r->getData() as $a){
        echo "{$a->contact->email} clicked {$a->clicks} time\n";
    }
}

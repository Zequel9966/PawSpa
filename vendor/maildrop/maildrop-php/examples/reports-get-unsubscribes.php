<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->reports()->unsubscribes([
        "cid" => 919154,
        "limit" => 10
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "{$r->getTotal()} contacts unsubscribed from this campaign\n";

    foreach($r->getData() as $a){
        echo "{$a->contact->email} unsubscribed an {$a->timestamp}\n";
    }
}

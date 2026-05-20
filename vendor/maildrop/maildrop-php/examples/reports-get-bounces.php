<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->reports()->bounces([
        "cid" => 919154,
        "limit" => 500
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "{$r->getTotal()} contacts bounced on this campaign.\n";

    foreach($r->getData() as $a){
        echo "{$a->contact->email} bounced at {$a->timestamp}, remote server replied: {$a->reason}\n";
    }
}

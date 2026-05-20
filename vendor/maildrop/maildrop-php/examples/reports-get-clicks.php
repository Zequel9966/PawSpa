<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->reports()->clicks([
        "cid" => 919154
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";

    foreach($r->getClicks() as $a_link){
        echo "Link {$a_link->url} (lid: {$a_link->lid}) clicked {$a_link->total} times ({$a_link->unique} uniques)\n";
    }
}

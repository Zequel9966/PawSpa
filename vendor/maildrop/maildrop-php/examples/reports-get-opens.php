<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->reports()->opens([
        "cid" => 919154,
        "limit"=> 2
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Total count of recipients who opened the email: {$r->getTotal()}\n";
    echo "- desktop: {$r->getTotalOnDesktop()}\n";
    echo "- mobile: {$r->getTotalOnMobile()}\n";

    foreach ($r->getOpens() as $an_opener) {
        var_dump($an_opener);
    }
}

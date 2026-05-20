<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->reports()->recipients([
        "cid" => 919154,
        "limit"=> 100
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Total recipients count: {$r->getTotal()}\n";

    foreach ($r->getRecipients() as $a_recipient) {
        var_dump($a_recipient);
    }
}

<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->subscribers()->unsubscribe([
        "listid" => "00ffbc53b45d8467e4a2675c62bb8c44",
        "emails"=> [
            "myemail@maildrop.fr",
            "myemail1@maildrop.fr",
            "myemail2@maildrop.fr",
            "myemail3@maildrop.fr",
        ]
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Unsubscribed: {$r->getUnsubrscribedCount()}\n";
}

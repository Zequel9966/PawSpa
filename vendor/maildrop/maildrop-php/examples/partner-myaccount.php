<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultPartnerApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->partner()->my_account();

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    var_dump($r->getData());
}

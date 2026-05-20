<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->sending_domains()->delete([
        "id" => 1335
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success, deleted !\n";
}

<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->transactionals()->resume();

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    $data = $r->getData();
    if ($data->resumed) {
        echo "Transactional emails are now running normaly on this account.";
    }
}

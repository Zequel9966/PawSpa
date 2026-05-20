<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->transactionals()->pause();

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    $data = $r->getData();
    if ($data->paused) {
        echo "Transactional emails are now paused on this account.";
    }
}

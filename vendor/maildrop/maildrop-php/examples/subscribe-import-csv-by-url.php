<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->subscribers()->import_by_url([
        "listid"=> "00ffbc53b45d8467e4a2675c62bb8c44",
        "url" => "http://www.maildrop.fr/file.csv",
        "options" => [
            "update_existing" => true,
            "resubscribe" => true
        ]
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "TaskID: {$r->getTaskId()}\n";
}
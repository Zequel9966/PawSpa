<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->subscribers()->import_by_url_get_status([
        "TaskID"=> "c4f8826cd6c03ca71345887e1ec32509"
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Status: {$r->getStatus()}\n";
    echo "Message: {$r->getMessage()}\n";

    var_dump($r->isPending());
    var_dump($r->isCompleted());
    var_dump($r->isError());
    var_dump($r->isWorking());
}

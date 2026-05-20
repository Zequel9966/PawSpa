<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->lists()->get([
        "created_after"  => new \DateTime("2019-01-01 00:00:00", new \DateTimeZone("Europe/Paris")),
        "created_before" => new \DateTime("2019-12-31 23:59:59", new \DateTimeZone("Europe/Paris"))
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "{$r->getTotal()} lists.\n";

    foreach($r->getData() as $list){
        echo "List named \"{$list->name}\" was created on {$list->date_created}\n";
    }
}

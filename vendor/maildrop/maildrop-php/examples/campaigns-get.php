<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->campaigns()->get([
        "status" => ['DRAFT', 'SENT'],
        "created_after"  => new \DateTime("2019-01-01 00:00:00", new \DateTimeZone("Europe/Paris")),
        "created_before" => new \DateTime("2019-12-31 23:59:59", new \DateTimeZone("Europe/Paris")),
        "sort_field" => "created"
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "{$r->getTotal()} campaigns.\n";

    foreach($r->getData() as $campaign){
        echo "{$campaign->status} / {$campaign->name} was created on {$campaign->date_created}\n";
    }
}

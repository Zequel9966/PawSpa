<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;
use \Maildrop\Model\Contact;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$contacts = [
    (new Contact("myemail@maildrop.fr"))
        ->setCustomField("ville", "Castres")
        ->setCustomField("codep", "81100"),

    (new Contact("mysecondemail@maildrop.fr"))
        ->setFirstName("D")
        ->setLastName("T"),

    new Contact("mysecondemail@maildropfr")
];


$md = new Maildrop();

$r = $md->subscribers()->import_batch([
        "listid"=> "00ffbc53b45d8467e4a2675c62bb8c44",
        "batch" => $contacts,
        "options" => [
            "update_existing" => true,
            "resubscribe" => true
        ]
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Subscribers added: {$r->getAddedCount()}\n";
    echo "Subscribers updated: {$r->getUpdatedCount()}\n";
    echo "Subscribers in error: {$r->getErrorsCount()}\n";
}
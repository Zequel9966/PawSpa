<?php

require __DIR__ . "/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->lists()->active([
    "listid" => "302e2bb12c08372dc1d253b5cc112a8b",
    "sort_field" => "date",
    "sort_dir" => "desc",
    "since_date" => new DateTime("2025-01-14T00:00:00Z"),
]);

if (!$r->success()) {
    echo "Error: " . $r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Your request returns {$r->getTotal()} active contacts.\n";

    foreach ($r->getData() as $contact) {
        echo "{$contact->email}\n";
    }
}

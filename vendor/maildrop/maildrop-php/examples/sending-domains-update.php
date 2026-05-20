<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->sending_domains()->update([
        "id" => 1335,
        "returnpath_domain" => "sender.newsletter.maildrop.fr",
        "tracking_domain" => "emails.maildrop.fr",
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";

    $sending_domain = $r->getData();
    echo "Sending domain \"{$sending_domain->domain}\" updated :\n";

    var_dump($sending_domain);
}

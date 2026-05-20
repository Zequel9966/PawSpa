<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->sending_domains()->add([
        "domain" => "newsletter.maildrop.fr",
        "returnpath_domain" => "sender.newsletter.maildrop.fr",
        "tracking_domain" => "trk-test.maildrop.fr"
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";

    $sending_domain = $r->getData();
    echo "Sending domain \"{$sending_domain->domain}\" added to your account :\n";

    var_dump($sending_domain);
}

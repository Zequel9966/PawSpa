<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$schedule_time = new \DateTime("2020-07-15 14:20:00", new \DateTimeZone("Asia/Kathmandu"));
//$schedule_time = "2020-07-15 14:20:00"; // Implicit Europe/Paris

$r = $md->campaigns()->schedule([
        "cid" => 849421,
        "schedule_time" => $schedule_time
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Campaign scheduled.\n";
}

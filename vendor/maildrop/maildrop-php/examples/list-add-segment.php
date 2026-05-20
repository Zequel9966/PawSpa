<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$first_condition = (new \Maildrop\Model\SegmentCondition())
                    ->setField("email")
                    ->setOperator("ends")
                    ->setValue("@orange.fr");

$second_condition = (new \Maildrop\Model\SegmentCondition())
                    ->setField("first_name")
                    ->setOperator("not blank");

$r = $md->segments()->add([
        "listid"=> "00ffbc53b45d8467e4a2675c62bb8c44",
        "name" => "My segment",
        "Conditions" => [$first_condition, $second_condition]
    ]);

if( ! $r->success() ){
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Segment created with segmentid: {$r->getSegmentId()}";
}

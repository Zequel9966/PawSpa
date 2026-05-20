<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->custom_fields()->add([
        "listid"=>"yourlistid",
        "name" => "my_custom_field_5",
        "type" => "STRING"
    ]);

if( ! $r->success() ){
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Custom Field created with cfid: {$r->getCfId()}";
}

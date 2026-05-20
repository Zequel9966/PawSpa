<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->lists()->delete([
        "listid"=>"yourlistid"
    ]);

if( ! $r->success() ){
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
}

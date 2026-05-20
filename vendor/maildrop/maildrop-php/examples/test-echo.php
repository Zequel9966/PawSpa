<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->tests()->echo(["string"=>"hello!"]);

if( ! $r->success() ){
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";

    echo $r->getReply();
}
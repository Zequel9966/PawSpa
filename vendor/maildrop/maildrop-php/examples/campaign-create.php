<?php

require __DIR__."/../vendor/autoload.php";

use \Maildrop\Maildrop;

Maildrop::setDefaultClientApiKey(getenv("apikey"));

$md = new Maildrop();

$r = $md->campaigns()->create([
        "campaign_name" => "Welcome email",
        "subject" => "Your welcome",
        "from_name" => "created",
        "from_email" => "hello@maildrop.fr",
        "message_html" => '<p>Message body in HTML format<p><p><a href="{$Unsubscribe_Link}">Unsubscribe me</a></p>',
        "list_id" => ["1e0e5a41742ed6072c916b391e94414e", "1a23c4dbc6083447f414006ddd54168d"]
    ]);

if (!$r->success()) {
    echo "Error: ".$r->getErrorMsg();
} else {
    echo "Success !\n";
    echo "Campaign created with CampaignId {$r->getCampaignId()}.\n";
}

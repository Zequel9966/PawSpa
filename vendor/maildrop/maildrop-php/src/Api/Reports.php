<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericCollectionResponse;
use Maildrop\Api\Response\Reports\RecipientsResponse;
use Maildrop\Api\Response\Reports\OpensResponse;
use Maildrop\Api\Response\Reports\ClicksResponse;
use Maildrop\Api\Request\DataFormatter\NoFormat;

/**
 * @method RecipientsResponse recipients()
 * @method OpensResponse opens()
 * @method ClicksResponse clicks()
 * @method GenericCollectionResponse unsubscribes()
 * @method GenericCollectionResponse clicks_details()
 * @method GenericCollectionResponse bounces()
 */
class Reports extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "recipients" => ["httpGet", "Reports.Recipients", NoFormat::class, RecipientsResponse::class],
        "opens" => ["httpGet", "Reports.Opens", NoFormat::class, OpensResponse::class],
        "clicks" => ["httpGet", "Reports.Clicks", NoFormat::class, ClicksResponse::class],
        "unsubscribes" => ["httpGet", "Reports.Unsubscribes", NoFormat::class, GenericCollectionResponse::class],
        "clicks_details" => ["httpGet", "Reports.ClickDetails", NoFormat::class, GenericCollectionResponse::class],
        "bounces" => ["httpGet", "Reports.BounceMessages", NoFormat::class, GenericCollectionResponse::class],
    ];
}

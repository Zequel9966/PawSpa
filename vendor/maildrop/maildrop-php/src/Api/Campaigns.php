<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\GenericCollectionResponse;
use Maildrop\Api\Response\Campaigns\CreateResponse;
use Maildrop\Api\Request\DataFormatter\NoFormat;
use Maildrop\Api\Request\DataFormatter\Campaigns\GetFormatter;
use Maildrop\Api\Request\DataFormatter\Campaigns\ScheduleFormatter;

/**
 * @method GenericCollectionResponse get()
 * @method GenericResponse delete()
 * @method GenericResponse send()
 * @method GenericResponse schedule()
 * @method CreateResponse create()
 */
class Campaigns extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "get" => ["httpGet", "Campaign.Get", GetFormatter::class, GenericCollectionResponse::class],
        "delete" => ["httpPost", "Campaign.Delete", NoFormat::class, GenericResponse::class],
        "send" => ["httpPost", "Campaign.Send", NoFormat::class, GenericResponse::class],
        "schedule" => ["httpPost", "Campaign.Schedule", ScheduleFormatter::class, GenericResponse::class],
        "create" => ["httpPost", "Campaign.Create", NoFormat::class, CreateResponse::class],
    ];
}

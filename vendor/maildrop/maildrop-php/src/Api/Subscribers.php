<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\Response\Subscribers\ImportBatchResponse;
use Maildrop\Api\Response\Subscribers\ImportByUrlResponse;
use Maildrop\Api\Response\Subscribers\ImportByUrlGetStatusResponse;
use Maildrop\Api\Response\Subscribers\UnsubscribeBatchResponse;
use Maildrop\Api\Response\Subscribers\GetDetailsResponse;
use Maildrop\Api\Request\DataFormatter\NoFormat;
use Maildrop\Api\Request\DataFormatter\Subscribers\ImportBatchFormatter;
use Maildrop\Api\Request\DataFormatter\Subscribers\UnsubscribeBatchFormatter;

/**
 * @method GenericResponse add()
 * @method GenericResponse add_and_resubscribe()
 * @method ImportBatchResponse import_batch()
 * @method ImportByUrlResponse import_by_url()
 * @method ImportByUrlGetStatusResponse import_by_url_get_status()
 * @method UnsubscribeBatchResponse unsubscribe()
 * @method GetDetailsResponse get_details()
 */
class Subscribers extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "add" => ["httpPost", "Subscriber.Add", NoFormat::class, GenericResponse::class],
        "add_and_resubscribe" => ["httpPost", "Subscriber.AddAndResubscribe", NoFormat::class, GenericResponse::class],
        "import_batch" => ["httpPost", "Subscriber.ImportBatch", ImportBatchFormatter::class, ImportBatchResponse::class],
        "import_by_url" => ["httpPost", "Subscriber.ImportByUrl", NoFormat::class, ImportByUrlResponse::class],
        "import_by_url_get_status" => ["httpGet", "Subscriber.ImportByUrlGetStatus", NoFormat::class, ImportByUrlGetStatusResponse::class],
        "unsubscribe" => ["httpPost", "Subscriber.UnsubscribeBatch", UnsubscribeBatchFormatter::class, UnsubscribeBatchResponse::class],
        "get_details" => ["httpGet", "Subscriber.GetDetails", NoFormat::class, GetDetailsResponse::class]
    ];
}

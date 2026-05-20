<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\GenericCollectionResponse;
use Maildrop\Api\Request\DataFormatter\NoFormat;

/**
 * @method GenericCollectionResponse get()
 * @method GenericResponse add()
 * @method GenericResponse verify_cname()
 * @method GenericResponse verify_ownership()
 * @method GenericResponse delete()
 */
class TrackingDomains extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "get" => ["httpGet", "TrackingDomains.Get", NoFormat::class, GenericCollectionResponse::class],
        "add" => ["httpPost", "TrackingDomains.Add", NoFormat::class, GenericResponse::class],
        "verify_cname" => ["httpGet", "TrackingDomains.VerifyCname", NoFormat::class, GenericResponse::class],
        "verify_ownership" => ["httpGet", "TrackingDomains.VerifyOwnership", NoFormat::class, GenericResponse::class],
        "delete" => ["httpPost", "TrackingDomains.Delete", NoFormat::class, GenericResponse::class],
    ];
}

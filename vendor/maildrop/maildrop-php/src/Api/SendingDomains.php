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
 * @method GenericResponse update()
 * @method GenericResponse verify_dkim()
 * @method GenericResponse verify_returnpath()
 * @method GenericResponse delete()
 */
class SendingDomains extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "get" => ["httpGet", "SendingDomains.Get", NoFormat::class, GenericCollectionResponse::class],
        "add" => ["httpPost", "SendingDomains.Add", NoFormat::class, GenericResponse::class],
        "update" => ["httpPost", "SendingDomains.Update", NoFormat::class, GenericResponse::class],
        "verify_dkim" => ["httpGet", "SendingDomains.VerifyDkim", NoFormat::class, GenericResponse::class],
        "verify_returnpath" => ["httpGet", "SendingDomains.VerifyReturnPath", NoFormat::class, GenericResponse::class],
        "delete" => ["httpPost", "SendingDomains.Delete", NoFormat::class, GenericResponse::class],
    ];
}

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
 * @method GenericResponse update()
 */
class Clients extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "get" => ["httpGet", "Client.Get", NoFormat::class, GenericCollectionResponse::class],
        "update" => ["httpPost", "Client.Update", NoFormat::class, GenericResponse::class]
    ];
}

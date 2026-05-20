<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\GenericCollectionResponse;
use Maildrop\Api\Response\Lists\CreateResponse;
use Maildrop\Api\Request\DataFormatter\NoFormat;
use Maildrop\Api\Request\DataFormatter\Lists\GetFormatter;
use Maildrop\Api\Request\DataFormatter\Lists\ActiveFormatter;

/**
 * @method CreateResponse create()
 * @method GenericCollectionResponse get()
 * @method GenericResponse delete()
 * @method GenericCollectionResponse active()
 */
class Lists extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "create" => ["httpPost", "List.Create", NoFormat::class, CreateResponse::class],
        "get" => ["httpGet", "List.Get", GetFormatter::class, GenericCollectionResponse::class],
        "delete" => ["httpGet", "List.Delete", NoFormat::class, GenericResponse::class],
        "active" => ["httpGet", "List.Active", ActiveFormatter::class, GenericCollectionResponse::class],
    ];
}

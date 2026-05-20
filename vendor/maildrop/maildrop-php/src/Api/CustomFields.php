<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\Response\CustomFields\AddResponse;

use Maildrop\Api\Request\DataFormatter\NoFormat;

class CustomFields extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter, response Class
        "add" => ["httpPost", "List.AddCustomField", NoFormat::class, AddResponse::class]
    ];
}
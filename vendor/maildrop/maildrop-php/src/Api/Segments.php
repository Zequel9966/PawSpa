<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\Response\Segments\AddResponse;
use Maildrop\Api\Request\DataFormatter\Segments\AddFormatter;

/**
 * @method AddResponse add()
 */
class Segments extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "add" => ["httpPost", "List.AddSegment", AddFormatter::class, AddResponse::class]
    ];
}

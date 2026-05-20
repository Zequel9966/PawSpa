<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\Response\Tests\EchoResponse;
use Maildrop\Api\Request\DataFormatter\NoFormat;
use Maildrop\Api\Request\DataFormatter\NoParameters;

/**
 * @method EchoResponse echo()
 * @method GenericResponse error()
 */
class Tests extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "echo" => ["httpPost", "Test.Echo", NoFormat::class, EchoResponse::class],
        "error" => ["httpGet", "Test.Error", NoParameters::class, GenericResponse::class]
    ];
}

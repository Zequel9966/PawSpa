<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\Request\DataFormatter\NoParameters;

/**
 * @method GenericResponse my_account()
 */
class Partner extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "my_account" => ["httpGet", "Partner.Myaccount", NoParameters::class, GenericResponse::class]
    ];
}

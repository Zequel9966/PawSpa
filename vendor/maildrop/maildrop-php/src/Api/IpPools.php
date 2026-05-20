<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api;

use Maildrop\Api\GenericResponse;
use Maildrop\Api\Request\DataFormatter\NoFormat;
use Maildrop\Api\Request\DataFormatter\NoParameters;
use Maildrop\Api\Response\IpPools\GetResponse;
use Maildrop\Api\Response\IpPools\IpsResponse;

/**
 * @method GetResponse get()
 * @method IpsResponse ips()
 */
class IpPools extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "get" => ["httpPost", "IpPool.List", NoParameters::class, GetResponse::class],
        "ips" => ["httpGet", "IpPool.Ips", NoFormat::class, IpsResponse::class]
    ];
}

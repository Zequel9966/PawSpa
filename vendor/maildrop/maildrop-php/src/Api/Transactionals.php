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
use Maildrop\Api\Request\DataFormatter\Lists\GetFormatter;

/**
 * @method GenericResponse pause() Pause message sending
 * @method GenericResponse resume() Resume message sending
 */
class Transactionals extends HttpApi
{
    protected $callables = [
        // public method name => http_method, api path, parameters formatter class, response class
        "pause" => ["httpPost", "Transactional.Pause", NoFormat::class, GenericResponse::class],
        "resume" => ["httpPost", "Transactional.Resume", NoFormat::class, GenericResponse::class]
    ];
}

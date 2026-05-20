<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Tests;

use Maildrop\Api\GenericResponse;

class EchoResponse extends GenericResponse
{
    public function getReply()
    {
        return $this->json_data->data??"";
    }
}
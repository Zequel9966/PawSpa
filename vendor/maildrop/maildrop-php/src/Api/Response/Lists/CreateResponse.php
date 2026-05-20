<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Lists;

use Maildrop\Api\GenericResponse;

class CreateResponse extends GenericResponse
{
    public function getListId()
    {
        return $this->json_data->data->ListID??"";
    }
}
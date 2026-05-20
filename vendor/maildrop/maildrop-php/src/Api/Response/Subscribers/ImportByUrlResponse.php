<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Subscribers;

use Maildrop\Api\GenericResponse;

class ImportByUrlResponse extends GenericResponse
{
    public function getTaskID()
    {
        return $this->json_data->data->TaskID??"";
    }
}
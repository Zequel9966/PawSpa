<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Segments;

use Maildrop\Api\GenericResponse;

class AddResponse extends GenericResponse
{
    public function getSegmentId(){
        return $this->json_data->data??"";
    }
}
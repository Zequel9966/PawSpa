<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\IpPools;

use Maildrop\Api\GenericResponse;

class IpsResponse extends GenericResponse
{
    public function getData(){
        return $this->json_data->data??[];
    }
}

<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Campaigns;

use Maildrop\Api\GenericResponse;

class CreateResponse extends GenericResponse
{
    public function getCampaignId()
    {
        return $this->json_data->data->cid??"";
    }
}

<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Subscribers;

use Maildrop\Api\GenericResponse;

class UnsubscribeBatchResponse extends GenericResponse
{
    public function getUnsubrscribedCount()
    {
        return $this->json_data->data->unsubscribed_count??0;
    }

    public function getUnsubscribes()
    {
        return $this->json_data->data->unsubscribed??[];
    }
}

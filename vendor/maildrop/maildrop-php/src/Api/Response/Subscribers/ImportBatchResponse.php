<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Subscribers;

use Maildrop\Api\GenericResponse;

class ImportBatchResponse extends GenericResponse
{
    public function getAddedCount()
    {
        return $this->json_data->data->add_count??0;
    }

    public function getAdded()
    {
        return $this->json_data->data->adds??[];
    }

    public function getUpdatedCount()
    {
        return $this->json_data->data->update_count??0;
    }

    public function getUpdated()
    {
        return $this->json_data->data->updates??[];
    }

    public function getErrorsCount()
    {
        return $this->json_data->data->error_count??0;
    }

    public function getErrors()
    {
        return $this->json_data->data->errors??[];
    }
}
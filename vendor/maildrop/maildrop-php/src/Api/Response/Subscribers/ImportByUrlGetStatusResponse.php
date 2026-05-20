<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Response\Subscribers;

use Maildrop\Api\GenericResponse;

class ImportByUrlGetStatusResponse extends GenericResponse
{
    public function getStatus()
    {
        return $this->json_data->data->status??"";
    }

    public function getMessage()
    {
        return $this->json_data->data->message??"";
    }

    public function isCompleted(){
        return "COMPLETED"==$this->getStatus();
    }

    public function isError(){
        return "ERROR"==$this->getStatus();
    }

    public function isPending(){
        return "PENDING"==$this->getStatus();
    }

    public function isWorking(){
        return "WORKING"==$this->getStatus();
    }
}

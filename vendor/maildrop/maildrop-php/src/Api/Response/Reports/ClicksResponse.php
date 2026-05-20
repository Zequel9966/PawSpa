<?php

namespace Maildrop\Api\Response\Reports;

use Maildrop\Api\GenericResponse;

class ClicksResponse extends GenericResponse {
    public function getClicks()
    {
        return $this->json_data->data??[];
    }
}

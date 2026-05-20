<?php

namespace Maildrop\Api\Response\Reports;

use Maildrop\Api\GenericCollectionResponse;

class OpensResponse extends GenericCollectionResponse {
    public function getOpens()
    {
        return $this->getData();
    }

    public function getTotalOnDesktop()
    {
        return $this->json_data->data->desktop??0;
    }

    public function getTotalOnMobile()
    {
        return $this->json_data->data->mobile??0;
    }
}

<?php

namespace Maildrop\Api\Response\Reports;

use Maildrop\Api\GenericCollectionResponse;

class RecipientsResponse extends GenericCollectionResponse {
    public function getRecipients()
    {
        return $this->getData();
    }
}

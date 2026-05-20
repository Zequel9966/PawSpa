<?php

declare(strict_types=1);

/*
* This software may be modified and distributed under the terms
* of the MIT license. See the LICENSE file for details.
*/

namespace Maildrop\Api\Request\DataFormatter\Subscribers;

use Maildrop\Api\Request\DataFormatter\DataFormatterInterface;

class ImportBatchFormatter implements DataFormatterInterface
{
    public static function format($array_of_parameters)
    {
        $array_of_parameters = array_change_key_case($array_of_parameters, CASE_LOWER);

        if (isset($array_of_parameters["batch"])) {
            $array_of_parameters["batch"] = \json_encode($array_of_parameters["batch"]);
        }

        return $array_of_parameters;
    }
}
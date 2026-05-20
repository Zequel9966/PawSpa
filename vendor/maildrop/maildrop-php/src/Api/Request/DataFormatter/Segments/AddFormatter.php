<?php

declare(strict_types=1);

/*
* This software may be modified and distributed under the terms
* of the MIT license. See the LICENSE file for details.
*/

namespace Maildrop\Api\Request\DataFormatter\Segments;

use Maildrop\Api\Request\DataFormatter\DataFormatterInterface;

class AddFormatter implements DataFormatterInterface
{
    public static function format($array_of_parameters)
    {
        $array_of_parameters = array_change_key_case($array_of_parameters, CASE_LOWER);

        if (isset($array_of_parameters["conditions"])) {
            $array_of_parameters["conditions"] = \json_encode($array_of_parameters["conditions"]);
        }

        return $array_of_parameters;
    }
}
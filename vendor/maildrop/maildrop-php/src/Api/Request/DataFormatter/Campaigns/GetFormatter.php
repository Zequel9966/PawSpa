<?php

declare(strict_types=1);

/*
* This software may be modified and distributed under the terms
* of the MIT license. See the LICENSE file for details.
*/

namespace Maildrop\Api\Request\DataFormatter\Campaigns;

use Maildrop\Api\Request\DataFormatter\DataFormatterInterface;

class GetFormatter implements DataFormatterInterface
{
    static private $datetime_keys = [
        "created_before",
        "created_after",
        "sendtime_before",
        "sendtime_after"
    ];

    public static function format($array_of_parameters)
    {
        $array_of_parameters = array_change_key_case($array_of_parameters, CASE_LOWER);

        if (isset($array_of_parameters["status"]) && is_array($array_of_parameters["status"])) {
            $array_of_parameters["status"] = implode(",", $array_of_parameters["status"]);
        }

        foreach (self::$datetime_keys as $key) {
            if (isset($array_of_parameters[$key]) && $array_of_parameters[$key] instanceof \DateTime) {
                $array_of_parameters[$key] = $array_of_parameters[$key]->format('c');
            }
        }

        return $array_of_parameters;
    }
}

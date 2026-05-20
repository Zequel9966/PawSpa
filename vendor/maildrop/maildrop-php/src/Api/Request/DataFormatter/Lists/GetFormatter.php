<?php

declare(strict_types=1);

/*
* This software may be modified and distributed under the terms
* of the MIT license. See the LICENSE file for details.
*/

namespace Maildrop\Api\Request\DataFormatter\Lists;

use Maildrop\Api\Request\DataFormatter\DataFormatterInterface;

class GetFormatter implements DataFormatterInterface
{
    static private $datetime_keys = [
        "created_before",
        "created_after"
    ];

    public static function format($array_of_parameters)
    {
        $array_of_parameters = array_change_key_case($array_of_parameters, CASE_LOWER);

        foreach (self::$datetime_keys as $key) {
            if (isset($array_of_parameters[$key]) && $array_of_parameters[$key] instanceof \DateTime) {
                $array_of_parameters[$key] = $array_of_parameters[$key]->format('c');
            }
        }

        return $array_of_parameters;
    }
}

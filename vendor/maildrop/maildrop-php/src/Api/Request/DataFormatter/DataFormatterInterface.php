<?php

declare(strict_types=1);

/*
 * This software may be modified and distributed under the terms
 * of the MIT license. See the LICENSE file for details.
 */

namespace Maildrop\Api\Request\DataFormatter;

interface DataFormatterInterface
{
    public static function format($array_of_parameters);
}
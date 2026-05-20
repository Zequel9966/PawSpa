<?php

declare(strict_types=1);

/*
* This software may be modified and distributed under the terms
* of the MIT license. See the LICENSE file for details.
*/

namespace Maildrop\Model;

class Contact implements \JsonSerializable
{
    private $email;
    private $first_name = false;
    private $last_name = false;
    private $customs_fields = false;

    public function __construct($email)
    {
        $this->email = $email;
    }

    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function setLastName($last_name)
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function setCustomField($field, $value)
    {
        if (!\is_array($this->customs_fields)) {
            $this->customs_fields = [];
        }

        $this->customs_fields[$field] = $value;

        return $this;
    }

    public function jsonSerialize()
    {
        $return = [
            "email" => $this->email??false,
            "first_name" => $this->first_name??false,
            "last_name" => $this->last_name??false,
            "customs_fields" => $this->customs_fields??false
        ];

        $return = \array_filter($return, "self::filter");

        return (object)$return;
    }

    protected static function filter($value)
    {
        return false !== $value;
    }
}

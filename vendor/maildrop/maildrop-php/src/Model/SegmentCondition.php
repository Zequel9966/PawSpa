<?php

declare(strict_types=1);

/*
* This software may be modified and distributed under the terms
* of the MIT license. See the LICENSE file for details.
*/

namespace Maildrop\Model;

class SegmentCondition implements \JsonSerializable
{
    private $field;
    private $operator;
    private $value;

    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    public function setOperator($operator)
    {
        $this->operator = $operator;

        return $this;
    }

    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    public function jsonSerialize()
    {
        return (object)[
            "field" => $this->field??"",
            "op" => $this->operator??"",
            "value" => $this->value??""
        ];
    }
}
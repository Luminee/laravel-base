<?php

namespace Luminee\Base\Foundations;

trait Join
{
    protected function join($table, $one, $equal, $two, $inner = 'inner')
    {
        $this->_model = $this->_model->join($table, $one, $equal, $two, $inner);
        return $this;
    }

    protected function leftJoin($table, $one, $equal, $two)
    {
        return $this->join($table, $one, $equal, $two, 'left');
    }

    protected function joinOnAnd($table, array $first_on, array $second_on, $inner = 'inner')
    {
        $this->_model = $this->_model->join($table, function ($join) use ($first_on, $second_on) {
            $join->on($first_on[0], $first_on[1], $first_on[2])
                ->where($second_on[0], $second_on[1], $second_on[2]);
        }, null, null, $inner);
        return $this;
    }

    protected function leftJoinOnAnd($table, array $first_on, array $second_on)
    {
        return $this->joinOnAnd($table, $first_on, $second_on, 'left');
    }

}
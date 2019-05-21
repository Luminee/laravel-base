<?php

namespace Luminee\Base\Foundations;

use Illuminate\Database\Eloquent\Builder;

trait Join
{
    public function innerJoin($table, $one, $equal, $two)
    {
        $this->_model = $this->_model->join($table, $one, $equal, $two);
        return $this;
    }

    public function leftJoin($table, $one, $equal, $two)
    {
        $this->_model = $this->_model->join($table, $one, $equal, $two, 'left');
        return $this;
    }

    public function innerJoinOnAnd($table, array $first_on, array $second_on)
    {
        $this->_model = $this->_model->join($table, function ($join) use ($first_on, $second_on) {
            $join->on($first_on[0], $first_on[1], $first_on[2])
                ->where($second_on[0], $second_on[1], $second_on[2]);
        }, null, null, 'inner');
        return $this;
    }

    public function leftJoinOnAnd($table, array $first_on, array $second_on)
    {
        $this->_model = $this->_model->join($table, function ($join) use ($first_on, $second_on) {
            $join->on($first_on[0], $first_on[1], $first_on[2])
                ->where($second_on[0], $second_on[1], $second_on[2]);
        }, null, null, 'left');
        return $this;
    }

    public function joinModel($model, $one_column, $operator = '=', $two_column = 'id', $type = 'inner')
    {
        $table_one = $this->getModel()->getTable();
        $table_two = $this->structureModel($model)->getTable();
        $this->_model = $this->_model->join($table_two, $table_one . '.' . $one_column, $operator, $table_two . '.' . $two_column, $type);
        return $this;
    }

    protected function getModel()
    {
        if ($this->_model instanceof Builder) {
            return $this->_model->getModel();
        }
        return $this->_model;
    }

    protected function structureModel($model_name)
    {
        $string = explode(':', $model_name);
        $class = get_class($this->getModel());
        $str_arr = explode('\\', $class);
        if (count($string) == 1) {
            $str_arr[3] = ucfirst($string[0]);
        } else {
            $str_arr[2] = ucfirst($string[0]);
            $str_arr[3] = ucfirst($string[1]);
        }
        $class = implode('\\', $str_arr);
        return new $class;
    }
}
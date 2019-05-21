<?php

namespace Luminee\Base\Foundations;

trait Other
{
    protected function orderBy($field, $sort = 'asc')
    {
        $this->_model = $this->_model->orderBy($field, $sort);
        return $this;
    }

    protected function orderByRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->orderByRaw($query, $param);
        return $this;
    }

    protected function groupBy($field)
    {
        $this->_model = $this->_model->groupBy($field);
        return $this;
    }

    protected function having($field, $value, $equal = null)
    {
        $this->_model = $this->_model->having($field, $equal, $value);
        return $this;
    }

    protected function havingRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->havingRaw($query, $param);
        return $this;
    }

    protected function selectDistinct($field)
    {
        $this->_model = $this->_model->selectRaw("distinct ($field)");
        return $this;
    }

    protected function selectRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->selectRaw($query, $param);
        return $this;
    }

    protected function addSelect($field)
    {
        $this->_model = $this->_model->addSelect($field);
        return $this;
    }

    protected function limit($rows, $offset = 0)
    {
        $this->_model = $this->_model->skip($offset)->take($rows);
        return $this;
    }

    protected function union($query)
    {
        $this->_model = $this->_model->union($query);
        return $this;
    }


    protected function distinct()
    {
        $this->_model = $this->_model->distinct();
        return $this;
    }

    protected function orderByStringAsInt($field, $sort = 'asc')
    {
        $this->_model = $this->_model->orderByRaw("CAST(`$field` AS DECIMAL) $sort");
        return $this;
    }

    protected function orderByArrayList($field, $array, $sort = 'asc')
    {
        $this->_model = $this->_model->orderByRaw("FIND_IN_SET($field,'$array') $sort");
        return $this;
    }

    protected function queryOrderBy($query, $order_by)
    {
        foreach ($order_by as $field => $sort) {
            $query->orderBy($field, $sort);
        }
        return $query;
    }

    protected function queryGroupBy($query, $group_by)
    {
        foreach ($group_by as $value) {
            $query->groupBy($value);
        }
    }

}
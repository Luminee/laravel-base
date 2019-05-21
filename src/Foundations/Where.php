<?php

namespace Luminee\Base\Foundations;

trait Where
{
    protected function where($field, $value, $equal = '=')
    {
        $this->_model = $this->_model->where($field, $equal, $value);
        return $this;
    }

    protected function orWhere($field, $value, $equal = '=')
    {
        $this->_model = $this->_model->orWhere($field, $equal, $value);
        return $this;
    }

    protected function whereIn($field, array $array)
    {
        $this->_model = $this->_model->whereIn($field, $array);
        return $this;
    }

    protected function whereNotIn($field, array $array)
    {
        $this->_model = $this->_model->whereNotIn($field, $array);
        return $this;
    }

    protected function whereRaw($query)
    {
        $this->_model = $this->_model->whereRaw($query);
        return $this;
    }

    protected function whereNull($field)
    {
        $this->_model = $this->_model->whereNull($field);
        return $this;
    }

    protected function whereNotNull($field)
    {
        $this->_model = $this->_model->whereNotNull($field);
        return $this;
    }

    protected function whereKeyValue($key, $value, $equal = '=')
    {
        $this->_model = $this->_model->where('key', $key)->where('value', $equal, $value);
        return $this;
    }

    protected function whereFields(array $array)
    {
        $this->_model = $this->_model->where($array);
        return $this;
    }

    protected function whereBetween($field, array $array)
    {
        $this->_model = $this->_model->whereBetween($field, $array);
        return $this;
    }

    protected function whereNotBetween($field, array $array)
    {
        $this->_model = $this->_model->whereNotBetween($field, $array);
        return $this;
    }

    protected function whereEmpty($field)
    {
        $this->_model = $this->_model->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
        return $this;
    }

    protected function whereNotEmpty($field)
    {
        $this->_model = $this->_model->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
        return $this;
    }

    protected function whereCommaExpressArray($field, $value)
    {
        $this->_model = $this->_model->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
        return $this;
    }

    protected function whereId($id)
    {
        $this->_model = $this->_model->where('id', $id);
        return $this;
    }

    protected function isActive($set = true)
    {
        $this->_model = $this->_model->where('is_active', $set ? 1 : 0);
        return $this;
    }

    protected function isAvailable($set = true)
    {
        $this->_model = $this->_model->where('is_available', $set ? 1 : 0);
        return $this;
    }

    protected function joinAvailable($set = true)
    {
        $this->_model = $this->_model->where('join_available', $set ? 1 : 0);
        return $this;
    }

    protected function onlyTrashed()
    {
        $this->_model = $this->_model->onlyTrashed();
        return $this;
    }

    protected function withTrashed()
    {
        $this->_model = $this->_model->withTrashed();
        return $this;
    }


}
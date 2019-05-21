<?php

namespace Luminee\Base\Foundations;

trait Where
{
    public function where($field, $value, $equal = '=')
    {
        $this->_model = $this->_model->where($field, $equal, $value);
        return $this;
    }

    public function orWhere($field, $value, $equal = '=')
    {
        $this->_model = $this->_model->orWhere($field, $equal, $value);
        return $this;
    }

    public function whereIn($field, array $array)
    {
        $this->_model = $this->_model->whereIn($field, $array);
        return $this;
    }

    public function whereNotIn($field, array $array)
    {
        $this->_model = $this->_model->whereNotIn($field, $array);
        return $this;
    }

    public function whereRaw($query)
    {
        $this->_model = $this->_model->whereRaw($query);
        return $this;
    }

    public function whereNull($field)
    {
        $this->_model = $this->_model->whereNull($field);
        return $this;
    }

    public function whereNotNull($field)
    {
        $this->_model = $this->_model->whereNotNull($field);
        return $this;
    }

    public function whereKeyValue($key, $value, $equal = '=')
    {
        $this->_model = $this->_model->where('key', $key)->where('value', $equal, $value);
        return $this;
    }

    public function whereFields(array $array)
    {
        $this->_model = $this->_model->where($array);
        return $this;
    }

    public function whereBetween($field, array $array)
    {
        $this->_model = $this->_model->whereBetween($field, $array);
        return $this;
    }

    public function whereNotBetween($field, array $array)
    {
        $this->_model = $this->_model->whereNotBetween($field, $array);
        return $this;
    }

    public function whereEmpty($field)
    {
        $this->_model = $this->_model->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
        return $this;
    }

    public function whereNotEmpty($field)
    {
        $this->_model = $this->_model->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
        return $this;
    }

    public function whereCommaExpressArray($field, $value)
    {
        $this->_model = $this->_model->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
        return $this;
    }

    public function whereId($id)
    {
        $this->_model = $this->_model->where('id', $id);
        return $this;
    }

    public function isActive($set = true)
    {
        $this->_model = $this->_model->where('is_active', $set ? 1 : 0);
        return $this;
    }

    public function isAvailable($set = true)
    {
        $this->_model = $this->_model->where('is_available', $set ? 1 : 0);
        return $this;
    }

    public function joinAvailable($set = true)
    {
        $this->_model = $this->_model->where('join_available', $set ? 1 : 0);
        return $this;
    }

    public function onlyTrashed()
    {
        $this->_model = $this->_model->onlyTrashed();
        return $this;
    }

    public function withTrashed()
    {
        $this->_model = $this->_model->withTrashed();
        return $this;
    }


}
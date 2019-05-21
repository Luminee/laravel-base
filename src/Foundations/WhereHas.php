<?php

namespace Luminee\Base\Foundations;

trait WhereHas
{
    public function whereHas($relation, $field, $value, $equal = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value, $equal) {
                $query->where($field, $equal, $value);
            });
        return $this;
    }

    public function whereHasMorph($relation, $field, $value, $equal = '=')
    {
        $this->_model = $this->_model
            ->whereHasMorph($relation, $field, $value, $equal);
        return $this;
    }

    public function whereHasIn($relation, $field, $array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $array) {
                $query->whereIn($field, $array);
            });
        return $this;
    }

    public function whereHasNotIn($relation, $field, $array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $array) {
                $query->whereNotIn($field, $array);
            });
        return $this;
    }

    public function whereHasNull($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNull($field);
            });
        return $this;
    }

    public function whereHasNotNull($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNotNull($field);
            });
        return $this;
    }

    public function whereHasEmpty($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
            });
        return $this;
    }

    public function whereHasNotEmpty($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
            });
        return $this;
    }

    public function whereHasBetween($relation, $field, $between)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $between) {
                $query->whereBetween($field, $between);
            });
        return $this;
    }

    public function whereHasKeyBetween($relation, $key, $between)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($key, $between) {
                $query->where('key', $key)->whereBetween('value', $between);
            });
        return $this;
    }

    public function whereHasKeyValue($relation, $key, $value, $equal = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($key, $value, $equal) {
                $query->where('key', $key)->where('value', $equal, $value);
            });
        return $this;
    }

    public function whereHasCommaExpressArray($relation, $field, $value)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value) {
                $query->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
            });
        return $this;
    }
}
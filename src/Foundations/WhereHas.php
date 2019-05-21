<?php

namespace Luminee\Base\Foundations;

trait WhereHas
{
    protected function whereHas($relation, $field, $value, $equal = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value, $equal) {
                $query->where($field, $equal, $value);
            });
        return $this;
    }

    protected function whereHasMorph($relation, $field, $value, $equal = '=')
    {
        $this->_model = $this->_model
            ->whereHasMorph($relation, $field, $value, $equal);
        return $this;
    }

    protected function whereHasIn($relation, $field, $array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $array) {
                $query->whereIn($field, $array);
            });
        return $this;
    }

    protected function whereHasNotIn($relation, $field, $array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $array) {
                $query->whereNotIn($field, $array);
            });
        return $this;
    }

    protected function whereHasNull($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNull($field);
            });
        return $this;
    }

    protected function whereHasNotNull($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNotNull($field);
            });
        return $this;
    }

    protected function whereHasEmpty($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
            });
        return $this;
    }

    protected function whereHasNotEmpty($relation, $field)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
            });
        return $this;
    }

    protected function whereHasBetween($relation, $field, $between)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $between) {
                $query->whereBetween($field, $between);
            });
        return $this;
    }

    protected function whereHasKeyBetween($relation, $key, $between)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($key, $between) {
                $query->where('key', $key)->whereBetween('value', $between);
            });
        return $this;
    }

    protected function whereHasKeyValue($relation, $key, $value, $equal = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($key, $value, $equal) {
                $query->where('key', $key)->where('value', $equal, $value);
            });
        return $this;
    }

    protected function whereHasCommaExpressArray($relation, $field, $value)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value) {
                $query->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
            });
        return $this;
    }
}
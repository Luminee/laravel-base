<?php

namespace Luminee\Base\Foundations;

trait With
{
    public function withRelated($relation)
    {
        if (!empty($relation)) $this->_model = $this->_model->with($relation);
        return $this;
    }

    public function withRelatedOnWrite($relation)
    {
        if (!empty($relation)) $this->_model = $this->_model->withRelatedOnWrite($relation);
        return $this;
    }

    public function withCertain($relation, Array $columns)
    {
        if (!empty($relation)) $this->_model = $this->_model->withCertain($relation, $columns);
        return $this;
    }

    public function withRelatedWhere($relation, $field, $value, $equal = '=')
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field, $value, $equal) {
                $value == null ? $query->whereRaw("($field is NULL or $field = '')")
                    : $query->where($field, $equal, $value);
            }]);
        }
        return $this;
    }

    public function withRelatedWhereIn($relation, $field, $array)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field, $array) {
                $query->whereIn($field, $array);
            }]);
        }
        return $this;
    }

    public function withRelatedWhereNotNull($relation, $field)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field) {
                $query->whereNotNull($field);
            }]);
        }
        return $this;
    }

    public function withRelationTrashed($relation)
    {
        $this->_model = $this->_model->with([$relation => function ($query) {
            $query->withTrashed();
        }]);
        return $this;
    }

    public function withRelationOnlyTrashed($relation)
    {
        $this->_model = $this->_model->with([$relation => function ($query) {
            $query->onlyTrashed();
        }]);
        return $this;
    }

    public function withRelatedOrderBy($relation, $order_by, $sort = 'asc')
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($order_by, $sort) {
                $query->orderBy($order_by, $sort);
            }]);
        }
        return $this;
    }


}
<?php

namespace Luminee\Base\Foundations;

trait With
{
    protected function withRelated($relation)
    {
        if (!empty($relation)) $this->_model = $this->_model->with($relation);
        return $this;
    }

    protected function withRelatedOnWrite($relation)
    {
        if (!empty($relation)) $this->_model = $this->_model->withRelatedOnWrite($relation);
        return $this;
    }

    protected function withCertain($relation, Array $columns)
    {
        if (!empty($relation)) $this->_model = $this->_model->withCertain($relation, $columns);
        return $this;
    }

    protected function withRelatedWhere($relation, $field, $value, $equal = '=')
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field, $value, $equal) {
                $value == null ? $query->whereRaw("($field is NULL or $field = '')")
                    : $query->where($field, $equal, $value);
            }]);
        }
        return $this;
    }

    protected function withRelatedWhereIn($relation, $field, $array)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field, $array) {
                $query->whereIn($field, $array);
            }]);
        }
        return $this;
    }

    protected function withRelatedWhereNotNull($relation, $field)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field) {
                $query->whereNotNull($field);
            }]);
        }
        return $this;
    }

    protected function withRelationTrashed($relation)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) {
                $query->withTrashed();
            }]);
        }
        return $this;
    }

    protected function withRelationOnlyTrashed($relation)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) {
                $query->onlyTrashed();
            }]);
        }
        return $this;
    }

    protected function withRelatedOrderBy($relation, $order_by, $sort = 'asc')
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($order_by, $sort) {
                $query->orderBy($order_by, $sort);
            }]);
        }
        return $this;
    }


}
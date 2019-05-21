<?php

namespace Luminee\Base\Foundations;

trait Get
{
    public function find($id)
    {
        if (!is_numeric($id)) return null;
        return $this->_model->find($id);
    }

    public function getFirst()
    {
        return $this->_model->first();
    }

    public function getCollection()
    {
        return $this->_model->get();
    }

    public function listField($field, $alias = null)
    {
        return $this->_model->lists($field, $alias);
    }

    public function getCount($columns = '*')
    {
        return $this->_model->count($columns);
    }

    public function getSum($columns)
    {
        return $this->_model->sum($columns);
    }

    public function getMax($columns)
    {
        return $this->_model->max($columns);
    }

    public function getMin($columns)
    {
        return $this->_model->min($columns);
    }

    public function getAvg($columns)
    {
        return $this->_model->avg($columns);
    }

    public function getPaginate($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        return $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
    }

    public function getPagination($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        $total = $this->_model->count($columns);
        $paginate = $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
        $paginate->total = $total;
        return $paginate;
    }

    public function getPaginationForUnion($perPage, $nowPage = 1)
    {
        $items = $this->_model->get();
        $slice = $items->slice($perPage * ($nowPage - 1), $perPage)->all();
        $paginate = new \Illuminate\Pagination\Paginator($slice, count($items), $perPage);
        $paginate->_total = count($items);
        return $paginate;
    }

    public function getCollectionOrPaginate(self $query, $params)
    {
        if (isset($params['perPage'])) {
            return $query->getPaginate($params['perPage'], isset($params['nowPage']) ? $params['nowPage'] : 1);
        } else {
            return $query->getCollection();
        }
    }
}
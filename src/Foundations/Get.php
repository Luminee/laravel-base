<?php

namespace Luminee\Base\Foundations;

trait Get
{
    protected function find($id)
    {
        if (!is_numeric($id)) return null;
        return $this->_model->find($id);
    }

    protected function getFirst()
    {
        return $this->_model->first();
    }

    protected function getCollection()
    {
        return $this->_model->get();
    }

    protected function listField($field, $alias = null)
    {
        $method = method_exists($this->_model, 'lists') ? 'lists' : 'pluck';
        return $this->_model->$method($field, $alias);
    }

    protected function getCount($columns = '*')
    {
        return $this->_model->count($columns);
    }

    protected function getSum($columns)
    {
        return $this->_model->sum($columns);
    }

    protected function getMax($columns)
    {
        return $this->_model->max($columns);
    }

    protected function getMin($columns)
    {
        return $this->_model->min($columns);
    }

    protected function getAvg($columns)
    {
        return $this->_model->avg($columns);
    }

    protected function getPaginate($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        return $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
    }

    protected function getPagination($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        $total = $this->_model->count($columns);
        $paginate = $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
        $paginate->total = $total;
        return $paginate;
    }

    protected function getPaginationForUnion($perPage, $nowPage = 1)
    {
        $items = $this->_model->get();
        $slice = $items->slice($perPage * ($nowPage - 1), $perPage)->all();
        $paginate = new \Illuminate\Pagination\Paginator($slice, count($items), $perPage);
        $paginate->_total = count($items);
        return $paginate;
    }

    protected function getCollectionOrPaginate(self $query, $params)
    {
        if (isset($params['perPage'])) {
            return $query->getPaginate($params['perPage'], isset($params['nowPage']) ? $params['nowPage'] : 1);
        } else {
            return $query->getCollection();
        }
    }
}
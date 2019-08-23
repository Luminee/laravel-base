<?php

namespace Luminee\Base\Repositories;

use Luminee\Base\Foundations\Get;
use Luminee\Base\Foundations\Join;
use Luminee\Base\Foundations\With;
use Luminee\Base\Foundations\Other;
use Luminee\Base\Foundations\Where;
use Luminee\Base\Foundations\WhereHas;
use Luminee\Base\Foundations\Structure;

class BaseRepository
{
    use Where, WhereHas, Get, With, Structure, Join, Other;

    private $_model;

    /**
     * the path of db models
     * @var string
     */
    protected $db_models_path;

    /**
     * the path of redis models
     * @var string
     */
    protected $redis_models_path;

    private function _bind($model, $use_redis = false)
    {
        if ($use_redis) {
            $_models = $this->redis_Models($model);
        } else {
            $_models = include $this->db_models_path . '/_models.php';
        }

        $Model = $_models[$model];
        app()->singleton($Model, function () use ($Model) {
            return new $Model;
        });
        return app($Model);
    }

    private function redis_Models($model)
    {
        $_models = [];
        if (file_exists($this->redis_models_path . '/_models.php')) {
            $_models = include $this->redis_models_path . '/_models.php';
        }
        if (!isset($_models[$model])) {
            $_models = include $this->db_models_path . '/_models.php';
        }
        return $_models;
    }

    protected function _setModel($model_name, $path)
    {
        $_models = include $path . '/_models.php';
        $Model = $_models[$model_name];
        app()->singleton($Model, function () use ($Model) {
            return new $Model;
        });
        $this->_model = app($Model);
        return $this;
    }

    // 对外接口方法
    protected function setModel($model_name, $use_redis = false)
    {
        $this->_model = $this->_bind($model_name, $use_redis);
        return $this;
    }

    protected function onWriteConnection()
    {
        $this->_model = $this->_model->onWriteConnection();
        return $this;
    }

    protected function toSql()
    {
        return $this->_model->toSql();
    }

    protected function getBindings()
    {
        return $this->_model->getBindings();
    }

    protected function mergeBindings($query)
    {
        $this->_model->mergeBindings($query);
        return $this;
    }

    protected function select($select)
    {
        $this->_model = $this->_model->select($select);
        return $this;
    }

    protected function toModelCollection($data, $model)
    {
        $collection = [];
        foreach ($data as $item) {
            $Model = new $model;
            foreach ($item as $key => $value) {
                $Model->$key = $value;
            }
            $collection[] = $Model;
        }
        return collect($collection);
    }

}

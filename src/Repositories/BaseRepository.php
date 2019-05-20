<?php

namespace Luminee\Base\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class BaseRepository
{
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

    /**
     * @author LuminEe
     */
    private function _bind($model, $use_redis = false)
    {
        if ($use_redis) {
            $_models = [];
            if (file_exists($this->redis_models_path . '/_models.php')) {
                $_models = include $this->redis_models_path . '/_models.php';
            }
            if (!isset($_models[$model])) {
                $_models = include $this->db_models_path . '/_models.php';
            }
        } else {
            $_models = include $this->db_models_path . '/_models.php';
        }

        $Model = $_models[$model];
        app()->singleton($Model, function () use ($Model) {
            return new $Model;
        });
        return app($Model);
    }

    /**
     * @author LuminEe
     */
    protected function _setModel($model_name, $path)
    {
        $_models = include $path . '/_models.php';
        $Model   = $_models[$model_name];
        app()->singleton($Model, function () use ($Model) {
            return new $Model;
        });
        $this->_model = app($Model);
        return $this;
    }

    // 内调功能方法

    /**
     * @author LuminEe
     */
    protected function get_model()
    {
        if ($this->_model instanceof Builder) {
            return $this->_model->getModel();
        }
        return $this->_model;
    }

    /**
     * @author LuminEe
     */
    protected function structureModel($model_name)
    {
        $string  = explode(':', $model_name);
        $class   = get_class($this->get_model());
        $str_arr = explode('\\', $class);
        if (count($string) == 1) {
            $str_arr[3] = ucfirst($string[0]);
        } else {
            $str_arr[2] = ucfirst($string[0]);
            $str_arr[3] = ucfirst($string[1]);
        }
        $class = implode('\\', $str_arr);
        return new $class;
    }

    /**
     * @author LuminEe
     */
    protected function getTableField($model_field)
    {
        if (strpos($model_field, ':') !== false) {
            $ex_field = explode('.', $model_field);
            if (count($ex_field) == 2) {
                $table       = $this->structureModel($ex_field[0])->getTable();
                $table_field = $table . '.' . $ex_field[1];
                return $table_field;
            }
        }
        return $model_field;
    }

    /**
     * @author LuminEe
     */
    protected function setTimeQuery($query, $time = null, $time_node = null, $created_at = 'created_at')
    {
        if ($time !== null) {
            $query = $query->whereBetween($created_at, $time);
        }
        if ($time_node !== null) {
            $query = $query->whereField($created_at, $time_node, '<=');
        }
        return $query;
    }

    /**
     * @author LuminEe
     */
    protected function queryOrderBy($query, $order_by)
    {
        foreach ($order_by as $field => $sort) {
            $query->orderBy($field, $sort);
        }
        return $query;
    }

    /**
     * @param self $query
     * @param array $params
     * @return array $success
     * @author LuminEe
     */
    protected function getCollectionOrPaginate(self $query, $params)
    {
        if (isset($params['perPage'])) {
            $nowPage = isset($params['nowPage']) ? $params['nowPage'] : 1;
            return $this->success($query->getPaginate($params['perPage'], $nowPage), 'pagination');
        } else {
            return $this->success($query->getCollection(), 'collection');
        }
    }

    // 对外接口方法

    /**
     * @author LuminEe
     */
    protected function setModel($model_name, $use_redis = false)
    {
        $this->_model = $this->_bind($model_name, $use_redis);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function setSubTable($query, $alias)
    {
        $model        = \DB::table(\DB::raw("({$query->toSql()}) as $alias"));
        $this->_model = $model->addBinding($query->getBindings());
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function onWriteConnection()
    {
        $this->_model = $this->_model->onWriteConnection();
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function getModel()
    {
        return $this->_model;
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

    /**
     * @author LuminEe
     */
    protected function union($query)
    {
        $this->_model = $this->_model->union($query);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelated($relation)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with($relation);
        }
        return $this;
    }

    protected function withRelatedOnWrite($relation)
    {
        if (!empty($relation)) $this->_model = $this->_model->withRelatedOnWrite($relation);
        return $this;
    }

    protected function withRelatedMaybeWhere($relation)
    {
        if (empty($relation)) {
            return $this;
        }
        if (!is_array($relation)) {
            $this->_model = $this->_model->with($relation);
            return $this;
        }
        foreach ($relation as $item) {
            if (is_array($item)) {
                $this->withRelatedWhere($item[0], $item[1], $item[2], isset($item[3]) ? $item[3] : '=');
            } else {
                $this->_model = $this->_model->with($item);
            }
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelatedOrderBy($relation, $order_by, $sort = 'asc')
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($order_by, $sort) {
                $query->orderBy($order_by, $sort);
            }]);
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withCertain($relation, Array $columns)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->withCertain($relation, $columns);
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelatedWhere($relation, $field, $value, $operation = '=')
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field, $value, $operation) {
                if ($value != null) {
                    $query->where($field, $operation, $value);
                } else {
                    $query->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
                }
            }]);
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelatedWhereIn($relation, $field, $array)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field, $array) {
                $query->whereIn($field, $array);
            }]);
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelatedWhereNotNull($relation, $field)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($field) {
                $query->whereNotNull($field);
            }]);
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelationTrashed($relation)
    {
        $this->_model = $this->_model->with([$relation => function ($query) {
            $query->withTrashed();
        }]);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelationsTrashed($relations)
    {
        foreach ($relations as $relation) {
            $this->_model = $this->_model->with([$relation => function ($query) {
                $query->withTrashed();
            }]);
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withTrashed()
    {
        $this->_model = $this->_model->withTrashed();
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function withRelationOnlyTrashed($relation)
    {
        $this->_model = $this->_model->with([$relation => function ($query) {
            $query->onlyTrashed();
        }]);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function onlyTrashed()
    {
        $this->_model = $this->_model->onlyTrashed();
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function distinct()
    {
        $this->_model = $this->_model->distinct();
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function select($select)
    {
        if (!is_array($select)) {
            $select = $this->getTableField($select);
        } else {
            foreach ($select as &$field) {
                $field = $this->getTableField($field);
            }
        }
        $this->_model = $this->_model->select($select);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function selectDistinct($field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->selectRaw('distinct (' . $field . ')');
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function selectRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->selectRaw($query, $param);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function addSelect($field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->addSelect($field);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function innerJoin($table, $one, $operator, $two)
    {
        $this->_model = $this->_model->join($table, $one, $operator, $two);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function leftJoin($table, $one, $operator, $two)
    {
        $this->_model = $this->_model->join($table, $one, $operator, $two, 'left');
        return $this;
    }

    protected function innerJoinOnAnd($table, array $first_on, array $second_on)
    {
        $this->_model = $this->_model->join($table, function ($join) use ($first_on, $second_on) {
            $join->on($first_on[0], $first_on[1], $first_on[2])
                ->where($second_on[0], $second_on[1], $second_on[2]);
        }, null, null, 'inner');
        return $this;
    }

    protected function leftJoinOnAnd($table, array $first_on, array $second_on)
    {
        $this->_model = $this->_model->join($table, function ($join) use ($first_on, $second_on) {
            $join->on($first_on[0], $first_on[1], $first_on[2])
                ->where($second_on[0], $second_on[1], $second_on[2]);
        }, null, null, 'left');
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function joinModel($model, $one_column, $operator = '=', $two_column = 'id', $type = 'inner')
    {
        $table_one    = $this->get_model()->getTable();
        $table_two    = $this->structureModel($model)->getTable();
        $this->_model = $this->_model->join($table_two, $table_one . '.' . $one_column, $operator, $table_two . '.' . $two_column, $type);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereField($field, $value, $equal = null)
    {
        $field        = $this->getTableField($field);
        $equal        = ($equal == null) ? '=' : $equal;
        $this->_model = $this->_model->where($field, $equal, $value);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereKeyValue($key, $value, $equal = null)
    {
        $equal        = ($equal == null) ? '=' : $equal;
        $this->_model = $this->_model->where('key', $key)->where('value', $equal, $value);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function orWhereField($field, $value, $equal = null)
    {
        $field        = $this->getTableField($field);
        $equal        = ($equal == null) ? '=' : $equal;
        $this->_model = $this->_model->orWhere($field, $equal, $value);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereOrWhere($field, $value, $orField, $orValue, $equal = '=', $orEqual = '=')
    {
        $this->_model = $this->_model
            ->where(function ($query) use ($field, $equal, $value, $orField, $orEqual, $orValue) {
                $query->where($field, $equal, $value)
                    ->orWhere($orField, $orEqual, $orValue);
            });
        return $this;
    }

    protected function whereHasInOrWhere($relation, $first, $array, $second, $value, $equal = '=')
    {
        $this->_model = $this->_model
            ->where(function ($query) use ($relation, $first, $array, $second, $value, $equal) {
                $query->whereHas($relation, function ($query) use ($first, $array) {
                    $query->whereIn($first, $array);
                })->orWhere($second, $equal, $value);
            });
        return $this;
    }

    protected function whereHasInAndWhere_Or_Where($relation, $first, $second, $third)
    {
        $this->_model = $this->_model
            ->where(function ($query) use ($relation, $first, $second, $third) {
                $query->whereHas($relation, function ($query) use ($first, $second) {
                    $query->whereIn($first[0], $first[1]);
                    if (is_array($second[1])) {
                        $query->whereIn($second[0], $second[1]);
                    } else {
                        $query->where($second[0], isset($second[2]) ? $second[2] : '=', $second[1]);
                    }
                });
                if (is_array($third[1])) {
                    $query->orWhereIn($third[0], $third[1]);
                } else {
                    $query->orWhere($third[0], isset($third[2]) ? $third[2] : '=', $third[1]);
                }
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereOr($fields, $values, $equals = [])
    {
        $this->_model = $this->_model
            ->where(function ($query) use ($fields, $values, $equals) {
                foreach ($fields as $key => $item) {
                    $equal = isset($equals[$key]) ? $equals[$key] : '=';
                    if ($key == 0) {
                        $query->where($fields[$key], $equal, $values[$key]);
                    } else {
                        $query->orWhere($fields[$key], $equal, $values[$key]);
                    }
                }
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereFields(array $whereArray)
    {
        $this->_model = $this->_model->where($whereArray);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereBetween($field, array $valueArray)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereBetween($field, $valueArray);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereNotBetween($field, array $valueArray)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereNotBetween($field, $valueArray);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereIn($field, array $valueArray)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereIn($field, $valueArray);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereNotIn($field, array $valueArray)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereNotIn($field, $valueArray);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->whereRaw($query, $param);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereNull($field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereNull($field);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereNotNull($field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereNotNull($field);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasNull($relation, $field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNull($field);
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasNotNull($relation, $field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNotNull($field);
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasEmpty($relation, $field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasNotEmpty($relation, $field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereEmpty($field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereNotEmpty($field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function isActive($set = true)
    {
        $set          = $set ? 1 : 0;
        $this->_model = $this->_model->where('is_active', $set);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function isAvailable($set = true)
    {
        $set          = $set ? 1 : 0;
        $this->_model = $this->_model->where('is_available', $set);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function joinAvailable($set = true)
    {
        $set          = $set ? 1 : 0;
        $this->_model = $this->_model->where('join_available', $set);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function hasRelation($relation, $count = null, $operator = '=')
    {
        if (!isset($count)) {
            $this->_model = $this->_model->has($relation);
        } else {
            $this->_model = $this->_model->has($relation, $operator, $count);
        }
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function hasRelationMorph($relation, $count = 1, $operator = '>=')
    {
        $this->_model = $this->_model->hasRelationMorph($relation, $count, $operator);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHas($relation, $field, $value, $operator = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value, $operator) {
                $query->where($field, $operator, $value);
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasMorph($relation, $field, $value, $operator = '=')
    {
        $this->_model = $this->_model
            ->whereHasMorph($relation, $field, $value, $operator);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasIn($relation, $field, $value_array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value_array) {
                $query->whereIn($field, $value_array);
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasNotIn($relation, $field, $value_array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value_array) {
                $query->whereNotIn($field, $value_array);
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasBetween($relation, $field, $between)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $between) {
                $query->whereBetween($field, $between);
            });
        return $this;
    }

    /**
     * @param string $relation
     * @param string $key
     * @param array $between
     * @return self $this
     * @author LuminEe
     */
    protected function whereHasKeyBetween($relation, $key, $between)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($key, $between) {
                $query->where('key', $key)->whereBetween('value', $between);
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasKeyValue($relation, $key, $value, $operator = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($key, $value, $operator) {
                $query->where('key', $key)->where('value', $operator, $value);
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasCommaExpressArray($relation, $field, $value)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value) {
                $query->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
            });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereHasCommaExpressArrayMorph($relation, $field, $value)
    {
        $this->_model = $this->_model->where(function ($query) use ($relation, $field, $value) {
            $morphType = $query->getModel()->$relation()->getMorphType();
            foreach (array_keys(Relation::morphMap()) as $key => $type) {
                $where = $key == 0 ? 'where' : 'orWhere';
                $query->$where(function ($query) use ($relation, $morphType, $type, $field, $value) {
                    $query->where($morphType, $type)
                        ->whereHas($type, function ($query) use ($field, $value) {
                            $query->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
                        });
                });
            }
        });
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function havingField($field, $value, $equal = null)
    {
        $this->_model = $this->_model->having($field, $equal, $value);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function havingRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->havingRaw($query, $param);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function limit($rows, $offset = 0)
    {
        $this->_model = $this->_model->skip($offset)->take($rows);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function orderBy($field, $sort = 'asc')
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->orderBy($field, $sort);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function orderByRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->orderByRaw($query, $param);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function groupBy($field)
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->groupBy($field);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function findById($id)
    {
        if (!is_numeric($id)) {
            return null;
        }
        return $this->_model->find($id);
    }

    /**
     * @author LuminEe
     */
    protected function listField($field, $alias = null)
    {
        return $this->_model->lists($field, $alias);
    }

    /**
     * @author LuminEe
     */
    protected function getFirst()
    {
        return $this->_model->first();
    }

    /**
     * @author LuminEe
     */
    protected function getCollection()
    {
        return $this->_model->get();
    }

    /**
     * @author weiyuzhu
     */
    protected function getCollectionByFileds($fileds = [])
    {
        if (count($fileds)) {
            return $this->_model->get($fileds);
        } else {
            return $this->_model->get();
        }

    }

    /**
     * @author LuminEe
     */
    protected function getPagination($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        $_total           = $this->_model->count($columns);
        $paginate         = $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
        $paginate->_total = $_total;
        return $paginate;
    }

    /**
     * @author LuminEe
     */
    protected function getPaginate($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        $paginate         = $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
        $paginate->_total = $paginate->total();
        return $paginate;
    }

    /**
     * @author LuminEe
     */
    protected function getPaginationForUnion($perPage, $nowPage = 1)
    {
        $items            = $this->_model->get();
        $slice            = $items->slice($perPage * ($nowPage - 1), $perPage)->all();
        $paginate         = new \Illuminate\Pagination\Paginator($slice, count($items), $perPage);
        $paginate->_total = count($items);
        return $paginate;
    }

    /**
     * @author LuminEe
     */
    protected function getCount($columns = '*')
    {
        return $this->_model->count($columns);
    }

    /**
     * @author LuminEe
     */
    protected function getSum($columns)
    {
        return $this->_model->sum($columns);
    }

    /**
     * @author LuminEe
     */
    protected function getMax($columns)
    {
        return $this->_model->max($columns);
    }

    /**
     * @author LuminEe
     */
    protected function getMin($columns)
    {
        return $this->_model->min($columns);
    }

    /**
     * @author LuminEe
     */
    protected function getAvg($columns)
    {
        return $this->_model->avg($columns);
    }

    /**
     * @author LuminEe
     */
    protected function createEntityWithData(array $data)
    {
        return $this->_model->create($data);
    }

    /**
     * @author LuminEe
     */
    protected function batchInsert($data)
    {
        return $this->_model->insert($data);
    }

    /**
     * @author LuminEe
     */
    protected function insertAndGetIds($data)
    {
        if (!$this->_model->insert($data)) return null;
        $last = \DB::getPdo()->lastInsertId();
        $ids  = [];
        for ($i = 0; $i < count($data); $i++) {
            $ids[] = $last + $i;
        }
        return $ids;
    }

    /**
     * @author LuminEe
     */
    protected function firstOrCreate($data)
    {
        return $this->_model->firstOrCreate($data);
    }

    /**
     * @author LuminEe
     */
    public function replaceColumn($column, $search, $replace)
    {
        return $this->_model->update([$column => \DB::raw("REPLACE($column, '$search', '$replace')")]);
    }

    /**
     * @author LuminEe
     */
    protected function batchUpdate($multipleData = array())
    {
        if (empty($multipleData)) {
            return false;
        }
        $updateColumn    = array_keys($multipleData[0]);
        $referenceColumn = $updateColumn[0]; //e.g id
        unset($updateColumn[0]);
        $whereIn = "";

        $q = "UPDATE " . $this->get_model()->getTable() . " SET ";
        foreach ($updateColumn as $uColumn) {
            $q .= $uColumn . " = CASE ";

            foreach ($multipleData as $data) {
                $q .= "WHEN " . $referenceColumn . " = " . $data[$referenceColumn] . " THEN '" . $data[$uColumn] . "' ";
            }
            $q .= "ELSE " . $uColumn . " END, ";
        }
        foreach ($multipleData as $data) {
            $whereIn .= "'" . $data[$referenceColumn] . "', ";
        }
        $q = rtrim($q, ", ") . " WHERE " . $referenceColumn . " IN (" . rtrim($whereIn, ', ') . ")";

        // Update
        return \DB::update(\DB::raw($q));
    }

    /**
     * @author weiyuzhu
     */
    protected function batchUpdateByFileds($multipleData = array(), $fileds = [])
    {
        if (empty($multipleData)) {
            return false;
        }
        $updateColumn    = array_keys($multipleData[0]);
        $referenceColumn = $updateColumn[0]; //e.g id
        unset($updateColumn[0]);
        $whereIn = "";

        $q = "UPDATE " . $this->get_model()->getTable() . " SET ";
        foreach ($updateColumn as $uColumn) {
            $q .= $uColumn . " = CASE ";

            foreach ($multipleData as $data) {
                $q .= "WHEN " . $referenceColumn . " = " . $data[$referenceColumn] . " THEN '" . $data[$uColumn] . "' ";
            }
            $q .= "ELSE " . $uColumn . " END, ";
        }
        foreach ($multipleData as $data) {
            $whereIn .= "'" . $data[$referenceColumn] . "', ";
        }
        $q = rtrim($q, ", ") . " WHERE " . $referenceColumn . " IN (" . rtrim($whereIn, ', ') . ")";

        foreach ($fileds as $filed) {
            foreach ($filed as $key => $value) {
                $q .= " AND " . $key . "  = '" . $value . "'";
            }
        }

        // Update
        return \DB::update(\DB::raw($q));
    }

    /**
     * @author LuminEe
     */
    protected function restore()
    {
        return $this->_model->restore();
    }

    /**
     * @throws \Exception
     * @author LuminEe
     */
    protected function updateIncrement($model_instance, $field, $count = 1, array $array = [])
    {
        $this->checkInstance($model_instance);
        $model_instance->increment($field, $count, $array);
        return $model_instance;
    }

    /**
     * @author LuminEe
     */
    protected function batchIncrement($field, $count = 1, array $array = [])
    {
        return $this->_model->increment($field, $count, $array);
    }

    /**
     * @throws \Exception
     * @author LuminEe
     */
    protected function updateDecrement($model_instance, $field, $count = 1, array $array = [])
    {
        $this->checkInstance($model_instance);
        $model_instance->decrement($field, $count, $array);
        return $model_instance;
    }

    /**
     * @author Weiyuzhu
     */
    protected function batchDecrement($field, $count = 1, array $array = [])
    {
        return $this->_model->decrement($field, $count, $array);
    }

    /**
     * @author LuminEe
     */
    protected function batchUpdateByData($data)
    {
        return $this->_model->update($data);
    }

    /**
     * @author LuminEe
     */
    protected function updateRawByIdsAndData($ids, $data)
    {
        return \DB::table($this->_model->getTable())->whereIn('id', $ids)->update($data);
    }

    /**
     * @throws \Exception
     * @author LuminEe
     */
    protected function updateModelByData($model, Array $data)
    {
        return $this->updateEntityByModelInstanceWithData($model, $data);
    }

    /**
     * @throws \Exception
     * @author LuminEe
     */
    protected function updateEntityByModelInstanceWithData($model_instance, Array $data)
    {
        $this->checkInstance($model_instance);
        $model_instance->fill($data)->save();
        return $model_instance;
    }

    /**
     * @throws \Exception
     * @author LuminEe
     */
    protected function refreshUpdated($model_instance)
    {
        $this->checkInstance($model_instance);
        return $model_instance->touch();
    }

    /**
     * @throws \Exception
     * @author LuminEe
     */
    protected function refreshCreated($model_instance)
    {
        $this->checkInstance($model_instance);
        $model_instance->created_at = date('Y-m-d H:i:s');
        $model_instance->save();
        return $model_instance;
    }

    /**
     * @throws \Exception
     */
    protected function checkInstance($instance)
    {
        if (empty($instance) || !is_object($instance)) throw new \Exception('Update Null Error!');
    }

    /**
     * @author LuminEe
     */
    protected function deleteEntityById($id)
    {
        if (!is_numeric($id)) {
            return null;
        }
        return (bool)$this->_model->destroy($id);
    }

    /**
     * @author weiyuzhu
     */
    protected function deleteEntityByIds($ids)
    {
        if (!is_array($ids)) {
            return null;
        }
        return (bool)$this->_model->destroy($ids);
    }

    /**
     * @author LuminEe
     */
    protected function deleteWhere($return_count = false)
    {
        if (strstr($this->_model->toSql(), ' 0 = 1 ') !== false) {
            return 0;
        }
        $delete = $this->_model->delete();
        return $return_count ? $delete : (bool)$delete;
    }

    /**
     * @author LuminEe
     */
    protected function forceDeleteWhere()
    {
        return (bool)$this->_model->forceDelete();
    }

    /**
     * @author LuminEe
     */
    protected function whereCommaExpressArray($field, $value)
    {
        $this->_model = $this->_model->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function whereNotCommaExpressArray($field, $value)
    {
        $this->_model = $this->_model->whereRaw("(NOT FIND_IN_SET('" . $value . "'," . $field . ") OR " . $field . " IS NULL)");
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function orderByStringAsInt($field, $sort = 'asc')
    {
        $this->_model = $this->_model->orderByRaw("CAST(`" . $field . "` AS DECIMAL) " . $sort);
        return $this;
    }

    /**
     * @author LuminEe
     */
    protected function orderByArrayList($field, $array, $sort = 'asc')
    {
        $field        = $this->getTableField($field);
        $this->_model = $this->_model->orderByRaw("FIND_IN_SET(" . $field . ",'" . $array . "') " . $sort);
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

    /**
     * @author LuminEe
     */
    protected function success($data, $format = 'model')
    {
        if ($format == 'pagination') {
            return ['status' => 1, 'format' => $format, 'data' => $data, '_total' => $data->_total];
        }
        return ['status' => 1, 'format' => $format, 'data' => $data];
    }


    /**
     * @author LuminEe
     */
    protected function error($message)
    {
        return ['status' => 0, 'message' => $message];
    }


}

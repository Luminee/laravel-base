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

    // 内调功能方法

    protected function get_model()
    {
        if ($this->_model instanceof Builder) {
            return $this->_model->getModel();
        }
        return $this->_model;
    }

    protected function structureModel($model_name)
    {
        $string = explode(':', $model_name);
        $class = get_class($this->get_model());
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

    protected function getTableField($model_field)
    {
        if (strpos($model_field, ':') !== false) {
            $ex_field = explode('.', $model_field);
            if (count($ex_field) == 2) {
                return $this->structureModel($ex_field[0])->getTable() . '.' . $ex_field[1];
            }
        }
        return $model_field;
    }

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

    protected function queryOrderBy($query, $order_by)
    {
        foreach ($order_by as $field => $sort) {
            $query->orderBy($field, $sort);
        }
        return $query;
    }

    protected function queryGroupBy($query, $group_by)
    {
        foreach ($group_by as $value) {
            $query->groupBy($value);
        }
    }

    protected function getCollectionOrPaginate(self $query, $params)
    {
        if (isset($params['perPage'])) {
            $nowPage = isset($params['nowPage']) ? $params['nowPage'] : 1;
            return $query->getPaginate($params['perPage'], $nowPage);
        } else {
            return $query->getCollection();
        }
    }

    // 对外接口方法
    protected function setModel($model_name, $use_redis = false)
    {
        $this->_model = $this->_bind($model_name, $use_redis);
        return $this;
    }

    protected function setSubTable($query, $alias)
    {
        $model = \DB::table(\DB::raw("({$query->toSql()}) as $alias"));
        $this->_model = $model->addBinding($query->getBindings());
        return $this;
    }

    protected function onWriteConnection()
    {
        $this->_model = $this->_model->onWriteConnection();
        return $this;
    }

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

    protected function union($query)
    {
        $this->_model = $this->_model->union($query);
        return $this;
    }

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
        if (empty($relation)) return $this;
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

    protected function withRelatedOrderBy($relation, $order_by, $sort = 'asc')
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->with([$relation => function ($query) use ($order_by, $sort) {
                $query->orderBy($order_by, $sort);
            }]);
        }
        return $this;
    }

    protected function withCertain($relation, Array $columns)
    {
        if (!empty($relation)) {
            $this->_model = $this->_model->withCertain($relation, $columns);
        }
        return $this;
    }

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
        $this->_model = $this->_model->with([$relation => function ($query) {
            $query->withTrashed();
        }]);
        return $this;
    }

    protected function withRelationsTrashed($relations)
    {
        foreach ($relations as $relation) {
            $this->_model = $this->_model->with([$relation => function ($query) {
                $query->withTrashed();
            }]);
        }
        return $this;
    }

    protected function withTrashed()
    {
        $this->_model = $this->_model->withTrashed();
        return $this;
    }

    protected function withRelationOnlyTrashed($relation)
    {
        $this->_model = $this->_model->with([$relation => function ($query) {
            $query->onlyTrashed();
        }]);
        return $this;
    }

    protected function onlyTrashed()
    {
        $this->_model = $this->_model->onlyTrashed();
        return $this;
    }

    protected function distinct()
    {
        $this->_model = $this->_model->distinct();
        return $this;
    }

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

    protected function selectDistinct($field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->selectRaw('distinct (' . $field . ')');
        return $this;
    }

    protected function selectRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->selectRaw($query, $param);
        return $this;
    }

    protected function addSelect($field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->addSelect($field);
        return $this;
    }

    protected function innerJoin($table, $one, $operator, $two)
    {
        $this->_model = $this->_model->join($table, $one, $operator, $two);
        return $this;
    }

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

    protected function joinModel($model, $one_column, $operator = '=', $two_column = 'id', $type = 'inner')
    {
        $table_one = $this->get_model()->getTable();
        $table_two = $this->structureModel($model)->getTable();
        $this->_model = $this->_model->join($table_two, $table_one . '.' . $one_column, $operator, $table_two . '.' . $two_column, $type);
        return $this;
    }

    protected function whereField($field, $value, $equal = '=')
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->where($field, $equal, $value);
        return $this;
    }

    protected function whereKeyValue($key, $value, $equal = '=')
    {
        $this->_model = $this->_model->where('key', $key)->where('value', $equal, $value);
        return $this;
    }

    protected function orWhereField($field, $value, $equal = '=')
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->orWhere($field, $equal, $value);
        return $this;
    }

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

    protected function whereOr($fields, $values, $equals = [])
    {
        $this->_model = $this->_model
            ->where(function ($query) use ($fields, $values, $equals) {
                foreach ($fields as $key => $item) {
                    $equal = isset($equals[$key]) ? $equals[$key] : '=';
                    $where = $key == 0 ? 'where' : 'orWhere';
                    $query->$where($fields[$key], $equal, $values[$key]);
                }
            });
        return $this;
    }

    protected function whereFields(array $whereArray)
    {
        $this->_model = $this->_model->where($whereArray);
        return $this;
    }

    protected function whereBetween($field, array $valueArray)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereBetween($field, $valueArray);
        return $this;
    }

    protected function whereNotBetween($field, array $valueArray)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereNotBetween($field, $valueArray);
        return $this;
    }

    protected function whereIn($field, array $valueArray)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereIn($field, $valueArray);
        return $this;
    }

    protected function whereNotIn($field, array $valueArray)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereNotIn($field, $valueArray);
        return $this;
    }

    protected function whereRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->whereRaw($query, $param);
        return $this;
    }

    protected function whereNull($field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereNull($field);
        return $this;
    }

    protected function whereNotNull($field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereNotNull($field);
        return $this;
    }

    protected function whereHasNull($relation, $field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNull($field);
            });
        return $this;
    }

    protected function whereHasNotNull($relation, $field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereNotNull($field);
            });
        return $this;
    }

    protected function whereHasEmpty($relation, $field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
            });
        return $this;
    }

    protected function whereHasNotEmpty($relation, $field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field) {
                $query->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
            });
        return $this;
    }

    protected function whereEmpty($field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereRaw("(" . $field . " is NULL or " . $field . " = '')");
        return $this;
    }

    protected function whereNotEmpty($field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->whereRaw("(" . $field . " is not NULL and " . $field . " <> '')");
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

    protected function hasRelation($relation, $count = null, $operator = '=')
    {
        if (!isset($count)) {
            $this->_model = $this->_model->has($relation);
        } else {
            $this->_model = $this->_model->has($relation, $operator, $count);
        }
        return $this;
    }

    protected function hasRelationMorph($relation, $count = 1, $operator = '>=')
    {
        $this->_model = $this->_model->hasRelationMorph($relation, $count, $operator);
        return $this;
    }

    protected function whereHas($relation, $field, $value, $operator = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value, $operator) {
                $query->where($field, $operator, $value);
            });
        return $this;
    }

    protected function whereHasMorph($relation, $field, $value, $operator = '=')
    {
        $this->_model = $this->_model
            ->whereHasMorph($relation, $field, $value, $operator);
        return $this;
    }

    protected function whereHasIn($relation, $field, $value_array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value_array) {
                $query->whereIn($field, $value_array);
            });
        return $this;
    }

    protected function whereHasNotIn($relation, $field, $value_array)
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($field, $value_array) {
                $query->whereNotIn($field, $value_array);
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

    protected function whereHasKeyValue($relation, $key, $value, $operator = '=')
    {
        $this->_model = $this->_model
            ->whereHas($relation, function ($query) use ($key, $value, $operator) {
                $query->where('key', $key)->where('value', $operator, $value);
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

    protected function havingField($field, $value, $equal = null)
    {
        $this->_model = $this->_model->having($field, $equal, $value);
        return $this;
    }

    protected function havingRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->havingRaw($query, $param);
        return $this;
    }

    protected function limit($rows, $offset = 0)
    {
        $this->_model = $this->_model->skip($offset)->take($rows);
        return $this;
    }

    protected function orderBy($field, $sort = 'asc')
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->orderBy($field, $sort);
        return $this;
    }

    protected function orderByRaw($query, Array $param = [])
    {
        $this->_model = $this->_model->orderByRaw($query, $param);
        return $this;
    }

    protected function groupBy($field)
    {
        $field = $this->getTableField($field);
        $this->_model = $this->_model->groupBy($field);
        return $this;
    }

    protected function find($id)
    {
        if (!is_numeric($id)) {
            return null;
        }
        return $this->_model->find($id);
    }

    protected function listField($field, $alias = null)
    {
        return $this->_model->lists($field, $alias);
    }

    protected function getFirst()
    {
        return $this->_model->first();
    }

    protected function getCollection()
    {
        return $this->_model->get();
    }

    protected function getPagination($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        $_total = $this->_model->count($columns);
        $paginate = $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
        $paginate->_total = $_total;
        return $paginate;
    }

    protected function getPaginate($perPage, $nowPage = 1, $columns = ['*'], $pageName = 'page')
    {
        $paginate = $this->_model->paginate($perPage, $columns, $pageName, $nowPage);
        $paginate->_total = $paginate->total();
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

    protected function create(array $data)
    {
        return $this->_model->create($data);
    }

    protected function insert($data)
    {
        return $this->_model->insert($data);
    }

    protected function firstOrCreate($data)
    {
        return $this->_model->firstOrCreate($data);
    }

    public function replaceColumn($column, $search, $replace)
    {
        return $this->_model->update([$column => \DB::raw("REPLACE($column, '$search', '$replace')")]);
    }

    protected function batchUpdateByFields($multipleData = array(), $fileds = [])
    {
        if (empty($multipleData)) return false;
        $updateColumn = array_keys($multipleData[0]);
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

    protected function restore()
    {
        return $this->_model->restore();
    }

    protected function increment($field, $count = 1, array $array = [])
    {
        return $this->_model->increment($field, $count, $array);
    }

    protected function decrement($field, $count = 1, array $array = [])
    {
        return $this->_model->decrement($field, $count, $array);
    }

    protected function update($data)
    {
        return $this->_model->update($data);
    }

    /**
     * @throws \Exception
     */
    protected function updateModelByData($model, Array $data)
    {
        if (empty($instance) || !is_object($instance)) throw new \Exception('Update Null Error!');
        $model->fill($data)->save();
        return $model;
    }

    protected function refreshUpdated()
    {
        return $this->_model->touch();
    }

    protected function refreshCreated()
    {
        return $this->_model->update(['created_at' => date('Y-m-d H:i:s')]);
    }

    protected function deleteEntityById($id)
    {
        if (!is_numeric($id)) return null;
        return (bool)$this->_model->destroy($id);
    }

    protected function deleteWhere($return_count = false)
    {
        if (strstr($this->_model->toSql(), ' 0 = 1 ') !== false) return 0;
        $delete = $this->_model->delete();
        return $return_count ? $delete : (bool)$delete;
    }

    protected function forceDeleteWhere()
    {
        return (bool)$this->_model->forceDelete();
    }

    protected function whereCommaExpressArray($field, $value)
    {
        $this->_model = $this->_model->whereRaw("FIND_IN_SET('" . $value . "'," . $field . ")");
        return $this;
    }

    protected function orderByStringAsInt($field, $sort = 'asc')
    {
        $this->_model = $this->_model->orderByRaw("CAST(`" . $field . "` AS DECIMAL) " . $sort);
        return $this;
    }

    protected function orderByArrayList($field, $array, $sort = 'asc')
    {
        $field = $this->getTableField($field);
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

}

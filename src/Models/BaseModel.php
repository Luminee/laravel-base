<?php

namespace Luminee\Base\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

abstract class BaseModel extends Model
{
    public function scopeWithCertain($query, $relation, Array $columns)
    {
        return $query->with([$relation => function ($query) use ($columns) {
            $query->select(array_merge(['id'], $columns));
        }]);
    }
    
    public function scopeWithRelatedOnWrite($query, $relation)
    {
        return $query->with([$relation => function ($query) {
            $query->onWriteConnection();
        }]);
    }
    
    /**
     * Scope WhereHas Base On Polymorphic Relations
     *
     * @author LuminEe
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $relation
     * @param string $field
     * @param string $value
     * @param string $operation
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWhereHasMorph(Builder $query, $relation, $field, $value, $operation = '=')
    {
        return $query->where(function (Builder $query) use ($relation, $field, $value, $operation) {
            strstr($relation, '.') ? list($relation, $relations) = explode('.', $relation, 2) : null;
            $morphType = $query->getModel()->$relation()->getMorphType();
            foreach (array_keys(Relation::morphMap()) as $key => $type) {
                if (isset($relations)) {
                    $where = $key == 0 ? 'where' : 'orWhere';
                    $query->$where(function (Builder $query) use ($relations, $morphType, $type, $field, $value, $operation) {
                        $query->where($morphType, $type)
                            ->whereHas($type, function (Builder $query) use ($relations, $field, $value, $operation) {
                                $query->whereHas($relations, function (Builder $query) use ($field, $operation, $value) {
                                    $query->where($field, $operation, $value);
                                });
                            });
                    });
                } else {
                    $class = Relation::morphMap()[$type];
                    if (!in_array($field, (new $class)->fillable)) continue;
                    $where = $key == 0 ? 'where' : 'orWhere';
                    $query->$where(function (Builder $query) use ($morphType, $type, $field, $value, $operation) {
                        $query->where($morphType, $type)
                            ->whereHas($type, function (Builder $query) use ($field, $value, $operation) {
                                $query->where($field, $operation, $value);
                            });
                    });
                }
            }
        });
    }
    
    /**
     * Scope HasRelation Base On Polymorphic Relations
     *
     * @author LuminEe
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $relation
     * @param int $count
     * @param string $operator
     * @return \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeHasRelationMorph(Builder $query, $relation, $count = 1, $operator = '>=')
    {
        return $query->where(function (Builder $query) use ($relation, $count, $operator) {
            $morphType = $query->getModel()->$relation()->getMorphType();
            foreach (array_keys(Relation::morphMap()) as $key => $type) {
                $where = $key == 0 ? 'where' : 'orWhere';
                $query->$where(function (Builder $query) use ($morphType, $type, $count, $operator) {
                    $query->where($morphType, $type)->has($type, $operator, $count);
                });
            }
        });
    }
    
    /**
     * Get Attribute
     */
    public function getIdAttribute($value)
    {
        return (int)$value;
    }
    
    public function getAccountIdAttribute($value)
    {
        return (int)$value;
    }
    
    public function getIsActiveAttribute($value)
    {
        return (int)$value;
    }
    
    public function getIsAvailableAttribute($value)
    {
        return (int)$value;
    }
    
    public function getJoinAvailableAttribute($value)
    {
        return (int)$value;
    }
    
    public function getNameAttribute($value)
    {
        return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Set Attribute
     */
    public function setCodeAttribute($value)
    {
        $this->attributes['code'] = e($value);
    }
    
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = e($value);
    }
    
    public function setKeywordsAttribute($value)
    {
        $this->attributes['keywords'] = e($value);
    }
    
    public function setDescriptionAttribute($value)
    {
        $this->attributes['description'] = e($value);
    }
    
    public function setLabelAttribute($value)
    {
        $this->attributes['label'] = e($value);
    }
    
    public function setKeyAttribute($value)
    {
        $this->attributes['key'] = e($value);
    }
    
    public function setValueAttribute($value)
    {
        $this->attributes['value'] = e($value);
    }
    
    public function html_decode($str)
    {
        $search  = array("&#039;");
        $replace = array("'");
        return str_replace($search, $replace, $str);
    }
    
}
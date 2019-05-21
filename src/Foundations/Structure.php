<?php

namespace Luminee\Base\Foundations;

trait Structure
{
    protected function create(array $data)
    {
        return $this->_model->create($data);
    }

    protected function firstOrCreate($data)
    {
        return $this->_model->firstOrCreate($data);
    }

    protected function insert($data)
    {
        return $this->_model->insert($data);
    }

    protected function update($data)
    {
        return $this->_model->update($data);
    }

    protected function increment($field, $count = 1, array $array = [])
    {
        return $this->_model->increment($field, $count, $array);
    }

    protected function decrement($field, $count = 1, array $array = [])
    {
        return $this->_model->decrement($field, $count, $array);
    }

    protected function replaceColumn($column, $search, $replace)
    {
        return $this->update([$column => \DB::raw("REPLACE($column, '$search', '$replace')")]);
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

    protected function delete($id)
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

    protected function restore()
    {
        return $this->_model->restore();
    }

    protected function batchUpdateByFields($multipleData = array(), $fileds = [])
    {
        if (empty($multipleData)) return false;
        $updateColumn = array_keys($multipleData[0]);
        $referenceColumn = $updateColumn[0]; //e.g id
        unset($updateColumn[0]);
        $whereIn = "";

        $q = "UPDATE " . $this->_model->getTable() . " SET ";
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
}
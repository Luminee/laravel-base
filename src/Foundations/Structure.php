<?php

namespace Luminee\Base\Foundations;

trait Structure
{
    public function create(array $data)
    {
        return $this->_model->create($data);
    }

    public function firstOrCreate($data)
    {
        return $this->_model->firstOrCreate($data);
    }

    public function insert($data)
    {
        return $this->_model->insert($data);
    }

    public function update($data)
    {
        return $this->_model->update($data);
    }

    public function increment($field, $count = 1, array $array = [])
    {
        return $this->_model->increment($field, $count, $array);
    }

    public function decrement($field, $count = 1, array $array = [])
    {
        return $this->_model->decrement($field, $count, $array);
    }

    public function replaceColumn($column, $search, $replace)
    {
        return $this->_model->update([$column => \DB::raw("REPLACE($column, '$search', '$replace')")]);
    }

    /**
     * @throws \Exception
     */
    public function updateModelByData($model, Array $data)
    {
        if (empty($instance) || !is_object($instance)) throw new \Exception('Update Null Error!');
        $model->fill($data)->save();
        return $model;
    }

    public function delete($id)
    {
        if (!is_numeric($id)) return null;
        return (bool)$this->_model->destroy($id);
    }

    public function deleteWhere($return_count = false)
    {
        if (strstr($this->_model->toSql(), ' 0 = 1 ') !== false) return 0;
        $delete = $this->_model->delete();
        return $return_count ? $delete : (bool)$delete;
    }

    public function forceDeleteWhere()
    {
        return (bool)$this->_model->forceDelete();
    }

    public function restore()
    {
        return $this->_model->restore();
    }

    public function batchUpdateByFields($multipleData = array(), $fileds = [])
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
}
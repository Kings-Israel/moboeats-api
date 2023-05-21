<?php

namespace App\Traits\Admin;

use Illuminate\Support\Facades\Schema;

trait ColumnsTrait
{
    public static function getColumns(){
        $model = new static;
        $tableName = $model->getTable();
        $cols = \DB::getSchemaBuilder()->getColumnListing($tableName);
        $columns = [];
        foreach($cols as $key => $col){
            $dataType = Schema::getConnection()->getDoctrineColumn($tableName, $col)->getType()->getName();
            $length = Schema::getConnection()->getDoctrineColumn($tableName, $col)->getLength();
            $typeProperties = static::dataTypeProperties($dataType,$length);
            $columns[] = ['name' => $col, 'dataType' => $typeProperties['type'], 'element' => $typeProperties['element']];
        }
        return $columns;
    }
    public static function getTableColumns($tableName){
        $cols = \DB::getSchemaBuilder()->getColumnListing($tableName);
        $columns = [];
        foreach($cols as $key => $col){
            $dataType = Schema::getConnection()->getDoctrineColumn($tableName, $col)->getType()->getName();
            $length = Schema::getConnection()->getDoctrineColumn($tableName, $col)->getLength();
            $typeProperties = static::dataTypeProperties($dataType,$length);
            $columns[] = ['name' => $col, 'dataType' => $typeProperties['type'], 'element' => $typeProperties['element']];
        }
        return $columns;
    }
    //
    public static function dataTypeProperties($dataType,$length){
        $data = [];
        if($dataType == "bigint" || $dataType == "integer"){
            $data['type'] = "number";
            $data['element'] = "input";
        }
        elseif($dataType == "boolean"){
            $data['type'] = "boolean";
            $data['element'] = "checkbox";
        }
        elseif($dataType == "date"){
            $data['type'] = "date";
            $data['element'] = "input";
        }
        elseif($dataType == "datetime"){
            $data['type'] = "datetime-local";
            $data['element'] = "input";
        }
        elseif($length >= 300){
            $data['type'] = "text";
            $data['element'] = "textarea";
        }
        else{
            $data['type'] = "text";
            $data['element'] = "input";
        }
        return $data;
    }
}

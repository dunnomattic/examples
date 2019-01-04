<?php

class MSSQLToMySQL
{
    private $name;
    private $type;

    public function convert($column)
    {
        $this->type = $column['DATA_TYPE'];
        $this->name = $column['COLUMN_NAME'];
        $isNull = $column['IS_NULLABLE'] == 'YES' ? 1 : 0;
        $default = $column['COLUMN_DEFAULT'] == 'NULL' ? '' : $column['COLUMN_DEFAULT'];
        $methodName = "convert".ucfirst($this->type);
        $res =  $this->$methodName($this->name,$column);
        $output = $this->constructStatement($res);
    }

    private function constructStatement($res)
    {
        return "{$this->name} {$res} ";
    }

    private function convertChar($column)
    {
        $length = $column['CHARACTER_MAXIMUM_LENGTH'];
        return "VARCHAR ($length)";
    }

    private function convertMoney($column){
        $precision = $column['NUMERIC_PRECISION'];
        $scale = $column['NUMERIC_SCALE'];
        return "NUMERIC({$precision},{$scale})";
    }

    private function convertDatetime($column){
        return "DATETIME";
    }

    private function convertFloat($column){
        $precision = $column['NUMERIC_PRECISION'];
        $scale = $column['NUMERIC_SCALE'];
        return "FLOAT ({$precision},1)";
    }

    private function convertInt($column){
        $precision = $column['NUMERIC_PRECISION'];
        return "INT({$precision})";
    }

    private function convertSmallint($column){
        $precision = $column['NUMERIC_PRECISION'];
        return "SMALLINT({$precision})";
    }

    private function convertVarchar($column){
        $length = $column['CHARACTER_MAXIMUM_LENGTH'];
        return "VARCHAR ($length)";
    }

    private function convertVarbinary($column){
        $length = $column['CHARACTER_MAXIMUM_LENGTH'] > 0 ? $column['CHARACTER_MAXIMUM_LENGTH'] : 0;
        return "VARBINARY ($length)";
    }

}
<?php

namespace Helpers;

class ParamGetter {

    private $columns = array();
    private $tables = array();
    private $filters = array();
    private $params = array();

    private static $paramCount = 0;

    public function add($type, $data)
    {
        if (isset($this->$type) && !in_array($data, $this->$type)) {
            $array = &$this->$type;
            $array[] = $data;
        }
        return $this;
    }

    public function release() {
        $sql = 'SELECT ' . implode(',', $this->columns);
        $sql .= ' FROM ' . implode(' ', $this->tables);

        if ($this->filters) {
            $sql .= ' WHERE ' . implode(' AND ', $this->filters);
        }

        return array('sql' => $sql, 'params' => $this->params);
    }

    public function setParam($name, $value) {
        $this->params[$name] = $value;
    }

    public static function in_sql(ParamGetter $getter, $type, $filter, $params)
    {
        $filter .= ' IN(';
        foreach($params as $value) {
            $param = 'inparam' . self::$paramCount;
            $filter .= ':' . $param . ',';
            $getter->setParam($param, $value);
            self::$paramCount++;
        }

        $filter = rtrim($filter, ',') . ')';

        $getter->add($type, $filter);
    }

}
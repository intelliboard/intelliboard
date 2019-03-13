<?php

global $CFG;
require_once($CFG->dirroot . '/local/intelliboard/locallib.php');

class DataExtractor
{

    const MYSQL_MODE = 'mysqli';
    const POSTGRES_MODE = 'pgsql';

    private $requests = array();

    private $scenarios;
    private $arguments;
    private $params;
    private $rawValues;

    private $separator = "\n";

    private $mode = self::MYSQL_MODE;

    public function __construct($scenarios, $arguments, $params, $settings, $mode = null)
    {
        $this->scenarios = $scenarios;
        $this->rawValues = $arguments;
        $this->params = $params;
        $this->arguments = array();

        if ($mode) {
            $this->setMode($mode);
        }
    }

    public function extract()
    {

        $result = array('response' => array());

        foreach($this->scenarios as $key => $value) {
            $result['response'][$key] = $this->getData($value);
        }

        if (!empty($this->params['debug'])) {
            $result['debug'] = $this->requests;
        }

        return $result;

    }

    public function getData($scenario) {
        global $DB;
        $this->arguments = array();

        $sql = $this->construct($scenario);
        $values = $this->prepareArguments($sql, $this->arguments);

        $data = $DB->get_records_sql($sql, $values);
        $result['hasPrev'] = !empty($scenario['offset']);
        if (!empty($this->params['pagination_numbers'])) {
            $countSql = $this->count($sql);
            $result['count'] = $DB->count_records_sql($countSql, $values);
            $result['hasNext'] = !empty($scenario['offset']);
        } else {
            if (empty($scenario['limit']) || count($data) <= $scenario['limit']) {
                $result['hasNext'] = false;
            } else {
                $result['hasNext'] = true;
                array_pop($data);
            }
            $result['count'] = 0;
        }
        $result['data'] = $data;
        $this->requests[] = array('sql' => $sql, 'arguments' => $values);
        return $result;
    }

    public function construct($scenario) {

        $data = $this->findElements($scenario);
        $sql = $this->separator . 'SELECT '.  $data['columns'];

        if (!empty($scenario['tables'])) {
            $sql .= ' FROM ' . $data['tables'];
        }

        if (!empty($scenario['filters'])) {
            $sql .= ' WHERE ' . $data['filters'];
        }

        if (!empty($scenario['groups'])) {
            $sql .= ' GROUP BY ' . $data['groups'];
        }

        if (!empty($scenario['orders'])) {
            $sql .= ' ORDER BY ' . $data['orders'];
        }

        if (!empty($scenario['havings'])) {
            $sql .= ' HAVING ' . $data['havings'];
        }

        if (!empty($scenario['limit'])) {
            $sql .= ' LIMIT ' . (empty($this->params->pagination_numbers) && (empty($scenario['type']) || $scenario['type'] === 'table')?  $scenario['limit'] + 1 : $scenario['limit']);
        }

        if (!empty($scenario['offset'])) {
            $sql .= ' OFFSET ' . $scenario['offset'];
        }

        return $sql;
    }


    private function findElements($scenario) {

        $result = array();

        foreach ($scenario as $key => $value) {
            $classname = 'Containers' . '\\' . ucfirst($key) . 'Container';

            if (class_exists($classname)) {
                $result[$key] = $classname::get($value, $this);
                $result[$key] = $classname::construct($result[$key], $this);
            }

        }

        return $result;
    }

    public function count($sql) {
        $limitIndex = strripos($sql, 'limit');

        if ($limitIndex !== false) {
            $sql = substr($sql, 0, $limitIndex);
        }

        return 'SELECT COUNT(*) FROM (' . $sql . ') as result';
    }

    public function prepareArguments(&$sql, $arguments, $prefix = null) {

        $result = array();

        foreach($arguments as $key => $argument) {

            if ($argument === null && $this->mode === static::POSTGRES_MODE) {
                $sql = preg_replace('~\:' . $key .'\b~', 'NULL', $sql);
            } else {
                $result[$key] = $argument;
            }
        }

        return $result;

    }

    public function getMode() {
        return $this->mode;
    }

    public function setMode($mode) {
        if (in_array($mode, array(self::MYSQL_MODE, self::POSTGRES_MODE))) {
            $this->mode = $mode;
        }
    }

    public function getArguments($key = null) {

        if ($key) {
            $key = str_replace(':', '', $key);
            return $this->arguments[$key];
        }

        return $this->arguments;
    }

    public function getRawValue($key) {
        return $this->rawValues[$key];
    }

    public function setArguments($key, $value) {
        $key = str_replace(':', '', $key);
        $this->arguments[$key] = $value;
    }

    public function getSeparator() {
        return $this->separator;
    }

}
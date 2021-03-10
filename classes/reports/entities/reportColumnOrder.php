<?php

namespace local_intelliboard\reports\entities;

class reportColumnOrder
{
    const TYPE_COUNTRY = "country";
    const TYPE_NORMAL = "normal";

    private $orderColumn;
    private $orderDir;

    public function __construct($orderColumn, $orderDir)
    {
        if (is_array($orderColumn)) {
            $this->orderColumn = $orderColumn;
        } else {
            $this->orderColumn = [
                "sql_column" => $orderColumn,
                "type" => self::TYPE_NORMAL
            ];
        }

        $this->orderDir = $orderDir;
    }

    public function getOrderSQL()
    {
        global $CFG;

        if (empty($this->orderColumn["sql_column"])) {
            return '';
        }

        $order= '';
        if ($CFG->dbtype == 'pgsql') {
            if (strtolower($this->orderDir) == 'desc') {
                $order = 'NULLS LAST';
            } else {
                $order = 'NULLS FIRST';
            }
        }

        return "ORDER BY {$this->orderColumn["sql_column"]} {$this->orderDir} {$order}";
    }
}
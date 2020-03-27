<?php

namespace local_intelliboard\reports\entities;

use local_intelliboard\helpers\CountryHelper;
use local_intelliboard\helpers\DBHelper;

class reportColumnFilter
{
    const TYPE_COUNTRY = "country";
    const TYPE_NORMAL = "normal";

    private $column;
    private $filterVal;
    private $filterPrefix;

    public function __construct($column, $filterVal, $filterPrefix)
    {
        if (is_array($column)) {
            $this->column = $column;
        } else {
            $this->column = [
                "sql_column" => $column,
                "type" => self::TYPE_NORMAL
            ];
        }

        $this->filterVal = $filterVal;
        $this->filterPrefix = $filterPrefix;
    }

    public function getFilterValue()
    {
        switch ($this->column["type"]) {
            case self::TYPE_COUNTRY:
                $countryCode = CountryHelper::getCountryCodeByName($this->filterVal);

                if ($countryCode) {
                    return "%{$countryCode}%";
                }

                return "%{$this->filterVal}%";
            default:
                return "%{$this->filterVal}%";
        }
    }

    /**
     * @return string
     * @throws \coding_exception
     * @throws \Exception
     */
    public function getFilterSQL()
    {
        global $DB;

        $textTypeCast = DBHelper::get_typecast("text");
        $key = $this->getFilterKey();

        return $DB->sql_like($this->column["sql_column"] . $textTypeCast, ":{$key}", false, false);
    }

    /**
     * @return string
     * @throws \coding_exception
     */
    public function getFilterKey()
    {
        return strtolower(clean_param($this->column["sql_column"], PARAM_ALPHANUMEXT) . $this->filterPrefix);
    }
}
<?php

namespace Containers;

use Helpers\FunctionHelper;
use Helpers\ArrayHelper;
use DataExtractor;

class FiltersContainer extends BaseContainer {

    private static $conjunctions;
    private static $operators;
    private static $mode;
    private static $filtersCount = 0;

    public static function init($mode) {

        if (static::$operators && static::$conjunctions && static::$mode === $mode) {
            return;
        }

        static::$mode = $mode;

        $operators = array(
            1 => "=",
            2 => ">",
            3 => "<",
            4 => "<>",
            5 => function($param) {
                return "BETWEEN " . $param . "from AND " . $param . "to";
            },
            6 => "IS NOT",
            7 => function($param) {
                if (is_array($param)) {
                    return "IN(" . implode(',',$param) . ")";
                }

                return "IN(" . $param . ")";
            },
            8 => "NOT IN",
            9 => "IS",
            10 => "NOT",
            11 => "LIKE",
            12 => ">=",
            13 => "<=",
        );


        static::$conjunctions = array(
            1 => 'AND',
            2 => 'OR'
        );

        static::$operators = array_map(function($operator) use ($mode) {

            if (is_array($operator)) {
                $operator = $operator[$mode];
            }

            return $operator;

        }, $operators);

    }

    public static function get($request, DataExtractor $extractor, $params = array(), $isHaving = false) {

        static::init($extractor->getMode());

        $selected = ($flag = ArrayHelper::is_indexed_array($request))? $request : array($request);

        $result = array_map(function($filter) use ($extractor, &$filtersCount, $isHaving) {

            $conjunction = isset($filter['conjunction'])? static::$conjunctions[$filter['conjunction']] : static::$conjunctions[1];
            $filter = isset($filter['prop'])? $filter : array('prop' => $filter);

            if (ArrayHelper::is_indexed_array($filter['prop'])) {
                return array('prop' => static::get($filter['prop'], $extractor), 'conjunction' => $conjunction);
            }

            $prop = $isHaving && $extractor->getMode() === DataExtractor::MYSQL_MODE? ColumnsContainer::get($filter['prop'], $extractor)['name'] : ColumnsContainer::get($filter['prop'], $extractor)['sql'];
            $placeholder = false;

            if (isset($filter['value']['id'])) {
                $placeholder = ColumnsContainer::get($filter['value'], $extractor)['sql'];
            } else {
                $id = ':filter' . static::$filtersCount;
                static::$filtersCount++;

                if (!isset($filter['value'])) {
                    $value = $extractor->getRawValue($filter['argument']);
                } else {
                    $value = $filter['value'];
                }

                $extractor->setArguments($id, $value);
            }

            if (!isset($filter['operator'])) {
                $filter['operator'] = !empty($value) && ArrayHelper::is_indexed_array($value)? array(7) : array(1);
            }

            if (!$placeholder) {
                $checker = $extractor->getArguments($id);

                if (in_array(5, $filter['operator'])) {
                    $extractor->setArguments($id . 'from', $checker['from']);
                    $extractor->setArguments($id . 'to', $checker['to']);
                    $placeholder = $id;
                } elseif (in_array(7, $filter['operator'])) {

                    $value = array();
                    if (!is_array($checker)) {
                        $checker = array($checker);
                    }
                    foreach($checker as $key => $current) {
                        $deeper = $id . '_' . $key;
                        $extractor->setArguments($deeper, $current);
                        $value[] = $deeper;
                    }
                    $placeholder = $value;
                    static::ignoreCase($prop, $placeholder, $checker, $extractor);

                } else if(in_array(11, $filter['operator'])) {
                    $extractor->setArguments($id, "%$checker%");
                    $placeholder = $id;
                    static::ignoreCase($prop, $placeholder, $checker, $extractor);
                } else {
                    $placeholder = $id;

                }
            }

            $operator = array_map(function($operator) {
                return static::$operators[$operator];
            }, $filter['operator']);

            return compact('prop', 'operator', 'conjunction', 'placeholder');
        }, $selected);

        return $flag? $result : $result[0];
    }


    public static function construct($filters, DataExtractor $extractor, $params = array()) {

        $filters = ArrayHelper::is_indexed_array($filters)? $filters : array($filters);

        $processed = array_reduce($filters, function($carry, $filter) use ($extractor) {

            if (ArrayHelper::is_indexed_array($filter['prop'])) {
                return $carry . '(' . static::construct($filter['prop'], $extractor) . ')' . ' ' . $filter['conjunction'];
            }

            return $carry . ' ' . $filter['prop'] . ' ' . static::applyOperators($filter['operator'], $filter['placeholder']) . $extractor->getSeparator() .' ' . $filter['conjunction'];
        }, '');

        return trim($processed, 'ANDOR');

    }

    protected static function applyOperators($operators, $placeholder) {
        $buffer = '';
        foreach ($operators as $operator) {
            if (FunctionHelper::is_anonym_function($operator)) {
                $placeholder = $operator($placeholder);
            } else {
                $buffer .= $operator . ' ';
            }
        }
        return $buffer . $placeholder;
    }

    protected static function ignoreCase(&$prop, &$value, $checker, DataExtractor $extractor) {

        if (is_array($checker)) {
            $flag = false;
            foreach ($checker as $index => $item) {
                if (is_string($item) && !is_numeric($item)) {
                    $flag = true;
                    $value[$index] = ColumnsContainer::applyModifier(10, $value[$index], $extractor);
                }
            }

            if ($flag) {
                $prop = ColumnsContainer::applyModifier(10, $prop, $extractor);
            }

        } else {
            if (is_string($checker) && !is_numeric($checker)) {
                $value = ColumnsContainer::applyModifier(10, $value, $extractor);
                $prop = ColumnsContainer::applyModifier(10, $prop, $extractor);
            }
        }


    }

    public static function getOperator($id, DataExtractor $extractor) {
        static::init($extractor->getMode());
        return static::$operators[$id];
    }

}
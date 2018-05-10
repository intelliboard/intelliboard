<?php

namespace Containers;

use DataExtractor;

class OrdersContainer extends BaseContainer {

    public static function get($selected, DataExtractor $extractor, $params = array()) {
        $directions = array(
            1 => array(
                DataExtractor::MYSQL_MODE => 'ASC',
                DataExtractor::POSTGRES_MODE => 'ASC NULLS FIRST'
            ),
            2 => array(
                DataExtractor::MYSQL_MODE => 'DESC',
                DataExtractor::POSTGRES_MODE => 'DESC NULLS LAST'
            )
        );

        $mode = $extractor->getMode();

        $directions = array_map(function($direction) use ($mode) {
            if (is_array($direction)) {
                $direction = $direction[$mode];
            }
            return $direction;
        }, $directions);

        return array_map(function($order) use ($directions, $extractor) {

            if (empty($order['name'])) {
                $column = ColumnsContainer::get($order, $extractor)['sql'];
                $order['name'] = $column;
            }

            $order['direction'] = isset($order['direction'])? $directions[$order['direction']] : $directions[1];

            return $order;
        }, $selected);

    }

    public static function construct($orders, DataExtractor $extractor, $params = array()) {
        return implode(','  . $extractor->getSeparator() . ' '  . $extractor->getSeparator(), array_map(function($order) {
            return $order['name'] . ' ' . $order['direction'];
        }, $orders));
    }

}
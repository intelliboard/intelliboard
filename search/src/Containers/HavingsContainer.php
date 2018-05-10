<?php

namespace Containers;

use DataExtractor;

class HavingsContainer extends BaseContainer {

    static protected $conjunctions;
    static protected $mode = DataExtractor::MYSQL_MODE;

    public static function init($mode) {}

    public static function get($selected, DataExtractor $extractor, $params = array()) {
        static::init($extractor->getMode());

        return FiltersContainer::get($selected, $extractor, $params, true);
    }

    public static function construct($havings, DataExtractor $extractor, $params = array()) {
        return FiltersContainer::construct($havings, $extractor, $params);
    }

}
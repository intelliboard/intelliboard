<?php

namespace Containers;

use DataExtractor;

abstract class BaseContainer {

    public static abstract function get($entities, DataExtractor $extractor, $params = array());
    public static abstract function construct($entities, DataExtractor $extractor, $params = array());

    public static function release($entities, DataExtractor $extractor, $params = array())
    {
        $entities = static::get($entities, $extractor, $params);
        return static::construct($entities, $extractor, $params);
    }

}
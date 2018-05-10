<?php

namespace Containers;

use DataExtractor;

class GroupsContainer extends BaseContainer{

    public static function get($selected, DataExtractor $extractor, $params = array()) {
        $mode = $extractor->getMode();

        return array_map(function($group) use ($extractor, $mode) {

            if (empty($group['name'])) {
                $column = ColumnsContainer::get($group, $extractor)['sql'];
                $group['name'] = $column;
            } else {
                $group['name'] = is_numeric($group['name'])? ColumnsContainer::getById($group['name'], $extractor)['name'] : $group['name'];
            }

            return $group['name'];

        }, $selected);
    }

    public static function construct($groups, DataExtractor $extractor, $params = array()) {
        return implode(',' . $extractor->getSeparator() . ' ', $groups) . $extractor->getSeparator();
    }

}
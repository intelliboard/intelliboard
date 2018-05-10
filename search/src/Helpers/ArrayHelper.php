<?php

namespace Helpers;

class ArrayHelper {


    public static function is_indexed_array($array) {

            if (!is_array($array)) {
                return false;
            }

            foreach ($array as $key => $value) {
                if (!is_numeric($key)) return false;
            }
            return true;

    }
}
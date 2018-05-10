<?php

namespace Helpers;

class FunctionHelper {

    public static function is_anonym_function($f) {
        return is_object($f) && ($f instanceof \Closure);
    }

}
<?php

/**
 * Function for class auto loading
 *
 * @param string   $class Class name for required class
 * @return bool A status indicating that class has been loaded or not
 */

spl_autoload_register(function($class) {

    $baseDir = __DIR__;
    $path = $baseDir . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($path)) {
        require_once($path);
        return true;
    }

    return false;

});

function d($arg, $stop = true, $backtrace = false) {

    print "<pre>";
    print "Debug info:";
    var_dump($arg);
    print "</pre>";

    if ($backtrace) {
        debug_print_backtrace();
    }
    if ($stop) {
        die();
    }
}


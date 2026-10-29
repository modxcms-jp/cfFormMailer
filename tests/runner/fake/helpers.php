<?php
/**
 * MODX 本体のグローバル関数のうち、cfFormMailer が使うもの。
 *
 * evolution-jp の manager/includes/helpers.php から写した（commit 8c703cb38685710c35df228b61bad6101ea3ec1f）。
 * sessionv() のドット区切りや postv() の空配列の扱いを本物と同じにするため、中身は変えていない。
 */

function evo()
{
    global $modx;
    if (!$modx) {
        return null;
    }
    return $modx;
}

function db()
{
    return evo()->db;
}

function array_get($array, $key = null, $default = null, $validate = null)
{
    if ($key === null || trim($key) == '') {
        return $array;
    }

    if (isset($array[$key])) {
        if ($validate && is_callable($validate) && !$validate($array[$key])) {
            return $default;
        }
        return $array[$key];
    }
    $segments = explode('.', $key);
    foreach ($segments as $segment) {
        if (!is_array($array) || !isset($array[$segment])) {
            return $default;
        }
        $array = $array[$segment];
    }
    if ($validate && is_callable($validate) && !$validate($array)) {
        return $default;
    }
    return $array;
}

function array_set(&$array, $key, $value)
{
    if (is_null($key)) {
        return $array = $value;
    }

    $keys = explode('.', $key);
    foreach ($keys as $i => $key) {
        if (count($keys) === 1) {
            break;
        }
        unset($keys[$i]);
        if (!isset($array[$key]) || !is_array($array[$key])) {
            $array[$key] = [];
        }

        $array = &$array[$key];
    }

    $array[array_shift($keys)] = $value;

    return $array;
}

function getv($key = null, $default = null)
{
    $request = $_GET;
    if(isset($request[$key]) && $request[$key]==='') {
        unset($request[$key]);
    }
    return array_get($request, $key, $default);
}

function postv($key = null, $default = null)
{
    return array_get($_POST, $key, $default);
}

function serverv($key = null, $default = null)
{
    return array_get($_SERVER, strtoupper($key), $default);
}

function sessionv($key = null, $default = null)
{
    if (strpos($key, '*') === 0) {
        return array_set($_SESSION, ltrim($key, '*'), $default);
    }
    return array_get($_SESSION, $key, $default);
}

function request_time()
{
    static $request_time = null;

    if ($request_time) {
        return $request_time;
    }

    $request_time = serverv('request_time') ?: time();

    return $request_time;
}

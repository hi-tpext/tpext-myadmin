<?php

use tpext\common\ExtLoader;
use tpext\myadmin\common\UrlAuth;
use tpext\builder\inface\Auth;
use think\facade\Lang;

$classMap = [
    'tpext\\myadmin\\common\\Module',
];

ExtLoader::addClassMap($classMap);

if (!function_exists('checkUrl')) {
    function checkUrl($url)
    {
        if (interface_exists(Auth::class)) {
            return UrlAuth::checkUrl($url);
        }

        return true;
    }
}

if (!function_exists('__admin_lang')) {
    function __admin_lang($name = null, $vars = [], $range = '')
    {
        return Lang::get($name, $vars, $range);
    }
}

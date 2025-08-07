<?php

use tpext\common\ExtLoader;
use tpext\myadmin\common\UrlAuth;
use tpext\builder\inface\Auth;

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

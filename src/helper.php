<?php

use tpext\common\ExtLoader;

$classMap = [
    'tpext\\myadmin\\common\\Module',
];

ExtLoader::addClassMap($classMap);

if (!function_exists('checkUrl')) {
    function checkUrl($url)
    {
        return \tpext\myadmin\admin\model\AdminUser::checkUrl($url);
    }
}
<?php

use tpext\common\ExtLoader;
use tpext\myadmin\common\behavior\Auth;
use tpext\myadmin\common\behavior\Setup;

$classMap = [
    'tpext\\myadmin\\common\\Module',
];

ExtLoader::addClassMap($classMap);

ExtLoader::watch('module_init', Setup::class, true, '替换错误及跳转模板');
ExtLoader::watch('module_init', Auth::class, false, '权限验证');

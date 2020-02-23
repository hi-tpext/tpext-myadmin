<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
use tpext\common\ExtLoader;
use tpext\myadmin\common\hooks\Setup;

$classMap = [
    'tpext\\myadmin\\common\\Module',
    'tpext\\myadmin\\common\\Plugin',
];

ExtLoader::addClassMap($classMap);

ExtLoader::watch('module_init', Setup::class, '替换错误及跳转模板', false);
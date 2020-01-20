<?php

namespace tpext\lyatadmin\common;

use tpext\common\Module as baseModule;

class Module extends baseModule
{
    protected static $name = 'tpext.lyatadmin';

    protected static $modules = [
        'admin' => ['index'],
    ];

    public static function moduleInit()
    {
        static::$assets = realpath(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR . 'assets';

        parent::moduleInit();
    }
}

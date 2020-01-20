<?php

namespace tpext\lyatadmin\common;

use think\facade\View;
use tpext\common\Module as baseModule;

class Module extends baseModule
{
    protected static $name = 'tpext.lyatadmin';

    protected static $modules = [
        'admin' => ['index'],
    ];

    public static function moduleInit($info = [])
    {
        $rootPath = realpath(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR;

        static::$assets = $rootPath . 'assets';

        config('exception_tmpl', $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'error.html']));

        $config = config(static::getId());

        View::share('admin', $config);

        return parent::moduleInit($info);
    }
}

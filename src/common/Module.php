<?php

namespace tpext\lyatadmin\common;

use think\facade\View;
use tpext\common\Module as baseModule;

class Module extends baseModule
{
    protected $name = 'tpext.lyatadmin';

    protected $modules = [
        'admin' => ['index'],
    ];

    public function moduleInit($info = [])
    {
        $rootPath = realpath(dirname(dirname(__DIR__))) . DIRECTORY_SEPARATOR;

        $this->assets = $rootPath . 'assets';

        config('exception_tmpl', $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'error.html']));

        $config = config($this->getId());

        View::share('admin', $config);

        return parent::moduleInit($info);
    }
}

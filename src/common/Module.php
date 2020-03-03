<?php

namespace tpext\myadmin\common;

use tpext\common\Module as baseModule;

class Module extends baseModule
{
    protected $version = '1.0.1';

    protected $name = 'tpext.myadmin';

    protected $title = '后台框架';

    protected $description = '后台框架基础功能';

    protected $root = __DIR__ . '/../../';

    protected $modules = [
        'admin' => ['index', 'permission', 'role', 'admin', 'menu', 'operationlog'],
    ];
}

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
        'admin' => ['index', 'permission', 'role', 'admin', 'group', 'menu', 'operationlog'],
    ];

    public function install()
    {
        if (parent::install()) {
            session('admin_id', 1);

            return true;
        }

        return false;
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            session('admin_user', null);
            session('admin_id', null);
            cache('tpextmyadmin_installed', null);
            return true;
        }

        return false;
    }
}

<?php

namespace tpext\myadmin\common;

use tpext\common\Module as baseModule;
use tpext\myadmin\admin\model\AdminUser;

class Module extends baseModule
{
    protected $version = '1.0.1';

    protected $name = 'tpext.myadmin';

    protected $title = '后台框架';

    protected $description = '后台框架基础功能，建议优先安装，再装其他扩展';

    protected $root = __DIR__ . '/../../';

    protected $modules = [
        'admin' => ['index', 'permission', 'role', 'admin', 'group', 'menu', 'operationlog'],
    ];

    public function install()
    {
        if (parent::install()) {

            session('admin_id', 1);
            $user = AdminUser::get(1);

            if ($user) {
                unset($user['password'], $user['salt']);
                session('admin_user', $user);
            }

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

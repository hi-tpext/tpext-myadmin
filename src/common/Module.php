<?php

namespace tpext\myadmin\common;

use think\Db;
use tpext\common\ExtLoader;
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
            $user = AdminUser::get(1);
            if ($user) {
                session('admin_id', 1);
                unset($user['password'], $user['salt']);
                session('admin_user', $user);
                session('admin_last_time', $_SERVER['REQUEST_TIME']);

                return true;
            }
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

    public function isInstalled()
    {
        if (empty(config('database.database'))) {
            return false;
        }

        $tableName = config('database.prefix') . 'admin_user';

        $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

        if (empty($isTable)) {
            cache('tpextmyadmin_installed', 0);
            return false;
        }

        if (cache('tpextmyadmin_installed')) {
            return true;
        }

        $installed = ExtLoader::getInstalled();

        if (empty($installed)) {
            cache('tpextmyadmin_installed', 0);
            return false;
        }

        $is = false;
        foreach ($installed as $install) {
            if ($install['key'] == Module::class) {
                $is = true;
                break;
            }
        }

        cache('tpextmyadmin_installed', $is ? 1 : 0);

        return $is;
    }
}

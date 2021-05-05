<?php

namespace tpext\myadmin\common;

use think\facade\Db;
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

    protected static $tpextmyadminInstalled = false;

    protected $indexView = '';

    protected $loginViews = ['1' => '风格1', '2' => '风格2', '3' => '风格3', '4' => '风格4'];

    /**
     * Undocumented function
     *
     * @return boolean
     */
    public function install()
    {
        if (parent::install()) {
            $dataModel = new AdminUser;
            $user = $dataModel->where(['id' => 1])->find();

            if ($user && $dataModel->passValidate($user['password'], $user['salt'], 'tpextadmin')) {
                session('admin_id', 1);
                unset($user['password'], $user['salt']);
                session('admin_user', $user->toArray());
                session('admin_last_time', $_SERVER['REQUEST_TIME']);
            }

            return true;
        }

        return false;
    }

    /**
     * Undocumented function
     *
     * @param boolean $runSql
     * @return boolean
     */
    public function uninstall($runSql = true)
    {
        if (parent::uninstall($runSql)) {
            session('admin_user', null);
            session('admin_id', null);
            cache('tpextmyadmin_installed', null);
            return true;
        }

        return false;
    }

    /**
     * Undocumented function
     *
     * @return boolean
     */
    public static function isInstalled()
    {
        if (static::$tpextmyadminInstalled) {
            return true;
        }

        $type = Db::getConfig('default', 'mysql');

        $connections = Db::getConfig('connections');

        $config = $connections[$type] ?? [];

        if ($config['database'] == 'test' && $config['username'] == 'username' && $config['password'] == 'password') {
            return false;
        }

        $tableName = $config['prefix'] . 'admin_user';

        $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

        if (empty($isTable)) {
            cache('tpextmyadmin_installed', 0);
            return false;
        }

        if (cache('tpextmyadmin_installed')) {
            static::$tpextmyadminInstalled = true;
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

    /**
     * Undocumented function
     *
     * @param string $path
     * @return $this
     */
    public function setIndexView($path)
    {
        $this->indexView = $path;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return string
     */
    public function getIndexView()
    {
        return $this->indexView;
    }

    /**
     * Undocumented function
     *
     * @param string $path 模板路径
     * @param string $title 模板名称
     * @return $this
     */
    public function addLoginView($path, $title)
    {
        $this->loginViews[$path] = $title;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function getLoginViews()
    {
        return $this->loginViews;
    }
}

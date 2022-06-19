<?php

namespace tpext\myadmin\common;

use think\facade\Db;
use tpext\think\App;
use think\facade\Cache;
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

    protected $loginViews = ['1' => '风格1', '2' => '风格2', '3' => '风格3', '4' => '风格4'];
    protected $indexViews = ['1' => '默认lightYearAdmin'];

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
            Cache::set('tpextmyadmin_installed', null);
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

        if ($config['database'] == '' && $config['username'] == 'root' && $config['password'] == '') {
            return false;
        }

        if ($config['database'] == 'test' && $config['username'] == 'username' && $config['password'] == 'password') {
            return false;
        }

        $tableName = $config['prefix'] . 'admin_user';

        $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

        if (empty($isTable)) {
            Cache::set('tpextmyadmin_installed', 0);
            return false;
        }

        if (Cache::get('tpextmyadmin_installed')) {
            static::$tpextmyadminInstalled = true;
            return true;
        }

        $installed = ExtLoader::getInstalled();

        if (empty($installed)) {
            Cache::set('tpextmyadmin_installed', 0);
            return false;
        }

        $is = false;
        foreach ($installed as $install) {
            if ($install['key'] == Module::class) {
                $is = true;
                break;
            }
        }

        Cache::set('tpextmyadmin_installed', $is ? 1 : 0);

        return $is;
    }

    /**
     * 添加index框架模板到列表
     *
     * @param string $path 模板路径
     * @param string $title 模板名称
     * @return $this
     */
    public function addIndexView($path, $title)
    {
        $path = str_replace(App::getRootPath(), '__WWW__', $path);

        $this->indexViews[$path] = $title;
        return $this;
    }

    /**
     * 获取index框架模板到列表
     *
     * @return array
     */
    public function getIndexViews()
    {
        return $this->indexViews;
    }

    /**
     * 添加登录模板到列表
     *
     * @param string $path 模板路径
     * @param string $title 模板名称
     * @return $this
     */
    public function addLoginView($path, $title)
    {
        $path = str_replace(App::getRootPath(), '__WWW__', $path);

        $this->loginViews[$path] = $title;
        return $this;
    }

    /**
     * 获取登录框架模板到列表
     *
     * @return array
     */
    public function getLoginViews()
    {
        return $this->loginViews;
    }
}

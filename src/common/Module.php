<?php

namespace tpext\myadmin\common;

use think\facade\Cache;
use think\facade\Db;
use think\facade\Session;
use tpext\common\ExtLoader;
use tpext\common\Module as baseModule;
use tpext\myadmin\admin\model\AdminUser;
use tpext\think\App;

class Module extends baseModule
{
    protected $version = '1.2.0';

    protected $name = 'tpext.myadmin';

    protected $title = '后台框架';

    protected $description = '后台框架基础功能，建议优先安装，再装其他扩展';

    protected $root = __DIR__ . '/../../';

    protected $modules = [
        'admin' => ['index', 'permission', 'role', 'admin', 'group', 'menu', 'operationlog'],
    ];

    protected static $tpextmyadminInstalled = false;

    protected $loginViews = ['0' => 'v5默认'];
    protected $indexViews = ['1' => 'v5默认'];
    protected $assets = 'assets';

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

                Session::set('admin_id', 1);
                unset($user['password'], $user['salt']);
                Session::set('admin_user', $user->toArray());
                Session::set('admin_last_time', time());
            }

            self::$tpextmyadminInstalled = true;

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

            Session::delete('admin_user');
            Session::delete('admin_id');

            Cache::set('tpextmyadmin_installed', null);

            self::$tpextmyadminInstalled = false;

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

        $driver = Db::getConfig('default', 'mysql');

        $connections = Db::getConfig('connections');

        $config = $connections[$driver] ?? [];

        if (empty($config['database']) || empty($config['username']) || empty($config['password'])) {
            return false;
        }

        if ($config['database'] == 'test' && $config['username'] == 'root' && $config['password'] == '123456') {
            return false;
        }

        if ($config['database'] == 'database' && $config['username'] == 'username' && $config['password'] == 'password') {
            return false;
        }

        $prefix = $config['prefix'];

        $type = $config['type'];

        $tableName = $prefix . 'admin_user';

        $sql = "SHOW TABLES LIKE '{$tableName}'";

        if ($type == 'pgsql') {
            $sql = "SELECT tablename FROM pg_tables WHERE schemaname = 'public' AND tablename = '{$tableName}'";
        }

        $isTable = Db::query($sql);

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
        //可以监听此事件，调用addIndexView($path, $title)添加视图
        ExtLoader::trigger('tpext_admin_find_index_views');
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
        //可以监听此事件，调用addLoginView($path, $title)添加视图
        ExtLoader::trigger('tpext_admin_find_login_views');
        return $this->loginViews;
    }

    /**
     * 实例安装并启用，查找到之后调用
     *
     * @return $this
     */
    public function loaded()
    {
        //确保新版本样式正常
        if (!is_file(App::getPublicPath() . '/assets/tpextmyadmin/css/login.css')) {
            $this->copyAssets(true);
        }

        $this->loadLang('common');

        $this->loginViews = ['0' => __admin_lang('v5_default')];
        $this->indexViews = ['1' => __admin_lang('v5_default')];

        return $this;
    }
}

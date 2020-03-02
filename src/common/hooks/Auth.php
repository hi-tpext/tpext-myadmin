<?php
namespace tpext\myadmin\common\hooks;

use think\Db;
use think\facade\Request;
use tpext\common\ExtLoader;
use tpext\myadmin\common\Module;

class Auth
{
    public function isInstalled()
    {
        if (cache('tpextmyadmin_installed')) {
            return true;
        }

        $tableName = config('database.prefix') . 'admin_user';

        $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

        if (empty($isTable)) {
            return false;
        }

        $installed = ExtLoader::getInstalled();

        if (empty($installed)) {
            return false;
        }

        $is = false;
        foreach ($installed as $install) {
            if ($install['key'] == Module::class) {

                $is = true;
                cache('tpextmyadmin_installed', 1);
                break;
            }
        }

        return $is;
    }

    public function run($data = [])
    {
        $module = Request::module();

        if ($module == 'admin') { //admin模块

            $controller = strtolower(Request::controller());
            $action = strtolower(Request::action());

            $admin_id = session('admin_id');

            if (!$this->isInstalled()) {
                if ($controller != 'tpext' && $action != 'index') {
                    redirect('tpext/index')->send();
                    exit;
                }

                return;
            }

            $config = Module::getInstance()->getConfig();

            $login_timeout = $config['login_timeout'];
            

            $isLogin = $controller == 'index' && $action == 'login';

            $isAdmin = !empty($admin_id) && is_numeric($admin_id) && $admin_id > 0;

            if (!$isLogin && !$isAdmin) {
                if (Request::isAjax()) {
                    echo json_encode(['code' => 0, 'msg' => '登录超时，请重新登录！']);
                } else {
                    redirect('index/login')->send();
                }
                exit;
            } else if ($isLogin && $isAdmin) {
                redirect('index/index')->send();
                exit;
            }
        }
    }
}

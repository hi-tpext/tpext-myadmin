<?php
namespace tpext\myadmin\common\hooks;

use think\facade\Request;
use think\facade\View;
use tpext\myadmin\common\Module;

class Setup
{
    public function run($data = [])
    {
        $module = Request::module();

        if ($module == 'admin') { //admin模块， 替换错误和跳转模板

            $config = Module::getInstance()->loadConfig();

            View::share(['admin' => $config]);
        }
    }
}

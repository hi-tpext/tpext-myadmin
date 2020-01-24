<?php
namespace tpext\lyatadmin\common\hooks;

use think\facade\Request;
use tpext\lyatadmin\common\Module;

class Tpl
{
    public function run($data = [])
    {
        $module = Request::module();

        if ($module == 'admin') { //admin模块， 替换错误和跳转模板

            $instance = Module::getInstance();

            $rootPath = $instance->getRoot();

            $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', '']);

            config('exception_tmpl', $tplPath . 'exception_tmpl.tpl');
            config('dispatch_success_tmpl', $tplPath . 'dispatch_jump.tpl');
            config('dispatch_error_tmpl', $tplPath . 'dispatch_jump.tpl');
        }
    }
}

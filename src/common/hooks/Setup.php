<?php
namespace tpext\myadmin\common\hooks;

use think\facade\Request;
use think\facade\View;
use tpext\myadmin\common\MinifyTool;
use tpext\myadmin\common\Module;

class Setup
{
    public function run($data = [])
    {
        $module = Request::module();

        if ($module == 'admin') { //admin模块， 替换错误和跳转模板

            $instance = Module::getInstance();

            $rootPath = $instance->getRoot();

            $instance->copyAssets();

            $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', '']);

            //config('exception_tmpl', $tplPath . 'exception_tmpl.tpl');
            config('dispatch_success_tmpl', $tplPath . 'dispatch_jump.tpl');
            config('dispatch_error_tmpl', $tplPath . 'dispatch_jump.tpl');

            $config = $instance->getConfig();
            $admin_layout = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'layout.html']);

            if ($config['minify']) {
                $tool = new MinifyTool;
                $tool->minify();
            }

            View::share([
                'admin_page_position' => '',
                'admin_page_title' => isset($config['name']) ? $config['name'] : '',
                'admin_page_description' => isset($config['description']) ? $config['description'] : '',
                'admin_logo' => isset($config['logo']) ? $config['logo'] : '',
                'admin_favicon' => isset($config['favicon']) ? $config['favicon'] : '',
                'admin_copyright' => isset($config['copyright']) ? $config['copyright'] : '',
                'admin_login_logo' => isset($config['login_logo']) ? $config['login_logo'] : '',
                'admin_login_background_img' => isset($config['login_background_img']) ? $config['login_background_img'] : '',
                'admin_js' => MinifyTool::getJs(),
                'admin_css' => MinifyTool::getCss(),
                'admin_layout' => $admin_layout,
                'admin_assets_ver' => $config['assets_ver'],
            ]);
        }
    }
}

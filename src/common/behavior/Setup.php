<?php

namespace tpext\myadmin\common\behavior;

use tpext\builder\common\Builder;
use tpext\common\ExtLoader;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\common\MinifyTool;
use tpext\myadmin\common\Module;

class Setup
{
    public function run($data = [])
    {
        $module = request()->module();

        if (strtolower($module) == 'admin') { //admin模块， 替换错误和跳转模板 ,其他事件监听

            if (Module::isInstalled()) {
                ExtLoader::watch('tpext_menus', Menu::class, false, '接收菜单创建/删除事件');
                ExtLoader::watch('tpext_copy_assets', Assets::class, false, '监视资源刷新，修改版本号');
                ExtLoader::watch('app_end', Log::class, false, '记录日志');
            }

            $instance = Module::getInstance();

            $rootPath = $instance->getRoot();

            $instance->copyAssets();

            $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', '']);

            config('dispatch_success_tmpl', $tplPath . 'dispatch_jump.tpl');
            config('dispatch_error_tmpl', $tplPath . 'dispatch_jump.tpl');

            $config = [];

            if (Module::isInstalled()) {
                $config = $instance->getConfig();
            } else {
                $config = $instance->defaultConfig();
            }
            
            $admin_layout = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'layout.html']);

            if ($config['minify']) {
                $tool = new MinifyTool;
                $tool->minify();
            }

            $css = MinifyTool::getCss();
            $js = MinifyTool::getJs();

            foreach ($css as &$c) {
                if (strpos($c, '?') == false && strpos($c, 'http') == false) {
                    $c .= '?aver=' . $config['assets_ver'];
                }
            }

            unset($c);

            foreach ($js as &$j) {
                if (strpos($j, '?') == false && strpos($j, 'http') == false) {
                    $j .= '?aver=' . $config['assets_ver'];
                }
            }

            unset($j);

            Builder::aver($config['assets_ver']);
            Builder::auth(AdminUser::class);
            app('view')->share([
                'admin_page_position' => '',
                'admin_page_title' => isset($config['name']) ? $config['name'] : '',
                'admin_page_description' => isset($config['description']) ? $config['description'] : '',
                'admin_logo' => isset($config['logo']) ? $config['logo'] : '',
                'admin_favicon' => isset($config['favicon']) ? $config['favicon'] : '',
                'admin_copyright' => isset($config['copyright']) ? $config['copyright'] : '',
                'admin_login_logo' => isset($config['login_logo']) ? $config['login_logo'] : '',
                'admin_login_background_img' => isset($config['login_background_img']) ? $config['login_background_img'] : '',
                'admin_js' => $js,
                'admin_css' => $css,
                'admin_layout' => $admin_layout,
                'admin_assets_ver' => $config['assets_ver'],
            ]);
        }
    }
}

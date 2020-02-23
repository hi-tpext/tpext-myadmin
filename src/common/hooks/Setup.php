<?php
namespace tpext\myadmin\common\hooks;

use think\facade\Request;
use think\facade\View;
use tpext\myadmin\common\Module;

class Setup
{
    protected static $js = [
        '/assets/lightyearadmin/js/jquery.min.js',
        '/assets/lightyearadmin/js/bootstrap.min.js',
        '/assets/lightyearadmin/js/jquery.lyear.loading.js',
        '/assets/lightyearadmin/js/bootstrap-notify.min.js',
        '/assets/lightyearadmin/js/lightyear.js',
        '/assets/lightyearadmin/js/main.min.js',
        '/assets/lightyearadmin/js/jconfirm/jquery-confirm.min.js',
    ];

    protected static $css = [
        '/assets/lightyearadmin/css/bootstrap.min.css',
        '/assets/lightyearadmin/css/materialdesignicons.min.css',
        '/assets/lightyearadmin/css/animate.css',
        '/assets/lightyearadmin/css/style.min.css',
        '/assets/lightyearadmin/js/jconfirm/jquery-confirm.min.css',
    ];

    /**
     * Undocumented function
     *
     * @param array $val
     */
    public static function addJs($val)
    {
        $this->js = array_merge($this->js, $val);
    }

    /**
     * Undocumented function
     *
     * @param array $val
     * @return $this
     */
    public static function addCss($val)
    {
        $this->css = array_merge($this->css, $val);
    }

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

            View::share([
                'admin_page_position' => '',
                'admin_page_title' => $config['name'],
                'admin_page_description' => $config['description'],
                'admin_logo' => $config['logo'],
                'admin_favicon' => $config['favicon'],
                'admin_copyright' => $config['copyright'],
                'admin_js' => static::$js,
                'admin_css' => static::$css,
                'admin_layout' => $admin_layout,
            ]);
        }
    }
}

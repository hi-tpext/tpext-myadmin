<?php

namespace tpext\myadmin\common\middleware;

use Closure;
use think\App;
use think\event\HttpEnd;
use think\helper\Str;
use think\Request;
use think\Response;
use tpext\builder\common\Builder;
use tpext\common\ExtLoader;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\common\event\Assets;
use tpext\myadmin\common\event\Log;
use tpext\myadmin\common\event\Menu;
use tpext\myadmin\common\Module;
use tpext\myadmin\common\UrlAuth;
use tpext\think\View;

/**
 * for tp6
 */

class Auth
{
    /** @var App */
    protected $app;

    protected $js = [
        '/assets/lightyearadmin/js/jquery.min.js',
        '/assets/lightyearadmin/js/bootstrap.min.js',
        '/assets/lightyearadmin/js/jquery.lyear.loading.js',
        '/assets/lightyearadmin/js/bootstrap-notify.min.js',
        '/assets/lightyearadmin/js/jconfirm/jquery-confirm.min.js',
        '/assets/lightyearadmin/js/lightyear.js',
        '/assets/lightyearadmin/js/main.min.js',
        '/assets/tpextmyadmin/js/tpextbuilder.js',
        '/assets/tpextmyadmin/js/layer/layer.js',
    ];

    protected $css = [
        '/assets/lightyearadmin/css/bootstrap.min.css',
        '/assets/lightyearadmin/css/materialdesignicons.min.css',
        '/assets/lightyearadmin/css/animate.css',
        '/assets/lightyearadmin/css/style.min.css',
        '/assets/lightyearadmin/js/jconfirm/jquery-confirm.min.css',
        '/assets/tpextmyadmin/css/tpextbuilder.css',
    ];

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * 多模块解析
     * @access public
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle($request, Closure $next)
    {
        $response = $this->check();

        if ($response) {
            return $response;
        }

        if (Module::isInstalled()) {
            ExtLoader::watch('tpext_menus', Menu::class, false, '接收菜单创建/删除事件');
            ExtLoader::watch('tpext_copy_assets', Assets::class, false, '监视资源刷新，修改版本号');
            ExtLoader::watch(HttpEnd::class, Log::class, false, '记录日志');
        }

        return $next($request);
    }

    protected function getLoginTimeout()
    {
        $config = Module::getInstance()->getConfig();

        $login_timeout = 10;

        if (isset($config['login_timeout']) && is_numeric($config['login_timeout'])) {
            $login_timeout = $config['login_timeout'];
        }

        return $login_timeout;
    }

    protected function setup()
    {
        $instance = Module::getInstance();

        $rootPath = $instance->getRoot();

        $instance->copyAssets();

        $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', '']);

        $config = [];

        if (Module::isInstalled()) {
            config('dispatch_success_tmpl', $tplPath . 'dispatch_jump.tpl');
            config('dispatch_error_tmpl', $tplPath . 'dispatch_jump.tpl');

            $config = $instance->getConfig();
        } else {
            $config = $instance->defaultConfig();
        }

        $admin_layout = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'layout.html']);

        foreach ($this->css as &$c) {
            if (strpos($c, '?') == false && strpos($c, 'http') == false) {
                $c .= '?aver=' . $config['assets_ver'];
            }
        }

        unset($c);

        foreach ($this->js as &$j) {
            if (strpos($j, '?') == false && strpos($j, 'http') == false) {
                $j .= '?aver=' . $config['assets_ver'];
            }
        }

        unset($j);

        if (class_exists(Builder::class)) {
            Builder::aver($config['assets_ver']);
            Builder::auth(UrlAuth::class);
        }
        View::share(
            [
                'admin_page_position' => '',
                'admin_page_title' => isset($config['name']) ? $config['name'] : '',
                'admin_page_description' => isset($config['description']) ? $config['description'] : '',
                'admin_logo' => isset($config['logo']) ? $config['logo'] : '',
                'admin_favicon' => isset($config['favicon']) ? $config['favicon'] : '',
                'admin_copyright' => isset($config['copyright']) ? $config['copyright'] : '',
                'admin_login_logo' => isset($config['login_logo']) ? $config['login_logo'] : '',
                'admin_login_background_img' => isset($config['login_background_img']) ? $config['login_background_img'] : '',
                'admin_js' => $this->js,
                'admin_css' => $this->css,
                'admin_layout' => $admin_layout,
                'admin_assets_ver' => $config['assets_ver'],
            ]
        );
    }

    protected function isInstalled()
    {
        return Module::isInstalled();
    }

    /**
     * Undocumented function
     *
     * @return boolean|Response
     */
    public function check()
    {
        $module = strtolower($this->app->http->getName());

        if ($module == 'admin') { //admin模块

            $this->setup();

            $controller = strtolower(Str::snake($this->app->request->controller()));
            $action = strtolower($this->app->request->action());

            if (!$this->isInstalled()) {
                if ($controller != 'extension') {
                    return $this->error('请安装扩展！', url('/admin/extension/prepare'));
                } else {
                    return false;
                }
            }

            $admin_id = session('admin_id');

            $isLogin = $controller == 'index' && ($action == 'login' || $action == 'captcha');
            $isAdmin = !empty($admin_id) && is_numeric($admin_id) && $admin_id > 0;

            if ($isAdmin) {
                $login_timeout = $this->getLoginTimeout();
                $now = $_SERVER['REQUEST_TIME'];

                if (!session('?admin_last_time') || $now - session('admin_last_time') > $login_timeout * 60) {
                    $isAdmin = 0;
                    session('admin_user', null);
                    session('admin_id', null);
                } else {
                    if ($now - session('admin_last_time') > 60) {

                        session('admin_last_time', $now);
                    }

                    $userModel = new AdminUser;

                    $res = $userModel->checkPermission($admin_id, $controller, $action);

                    if (!$res) {
                        return $this->error('无权限访问！', url('/admin/index/denied'), 1);
                    }
                }
            }

            if (!$isLogin && !$isAdmin && $this->isInstalled()) {
                $config = Module::getInstance()->getConfig();

                cookie('after_login_url', $this->app->request->url(), ['expire' => 0, 'httponly' => true]);

                if (isset($config['login_session_key']) && $config['login_session_key'] == '1') {
                    if (!session('?login_session_key')) {
                        if (cookie('tpext_myadmin_entry')) {
                            $tpext_myadmin_entry = cookie('tpext_myadmin_entry');
                            return $this->error('登录超时，即将自动跳转缓存的后台入口（请保存入口地址：' . request()->domain() . url($tpext_myadmin_entry, [], false) . '，更换浏览器、清除浏览器缓存、更换电脑后需要重新手动输入）...', $tpext_myadmin_entry, 20);
                        }
                        header("HTTP/1.1 403 Forbidden");
                        echo '<div style="text-align:center"><h1>验证未通过</h1><hr>请从后台前置入口进入登录页面</div>';
                        exit;
                    }
                }

                return $this->error('登录超时，请重新登录！', url('/admin/index/login'));
            } else if ($isLogin && $isAdmin) {
                return $this->success('您已经登录！', url('/admin/index/index'));
            }
        }
    }

    protected function success($msg = '', $url = '', $wait = 2)
    {
        $url = (string) $url;
        if ($this->app->request->isAjax()) {
            return json([
                'code' => 1,
                'msg' => $msg,
                'url' => $url,
            ]);
        }

        $rootPath = Module::getInstance()->getRoot();

        $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', 'dispatch_jump']) . '.tpl';

        return view($tplPath, ['msg' => $msg, 'url' => $url, 'code' => 1, 'wait' => $wait]);
    }

    protected function error($msg = '', $url = '', $wait = 2)
    {
        $url = (string) $url;
        if ($this->app->request->isAjax()) {
            return json([
                'code' => 0,
                'msg' => $msg,
                'url' => $url,
            ]);
        }

        $rootPath = Module::getInstance()->getRoot();

        $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', 'dispatch_jump']) . '.tpl';

        return view($tplPath, ['msg' => $msg, 'url' => $url, 'code' => 0, 'wait' => $wait]);
    }
}

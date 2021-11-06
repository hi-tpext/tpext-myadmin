<?php

namespace tpext\myadmin\common\middleware;

use Closure;
use think\App;
use think\event\HttpEnd;
use think\Request;
use think\Response;
use tpext\builder\common\Builder;
use tpext\common\ExtLoader;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\common\event\Log;
use tpext\myadmin\common\event\Menu;
use tpext\myadmin\common\event\Assets;
use tpext\myadmin\common\MinifyTool;
use tpext\myadmin\common\Module;

/**
 * for tp6
 */

class Auth
{
    /** @var App */
    protected $app;

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

        if (isset($config['login_timeout'])) {
            $login_timeout = $config['login_timeout'];
        } else {
            $login_timeout = 10;
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
        $this->app->view->assign(
            [
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

            $controller = strtolower($this->app->request->controller());
            $action = strtolower($this->app->request->action());

            if (!$this->isInstalled()) {
                if ($controller != 'extension') {
                    return $this->error('请安装扩展！', url('/admin/extension/prepare')->__toString());
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
                        return $this->error('无权限访问！', url('/admin/index/denied')->__toString(), '', 1);
                    }
                }
            }

            if (!$isLogin && !$isAdmin && $this->isInstalled()) {
                $config = Module::getInstance()->getConfig();

                if (isset($config['login_session_key']) && $config['login_session_key'] == '1') {
                    if (!session('?login_session_key')) {
                        header("HTTP/1.1 404 Not Found");
                        exit;
                    }
                }

                cookie('after_login_url', $this->app->request->url(), ['expire' => 0, 'httponly' => true]);

                return $this->error('登录超时，请重新登录！', url('/admin/index/login')->__toString());
            } else if ($isLogin && $isAdmin) {
                return $this->success('您已经登录！', url('/admin/index/index')->__toString());
            }
        }
    }

    protected function success($msg = '', $url)
    {
        if ($this->app->request->isAjax()) {
            return json([
                'code' => 1,
                'msg' => $msg,
                'url' => $url,
            ]);
        }

        $rootPath = Module::getInstance()->getRoot();

        $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', 'dispatch_jump']) . '.tpl';

        return view($tplPath, ['msg' => $msg, 'url' => $url, 'code' => 1, 'wait' => 3]);
    }

    protected function error($msg = '', $url)
    {
        if ($this->app->request->isAjax()) {
            return json([
                'code' => 0,
                'msg' => $msg,
                'url' => $url,
            ]);
        }

        $rootPath = Module::getInstance()->getRoot();

        $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', 'dispatch_jump']) . '.tpl';

        return view($tplPath, ['msg' => $msg, 'url' => $url, 'code' => 0, 'wait' => 3]);
    }
}

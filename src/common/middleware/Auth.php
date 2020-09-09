<?php

namespace tpext\myadmin\common\middleware;

use Closure;
use think\App;
use think\Request;
use think\Response;
use tpext\myadmin\admin\model\AdminUser;
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

        return $this->app->middleware->pipeline('tpext')
            ->send($request)
            ->then(function ($request) use ($next) {
                return $next($request);
            });
    }

    private function getLoginTimeout()
    {
        $login_timeout = cache('admin_login_timeout');
        if ($login_timeout) {
            return $login_timeout;
        }

        $config = Module::getInstance()->getConfig();

        if (isset($config['login_timeout'])) {
            $login_timeout = $config['login_timeout'];
        } else {
            $login_timeout = 10;
        }

        cache('admin_login_timeout', $login_timeout);

        return $login_timeout;
    }

    private function isInstalled()
    {
        return Module::isInstalled();
    }

    /**
     * Undocumented function
     *
     * @return boolean
     */
    public function check()
    {
        $module = $this->app->http->getName();
        if ($module == 'admin') { //admin模块

            $controller = strtolower($this->app->request->controller());
            $action = strtolower($this->app->request->action());

            if (!$this->isInstalled()) {
                if ($controller != 'extension') {
                    return redirect(url('/admin/extension/index'))->with(['msg' => '登录超时，请重新登录！']);
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
                        return redirect(url('/admin/index/denied'))->with(['msg' => '无权限访问！']);
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

                cookie('after_login_url', request()->url(), ['expire' => 0, 'httponly' => true]);

                return redirect(url('/admin/index/login'))->with(['msg' => '登录超时，请重新登录！']);
            } else if ($isLogin && $isAdmin) {
                return redirect(url('/admin/index/index'))->with(['msg' => '您已经登录！']);
            }
        }
    }
}

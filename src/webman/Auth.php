<?php

namespace tpext\myadmin\webman;

use tpext\think\View;
use Webman\Http\Request;
use think\facade\Session;
use Webman\Http\Response;
use tpext\common\ExtLoader;
use Webman\MiddlewareInterface;
use tpext\myadmin\common\Module;
use tpext\builder\common\Builder;
use tpext\builder\common\Module as BuilderModule;
use tpext\myadmin\common\MinifyTool;
use tpext\myadmin\admin\model\AdminUser;
use think\Controller;

/**
 * for webman
 */

class Auth implements MiddlewareInterface
{
    protected $module = '';
    protected $controller = '';
    protected $action = '';

    public function process(Request $request, callable $next): Response
    {
        if ($request->route) {
            $path = strtolower($request->route->getPath());
            $explode = explode('/', ltrim($path, '/'));
            $this->module = !empty($explode[0]) ? $explode[0] : 'index';
            $this->controller  = !empty($explode[1]) ? $explode[1] : 'index';
            $this->action  = !empty($explode[2]) ? $explode[2] : 'index';
        } else {
            $path = strtolower($request->path());
            $explode = explode('/', ltrim($path, '/'));
            $this->module = !empty($explode[0]) ? $explode[0] : 'index';
            $this->controller  = !empty($explode[1]) ? $explode[1] : 'index';
            $this->action  = !empty($explode[2]) ? $explode[2] : 'index';
        }

        $this->setup();

        if (strtolower($this->module) !== 'admin') {
            $response = $next($request);
            Builder::destroyInstance();
            return $response;
        }
        Builder::auth(AdminUser::class);
        $response = $this->check();

        if ($response) {
            return $response;
        }

        $response = $next($request);

        if (Module::isInstalled()) {
            ExtLoader::trigger('admin_log');
        }

        $this->resetBuilder();

        return $response;
    }

    protected function resetBuilder()
    {
        BuilderModule::getInstance()->setUploadUrl('');
        BuilderModule::getInstance()->setImportUrl('');
        BuilderModule::getInstance()->setChooseUrl('');
        BuilderModule::getInstance()->setViewsPath('');
        Builder::destroyInstance();
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

            Controller::setDispatchJumpTemplate($tplPath . 'dispatch_jump.tpl');

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
        $controller = strtolower($this->controller);
        $action = strtolower($this->action);

        if (!$this->isInstalled()) {
            if ($controller != 'extension') {
                return $this->error('请安装扩展！', url('/admin/extension/prepare')->__toString());
            } else {
                return false;
            }
        }

        $admin_id = Session::get('admin_id');

        $isLogin = $controller == 'index' && ($action == 'login' || $action == 'captcha');
        $isAdmin = !empty($admin_id) && is_numeric($admin_id) && $admin_id > 0;

        if ($isAdmin) {
            $login_timeout = $this->getLoginTimeout();
            $now = time();

            if (!Session::has('admin_last_time') || $now - Session::get('admin_last_time') > $login_timeout * 60) {
                $isAdmin = 0;
                Session::delete('admin_user');
                Session::delete('admin_id');
            } else {
                if ($now - Session::get('admin_last_time') > 60) {

                    Session::set('admin_last_time', $now);
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
                if (!Session::has('login_session_key')) {
                    return new Response(404, [], '404 Not Found');
                }
            }

            Session::set('after_login_url', request()->fullUrl());

            return $this->error('登录超时，请重新登录！', url('/admin/index/login')->__toString());
        } else if ($isLogin && $isAdmin) {
            return $this->success('您已经登录！', url('/admin/index/index')->__toString());
        }
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function success($msg = '', $url = null, $data = '', $wait = 3, $header = array())
    {
        if (is_null($url) && $referer = request()->header('HTTP_REFERER')) {
            $url = $referer;
        } elseif ('' !== $url) {
            $url = (string) $url;
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : url($url)->__toString();
        }

        $result = [
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        if ($this->getResponseType() == 'json') {
            return json($result);
        } else {
            $rootPath = Module::getInstance()->getRoot();
            $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', 'dispatch_jump']) . '.tpl';
            $view = new View($tplPath, $result);
            return new Response(200, $header, $view->getContent());
        }
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     * @param  mixed     $msg 提示信息
     * @param  string    $url 跳转的URL地址
     * @param  mixed     $data 返回的数据
     * @param  integer   $wait 跳转等待时间
     * @param  array     $header 发送的Header信息
     * @return void
     */
    protected function error($msg = '', $url = null, $data = '', $wait = 3, $header = array())
    {
        if (is_null($url) && $referer = request()->header('HTTP_REFERER')) {
            $url = $referer;
        } elseif ('' !== $url) {
            $url = (string) $url;
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : url($url)->__toString();
        }

        $result = [
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        if ($this->getResponseType() == 'json') {
            return json($result);
        } else {
            $rootPath = Module::getInstance()->getRoot();
            $tplPath = $rootPath . implode(DIRECTORY_SEPARATOR, ['src', 'admin', 'view', 'tpl', 'dispatch_jump']) . '.tpl';
            $view = new View($tplPath, $result);
            return new Response(200, $header, $view->getContent());
        }
    }

    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     */
    protected function getResponseType()
    {
        $isAjax = request()->isAjax();

        return $isAjax ? 'json' : 'html';
    }
}

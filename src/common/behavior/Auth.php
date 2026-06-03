<?php

namespace tpext\myadmin\common\behavior;

use think\Response;
use think\Container;
use think\Loader;
use tpext\myadmin\common\Module;
use tpext\myadmin\admin\model\AdminUser;

class Auth
{
    protected $app;

    private function getLoginTimeout()
    {
        $config = Module::getInstance()->getConfig();

        $login_timeout = 10;

        if (isset($config['login_timeout']) && is_numeric($config['login_timeout'])) {
            $login_timeout = $config['login_timeout'];
        }

        return $login_timeout;
    }

    private function isInstalled()
    {
        return Module::isInstalled();
    }

    public function run($data = [])
    {
        $module = request()->module();

        if (strtolower($module) == 'admin') { //admin模块

            $controller = strtolower(Loader::parseName(request()->controller()));
            $action = strtolower(request()->action());

            if (!$this->isInstalled()) {
                if ($controller != 'extension') {
                    $this->error('请安装扩展！', url('/admin/extension/prepare'));
                } else {
                    return;
                }
            }

            $admin_id = session('admin_id');

            $isLogin = $controller == 'index' && ($action == 'login' || $action == 'captcha');
            $isAdmin = !empty($admin_id) && is_numeric($admin_id) && $admin_id > 0;

            if ($isAdmin) {
                $login_timeout = $this->getLoginTimeout();
                $now = time();

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
                        $this->error('无权限访问！', url('/admin/index/denied'), '', 1);
                    }
                }
            }

            if (!$isLogin && !$isAdmin && $this->isInstalled()) {
                $config = Module::getInstance()->getConfig();

                cookie('after_login_url', request()->url(), ['expire' => 0, 'httponly' => true]);

                if (isset($config['login_session_key']) && $config['login_session_key'] == '1') {
                    if (!session('?login_session_key')) {
                        if (cookie('tpext_myadmin_entry')) {
                            $tpext_myadmin_entry = cookie('tpext_myadmin_entry');
                            return $this->error('登录超时，即将自动跳转缓存的后台入口（请保存入口地址：' . request()->domain() . url($tpext_myadmin_entry, [], false) . '，更换浏览器、清除浏览器缓存、更换电脑后需要重新手动输入）...', $tpext_myadmin_entry, '', 20);
                        }
                        header("HTTP/1.1 403 Forbidden");
                        echo '<div style="text-align:center"><h1>验证未通过</h1><hr>请从后台前置入口进入登录页面</div>';
                        exit;
                    }
                }

                $this->error('登录超时，请重新登录！', url('/admin/index/login'));
            } else if ($isLogin && $isAdmin) {
                $this->success('您已经登录！', url('/admin/index/index'));
            }
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
    protected function error($msg = '', $url = null, $data = '', $wait = 2, array $header = [])
    {
        $type = $this->getResponseType();
        if (is_null($url)) {
            $url = $this->app['request']->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : $this->app['url']->build($url);
        }

        $result = [
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        if ('html' == strtolower($type)) {
            $type = 'jump';
        }

        $response = Response::create($result, $type)->header($header)->options(['jump_template' => $this->app['config']->get('dispatch_error_tmpl')]);

        $response->send();
        exit;
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
    protected function success($msg = '', $url = null, $data = '', $wait = 2, array $header = [])
    {
        if (is_null($url) && request()->server('HTTP_REFERER')) {
            $url = request()->server('HTTP_REFERER');
        } elseif ('' !== $url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : Container::get('url')->build($url);
        }

        $result = [
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        $type = $this->getResponseType();
        // 把跳转模板的渲染下沉，这样在 response_send 行为里通过getData()获得的数据是一致性的格式
        if ('html' == strtolower($type)) {
            $type = 'jump';
        }

        $response = Response::create($result, $type)->header($header)->options(['jump_template' => $this->app['config']->get('dispatch_success_tmpl')]);

        $response->send();
        exit;
    }

    /**
     * 获取当前的response 输出类型
     * @access protected
     * @return string
     */
    protected function getResponseType()
    {
        if (!$this->app) {
            $this->app = Container::get('app');
        }

        $isAjax = $this->app['request']->isAjax();
        $config = $this->app['config'];

        return $isAjax
            ? $config->get('default_ajax_return')
            : $config->get('default_return_type');
    }
}

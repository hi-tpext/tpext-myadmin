<?php
namespace tpext\myadmin\common\hooks;

use think\Container;
use think\Db;
use think\facade\Request;
use think\Response;
use tpext\common\ExtLoader;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\common\Module;

class Auth
{
    protected $app;

    public function isInstalled()
    {
        if (empty(config('database.database'))) {
            return false;
        }

        $tableName = config('database.prefix') . 'admin_user';

        $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

        if (empty($isTable)) {
            return false;
        }

        if (cache('tpextmyadmin_installed')) {
            return true;
        }

        $installed = ExtLoader::getInstalled();

        if (empty($installed)) {
            return false;
        }

        $is = false;
        foreach ($installed as $install) {
            if ($install['key'] == Module::class) {

                $is = true;
                cache('tpextmyadmin_installed', 1);
                break;
            }
        }

        return $is;
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

    public function run($data = [])
    {
        $module = Request::module();

        if ($module == 'admin') { //admin模块

            $controller = strtolower(Request::controller());
            $action = strtolower(Request::action());

            if (!$this->isInstalled()) {
                if ($controller != 'extension') {
                    $this->error('请安装扩展！', url('extension/index'));
                } else {
                    return;
                }
            }

            $admin_id = session('admin_id');

            $isLogin = $controller == 'index' && $action == 'login';
            $isAdmin = !empty($admin_id) && is_numeric($admin_id) && $admin_id > 0;

            if ($isAdmin) {
                $now = time();
                $login_timeout = $this->getLoginTimeout();
                $admin_last_time = session('admin_last_time');

                if ($admin_last_time && $now - $admin_last_time > $login_timeout * 60) {
                    $isAdmin = 0;
                    session('admin_user', null);
                    session('admin_id', null);
                } else {
                    session('admin_last_time', $now);

                    $userModel = new AdminUser;

                    $res = $userModel->checkPermission($admin_id, $controller, $action);

                    if (!$res) {
                        $this->error('无权限访问！', url('index/denied'));
                    }
                }
            }

            if (!$isLogin && !$isAdmin && $this->isInstalled()) {
                $this->error('登录超时，请重新登录！', url('index/login'));
            } else if ($isLogin && $isAdmin) {
                $this->success('您已经登录！', url('index/index'));
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
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
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

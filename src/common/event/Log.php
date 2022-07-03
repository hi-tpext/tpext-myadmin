<?php

namespace tpext\myadmin\common\event;

use think\App;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\common\Module;

class Log
{
    protected $module = '';
    protected $controller = '';
    protected $action = '';

    public function handle($data)
    {
        $request = request();

        $controller = '';
        $action = '';

        if ($request->route) {
            $path = strtolower($request->route->getPath());
            $explode = explode('/', ltrim($path, '/'));
            $controller  = !empty($explode[1]) ? $explode[1] : 'index';
            $action  = !empty($explode[2]) ? $explode[2] : 'index';
        } else {
            $path = strtolower($request->path());
            $explode = explode('/', ltrim($path, '/'));
            $controller  = !empty($explode[1]) ? $explode[1] : 'index';
            $action  = !empty($explode[2]) ? $explode[2] : 'index';
        }

        $admin_id = session('admin_id');

        if ($controller == 'index' && (in_array($action, ['login', 'welcome', 'index', 'denied']))) {

            return true;
        }

        if (empty($admin_id)) {

            return true;
        }

        $config = Module::getInstance()->getConfig();

        $method = request()->method();

        $types = $config['operation_log_catch'];

        if (!in_array($method, $types)) {
            return;
        }

        $param = $request->param();

        if ($controller == 'admin' && in_array($action, ['add', 'edit'])) {
            $param = [];
        } else if ($controller == 'index' && in_array($action, ['changepwd', 'profile'])) {
            $param = [];
        } else if ($controller == 'config' && in_array($action, ['extconfig', 'index'])) {
            $param = [];
        }

        unset($param['password'], $param['__table__'], $param['__search__'], $param['__token__']);

        if (in_array($method, ['PATCH', 'DELETE'])) {
            unset($param[strtolower($method)]);
        }

        AdminOperationLog::create([
            'user_id' => $admin_id,
            'path' => implode('/', ['admin', $controller, $action]),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'data' => json_encode($param, JSON_UNESCAPED_UNICODE),
        ]);

        return true;
    }
}

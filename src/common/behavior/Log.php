<?php
namespace tpext\myadmin\common\behavior;

use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\common\Module;

class Log
{
    public function run($data = [])
    {
        if (!Module::isInstalled()) {
            return;
        }

        $module = request()->module();

        $controller = strtolower(request()->controller());
        $action = strtolower(request()->action());

        $admin_id = session('admin_id');

        if ($controller == 'index' && $action == 'login') {

            return;
        }

        if (empty($admin_id)) {

            return;
        }

        $config = Module::getInstance()->getConfig();

        $method = request()->method();

        $types = $config['operation_log_catch'];

        if (!in_array($method, $types)) {
            return;
        }

        $param = request()->param();

        if ($controller == 'admin' && in_array($action, ['add', 'edit'])) {
            $param = [];
        } else if ($controller == 'index' && in_array($action, ['changepwd', 'profile'])) {
            $param = [];
        } else if ($controller == 'config' && in_array($action, ['extconfig', 'index'])) {
            $param = [];
        }

        unset($param['password'], $param['__table__'], $param['__search__'], $param['__token__']);

        if (!in_array($method, ['GET', 'POST'])) {
            unset($param[strtolower($method)]);
        }

        AdminOperationLog::create([
            'user_id' => $admin_id,
            'path' => implode('/', [$module, $controller, $action]),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'data' => json_encode($param, JSON_UNESCAPED_UNICODE),
        ]);
    }
}

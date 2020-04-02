<?php
namespace tpext\myadmin\common\hooks;

use think\Db;
use think\facade\Request;
use tpext\myadmin\admin\model\AdminOperationLog;

class Log
{
    public function run($data = [])
    {
        $module = Request::module();

        if ($module == 'admin') { //admin模块

            $controller = strtolower(Request::controller());
            $action = strtolower(Request::action());

            $admin_id = session('admin_id');

            if ($controller == 'index' && $action == 'login') {

                return;
            }

            if (empty($admin_id)) {

                return;
            }

            $tableName = config('database.prefix') . 'admin_operation_log';

            $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

            if (empty($isTable)) {
                return;
            }
            $param = Request::param();

            if ($controller == 'admin' && in_array($action, ['add', 'edit'])) {
                $param = [];
            } else if ($controller == 'index' && in_array($action, ['changepwd', 'profile'])) {
                $param = [];
            } else if ($controller == 'config' && in_array($action, ['extconfig', 'index'])) {
                $param = [];
            }

            unset($param['password'], $param['__table__'], $param['__search__']);

            AdminOperationLog::create([
                'user_id' => $admin_id,
                'path' => implode('/', [$module, $controller, $action]),
                'method' => Request::method(),
                'ip' => Request::ip(),
                'data' => json_encode($param),
            ]);

        }
    }
}

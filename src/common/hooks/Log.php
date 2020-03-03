<?php
namespace tpext\myadmin\common\hooks;

use think\Db;
use think\facade\Request;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\common\Module;

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

            AdminOperationLog::create([
                'user_id' => $admin_id,
                'path' => implode('/', [$module, $controller, $action]),
                'method' => Request::method(),
                'ip' => Request::ip(),
                'data' => json_encode(Request::param()),
            ]);

        }
    }
}

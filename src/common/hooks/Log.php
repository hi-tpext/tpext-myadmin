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
            $controller = Request::controller();
            $action = Request::action();

            $tableName = config('database.prefix') . 'admin_operation_log';

            $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

            if (empty($isTable)) {
                return;
            }

            AdminOperationLog::create([
                'user_id' => 1,
                'path' => implode('/', [$module, $controller, $action]),
                'method' => Request::method(),
                'ip' => Request::ip(),
                'data' => json_encode(Request::param()),
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s'),
            ]);

        }
    }
}

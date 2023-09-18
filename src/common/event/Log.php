<?php
namespace tpext\myadmin\common\event;

use think\App;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\common\Module;

class Log
{
    /** @var App */
    protected $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function handle($data)
    {
        $controller = strtolower($this->app->request->controller());
        $action = strtolower($this->app->request->action());

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

        $param = $this->app->request->param();

        if ($controller == 'admin' && in_array($action, ['add', 'edit'])) {
            $param = [];
        } else if ($controller == 'index' && in_array($action, ['changepwd', 'profile'])) {
            $param = [];
        } else if ($controller == 'config' && in_array($action, ['extconfig', 'index'])) {
            $param = [];
        }

        unset($param['password'], $param['validate'], $param['token'], $param['secret'], $param['__table__'], $param['__search__'], $param['__token__']);

        $path = implode('/', ['admin', $controller, $action]);

        $operation_log_fields_except = $config['operation_log_fields_except'] ?? '';

        if ($operation_log_fields_except) {
            $rules = explode("\n", $operation_log_fields_except);
            foreach ($rules as $rule) {
                if (empty($rule)) {
                    continue;
                }
                $ruleArr = explode(':', trim($rule));
                if (count($ruleArr) != 2) {
                    continue;
                }
                $rulePaths = explode(',', trim($ruleArr[0]));
                if ($rulePaths[0] == '*' || in_array($path, $rulePaths)) {
                    $fields = explode(',', $ruleArr[1]);
                    foreach ($fields as $f) {
                        unset($param[trim($f)]);
                    }
                }
            }
        }

        if (in_array($method, ['PATCH', 'DELETE'])) {
            unset($param[strtolower($method)]);
        }

        AdminOperationLog::create([
            'user_id' => $admin_id,
            'path' => '/' . $path,
            'method' => request()->method(),
            'ip' => request()->ip(),
            'data' => json_encode($param, JSON_UNESCAPED_UNICODE),
        ]);

        return true;
    }
}

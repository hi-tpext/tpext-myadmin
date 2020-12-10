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
        if (!Module::isInstalled()) {
            return true;
        }

        $controller = strtolower($this->app->request->controller());
        $action = strtolower($this->app->request->action());

        $admin_id = session('admin_id');

        if ($controller == 'index' && (in_array($action, ['login', 'welcome', 'index', 'denied']))) {

            return true;
        }

        if (empty($admin_id)) {

            return true;
        }

        $param = $this->app->request->param();

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
            'path' => implode('/', ['admin', $controller, $action]),
            'method' => request()->method(),
            'ip' => request()->ip(),
            'data' => json_encode($param),
        ]);

        return true;
    }
}
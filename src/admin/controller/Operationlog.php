<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\traits\actions;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\common\Module;

/**
 * Undocumented class
 * @title 操作日志
 */
class Operationlog extends Controller
{
    use actions\HasBase;
    use actions\HasIndex;
    use actions\HasView;
    use actions\HasDelete;

    /**
     * Undocumented variable
     *
     * @var AdminOperationLog
     */
    protected $dataModel;

    /**
     * Undocumented variable
     *
     * @var AdminUser
     */
    protected $userModel;

    protected function initialize()
    {
        Module::getInstance()->loadLang('operationlog');

        $this->dataModel = new AdminOperationLog;
        $this->userModel = new AdminUser;
        $this->pageTitle = __admin_lang('operation_log');

        $this->indexWith = ['admin', 'action']; //列表页关联模型

        $this->indexFieldsOnly = 'id,user_id,path,method,ip,create_time,LEFT(data,256) as data';
    }

    protected function filterWhere()
    {
        $searchData = request()->get();

        $where = [];
        if (!empty($searchData['user_id'])) {
            $where[] = ['user_id', '=', $searchData['user_id']];
        }

        if (!empty($searchData['path'])) {
            $where[] = ['path', 'like', '%' . $searchData['path'] . '%'];
        }

        if (!empty($searchData['ip'])) {
            $where[] = ['ip', 'like', '%' . $searchData['ip'] . '%'];
        }

        if (!empty($searchData['method'])) {
            $where[] = ['method', '=', $searchData['method']];
        }

        if (!empty($searchData['start'])) {
            $where[] = ['create_time', '>=', $searchData['start']];
        }

        if (!empty($searchData['end'])) {
            $where[] = ['create_time', '<=', $searchData['end']];
        }

        return $where;
    }

    /**
     * 构建搜索
     *
     * @return void
     */
    protected function buildSearch()
    {
        $search = $this->search;

        $search->select('user_id', __admin_lang('admin_user'), 3)->optionsData($this->userModel->select(), 'username');
        $search->text('path', '', 3);
        $search->text('ip', '', 3);
        $search->select('method', '', 3)->options(['GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE']);
        $search->datetime('start ', __admin_lang('operation_time'), 3)->placeholder(__admin_lang('start_time'));
        $search->datetime('end ', '~', 3)->placeholder(__admin_lang('end_time'));
    }

    /**
     * 构建表单
     *
     * @param boolean $isEdit
     * @param array $data
     */
    protected function buildForm($isEdit, &$data = [])
    {
        $form = $this->form;
        $form->show('id');
        $form->show('user_id', __admin_lang('admin_id'));
        $form->show('admin.username', __admin_lang('username'));
        $form->show('admin.name', __admin_lang('name'));
        $form->show('path');
        $form->show('method');
        $form->show('ip');
        $form->show('create_time', __admin_lang('time'));
        $form->html('data')->display(
            '<pre style="white-space:pre-wrap;word-break:break-all;">{$data}</pre>',
            ['data' => json_encode(json_decode($data['data']), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)]
        )->size(2, 10);
    }

    /**
     * 构建表格
     *
     * @return void
     */
    protected function buildTable(&$data = [])
    {
        $table = $this->table;

        $table->show('id');
        $table->show('admin.username', __admin_lang('username'));
        $table->show('admin.name', __admin_lang('name'));
        $table->show('path');
        $table->show('permission', __admin_lang('action'))->to('{controller_name}-{action.action_name}');
        $table->show('method');
        $table->show('ip');
        $table->show('data')->cut(100)->getWrapper()->style('max-width:40%;');
        $table->show('create_time', __admin_lang('time'))->getWrapper()->addStyle('width:160px');

        $table->getToolbar()
            ->btnDelete()
            ->btnRefresh();

        $table->getActionbar()
            ->btnView()
            ->btnDelete();

        $constrollers = [];
        foreach ($data as $d) {
            if ($d['action'] && $d['action']['controller']) {
                $constrollers[$d['action']['controller']] = $d['action']['controller'];
            }
        }

        $constrollers = AdminPermission::where('controller', 'in', $constrollers)->where('action', '#')->select();

        foreach ($data as $d) {
            foreach ($constrollers as $c) {
                if ($d['action'] && $d['action']['controller'] && $d['action']['controller'] == $c['controller']) {
                    $d['controller_name'] = $c['action_name'];
                }
            }
        }
    }
}

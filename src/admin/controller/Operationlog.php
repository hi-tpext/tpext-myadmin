<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\traits\actions;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\admin\model\AdminPermission;

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
        $this->dataModel = new AdminOperationLog;
        $this->userModel = new AdminUser;
        $this->pageTitle = '操作记录';

        $this->indexWith = ['admin', 'action']; //列表页关联模型

        $this->indexFieldsOnly = 'id,user_id,path,method,ip,create_time,LEFT(data,256) as data';
    }

    protected function filterWhere()
    {
        $searchData = request()->get();

        $where = [];
        if (!empty($searchData['user_id'])) {
            $where[] = ['user_id', 'eq', $searchData['user_id']];
        }

        if (!empty($searchData['path'])) {
            $where[] = ['path', 'like', '%' . $searchData['path'] . '%'];
        }

        if (!empty($searchData['ip'])) {
            $where[] = ['ip', 'like', '%' . $searchData['ip'] . '%'];
        }

        if (!empty($searchData['method'])) {
            $where[] = ['method', 'eq', $searchData['method']];
        }

        if (!empty($searchData['start'])) {
            $where[] = ['create_time', 'egt', $searchData['start']];
        }

        if (!empty($searchData['end'])) {
            $where[] = ['create_time', 'elt', $searchData['end']];
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

        $search->select('user_id', '管理员', 3)->optionsData($this->userModel->all(), 'username');
        $search->text('path', '路径', 3);
        $search->text('ip', 'IP', 3);
        $search->select('method', '提交方式', 3)->options(['GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE']);
        $search->datetime('start ', '操作时间', 3)->placeholder('起始');
        $search->datetime('end ', '~', 3)->placeholder('截止');
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
        $form->show('id', 'ID');
        $form->show('user_id', '管理员id');
        $form->show('admin.username', '登录帐号');
        $form->show('admin.name', '姓名');
        $form->show('path', '路径');
        $form->show('permission', '操作')->to('{controller.controller_name}-{action.action_name}');
        $form->show('method', '提交方式');
        $form->show('ip', 'IP');
        $form->show('create_time', '时间');
        $form->html('data', '数据')->display(
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

        $table->show('id', 'ID');
        $table->show('admin.username', '登录帐号');
        $table->show('admin.name', '姓名');
        $table->show('path', '路径');
        $table->show('permission', '操作')->to('{controller_name}-{action.action_name}');
        $table->show('method', '提交方式');
        $table->show('ip', 'IP');
        $table->show('data', '数据')->cut(100)->getWrapper()->style('max-width:40%;');
        $table->show('create_time', '时间')->getWrapper()->addStyle('width:160px');

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

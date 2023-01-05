<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\traits\actions;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminUser;

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

        $this->indexWith = ['admin']; //列表页关联模型

        $this->indexFieldsExcept = 'data';//排除某字段
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

        $search->select('user_id', '管理员', 3)->optionsData($this->userModel->select(), 'username');
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
        $table->show('method', '提交方式');
        $table->show('ip', 'IP');
        $table->show('create_time', '时间')->getWrapper()->addStyle('width:160px');

        $table->getToolbar()
            ->btnDelete()
            ->btnRefresh();

        $table->getActionbar()
            ->btnView()
            ->btnDelete();
    }
}

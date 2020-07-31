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
    }

    protected function filterWhere()
    {
        $searchData = request()->post();

        $where = [];
        if (!empty($searchData['user_id'])) {
            $where[] = ['user_id', 'eq', $searchData['user_id']];
        }

        if (!empty($searchData['path'])) {
            $where[] = ['path', 'like', '%' . $searchData['path'] . '%'];
        }

        if (!empty($searchData['method'])) {
            $where[] = ['method', 'eq', $searchData['method']];
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

        $search->select('user_id', '管理员')->optionsData($this->userModel->all(), 'username');
        $search->text('path', '路径');
        $search->radio('method', '提交方式', 3)->options(['' => '全部', 'GET' => 'GET', 'POST' => 'POST']);
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
        $form->show('username', '登录帐号');
        $form->show('name', '姓名');
        $form->show('path', '路径');
        $form->show('method', '提交方式');
        $form->show('ip', 'IP');
        $form->show('data', '数据');
        $form->show('create_time', '时间');
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
        $table->show('username', '登录帐号');
        $table->show('name', '姓名');
        $table->show('path', '路径');
        $table->show('method', '提交方式');
        $table->show('ip', 'IP');
        $table->show('data', '数据')->getWrapper()->style('width:25%;');
        $table->show('create_time', '时间')->getWrapper()->addStyle('width:160px');

        foreach ($data as &$d) {
            if ($d['method'] == 'POST' && mb_strlen($d['data']) > 50) {

                $d['data'] = mb_substr($d['data'], 0, 50) . '...}';
            }
        }

        $table->getToolbar()
            ->btnDelete()
            ->btnRefresh();

        $table->getActionbar()
            ->btnView()
            ->btnDelete();
    }
}

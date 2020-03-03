<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminUser;

class Operationlog extends Controller
{
    protected $dataModel;
    protected $roleModel;

    protected function initialize()
    {
        $this->dataModel = new AdminOperationLog;
        $this->userModel = new AdminUser;
    }

    public function index()
    {
        $builder = Builder::getInstance('用户管理', '列表');

        $form = $builder->form();

        $form->select('user_id', '管理员', 3)->options($this->getUesrList());
        $form->radio('method', '提交方式', 3)->options(['' => '全部', 'GET' => 'get', 'POST' => 'post']);

        $table = $builder->table();

        $table->searchForm($form);

        $table->show('id', 'ID');
        $table->show('username', '登录帐号');
        $table->show('name', '姓名');
        $table->show('method', '提交方式');
        $table->show('data', '数据')->getWapper()->style('width:40%;');
        $table->show('create_time', '时间')->getWapper()->addStyle('width:180px');

        $table->getToolbar()
            ->btnDelete()
            ->btnRefresh();

        $table->getActionbar()
            ->btnDelete();

        $pagezise = 10;

        $page = input('__page__/d', 1);

        $page = $page < 1 ? 1 : $page;

        $searchData = request()->only([
            'user_id',
            'method',
        ], 'post');

        $where = [];

        if (!empty($searchData['user_id'])) {
            $where[] = ['user_id', 'eq', $searchData['user_id']];
        }

        if (!empty($searchData['method'])) {
            $where[] = ['method', 'eq', $searchData['method']];
        }

        $data = $this->dataModel->where($where)->order('id desc')->limit(($page - 1) * $pagezise, $pagezise)->select();

        $table->data($data);
        $table->paginator($this->dataModel->where($where)->count(), $pagezise);

        if (request()->isAjax()) {
            return $table->partial()->render(false);
        }

        return $builder->render();
    }

    private function getUesrList()
    {
        $list = $this->userModel->all();
        $users = [
            '' => '请选择',
        ];

        foreach ($list as $row) {
            $users[$row['id']] = $row['name'];
        }

        return $users;
    }

    public function delete()
    {
        $ids = input('ids');

        $ids = array_filter(explode(',', $ids), 'strlen');

        if (empty($ids)) {
            $this->error('参数有误');
        }

        $res = 0;

        foreach ($ids as $id) {
            if ($this->dataModel->destroy($id)) {
                $res += 1;
            }
        }

        if ($res) {
            $this->success('成功删除' . $res . '条数据');
        } else {
            $this->error('删除失败');
        }
    }
}

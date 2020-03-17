<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;
use tpext\builder\traits\HasBuilder;
use tpext\myadmin\admin\model\AdminGroup;
use tpext\myadmin\admin\model\AdminRole;
use tpext\myadmin\admin\model\AdminUser;

class Admin extends Controller
{
    use HasBuilder;

    protected $dataModel;
    protected $roleModel;
    protected $groupModel;

    protected function initialize()
    {
        $this->dataModel = new AdminUser;
        $this->roleModel = new AdminRole;
        $this->groupModel = new AdminGroup;

        $this->pageTitle = '用户管理';
        $this->postAllowFields = ['phone', 'name', 'email'];
        $this->delNotAllowed = [1];
    }

    protected function filterWhere()
    {
        $searchData = request()->post();

        $where = [];
        if (!empty($searchData['username'])) {
            $where[] = ['username', 'like', '%' . $searchData['username'] . '%'];
        }

        if (!empty($searchData['name'])) {
            $where[] = ['name', 'like', '%' . $searchData['name'] . '%'];
        }

        if (!empty($searchData['phone'])) {
            $where[] = ['phone', 'like', '%' . $searchData['phone'] . '%'];
        }

        if (!empty($searchData['email'])) {
            $where[] = ['email', 'like', '%' . $searchData['email'] . '%'];
        }

        if (!empty($searchData['role_id'])) {
            $where[] = ['role_id', 'eq', $searchData['role_id']];
        }

        return $where;
    }

    /**
     * 构建搜索
     *
     * @return void
     */
    protected function builSearch()
    {
        $search = $this->search;

        $search->text('username', '账号', 3)->maxlength(20);
        $search->text('name', '姓名', 3)->maxlength(20);
        $search->text('phone', '手机号', 3)->maxlength(20);
        $search->text('email', '邮箱', 3)->maxlength(20);
        $search->select('role_id', '角色组', 3)->options($this->roleModel->all(), 'name');
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
        $table->text('name', '姓名')->autoPost()->getWapper()->addStyle('max-width:80px');
        $table->show('role_name', '角色');
        $table->show('group_name', '分组');
        $table->show('email', '电子邮箱')->default('无');
        $table->show('phone', '手机号')->default('无');
        $table->show('errors', '登录失败');
        $table->show('login_time', '登录时间')->getWapper()->addStyle('width:180px');
        $table->show('create_time', '添加时间')->getWapper()->addStyle('width:180px');
    }

    /**
     * 构建表单
     *
     * @param boolean $isEdit
     * @param array $data
     */
    protected function builForm($isEdit, &$data = [])
    {
        $form = $this->form;

        $form->text('username', '登录帐号')->required()->beforSymbol('<i class="mdi mdi-account-key"></i>');
        $form->select('role_id', '角色')->required()->optionsData($this->roleModel->all(), 'name')->disabled($isEdit && $data['id'] == 1);
        $form->password('password', '密码')->required(!$isEdit)->beforSymbol('<i class="mdi mdi-lock"></i>')->help($isEdit ? '不修改则留空（6～20位）' : '添加用户，密码必填（6～20位）');
        $form->text('name', '姓名')->required()->beforSymbol('<i class="mdi mdi-rename-box"></i>');
        $form->select('group_id', '用户组')->options([0 => '未分组'] + $this->groupModel->buildTree());
        $form->image('avatar', '头像')->default('/assets/lightyearadmin/images/no-avatar.jpg');
        $form->text('email', '电子邮箱')->beforSymbol('<i class="mdi mdi-email-variant"></i>');
        $form->text('phone', '手机号')->beforSymbol('<i class="mdi mdi-cellphone-iphone"></i>');
        $form->tags('tags', '标签');

        if ($isEdit) {

            $data['password'] = '';

            $form->show('create_time', '添加时间');
            $form->show('update_time', '修改时间');
        }
    }

    /**
     * 保存数据
     *
     * @param integer $id
     * @return void
     */
    private function save($id = 0)
    {
        if ($id == 1 && session('admin_id') != 1) {
            $this->error('超级管理员[id为1]，其他人不允许修改');
        }

        $data = request()->only([
            'name',
            'role_id',
            'group_id',
            'avatar',
            'username',
            'password',
            'email',
            'phone',
            'tags',
        ], 'post');

        if ($id == 1) {
            $data['role_id'] = 1;
        }

        if (!$id && $this->dataModel->where(['username' => $data['username']])->find()) {
            $this->error('账号已存在');
        }

        $result = $this->validate($data, [
            'role_id|角色组' => 'require',
            'username|登录帐号' => 'require',
            'name|姓名' => 'require',
            'email|电子邮箱' => 'email',
            'phone|手机号' => 'mobile',
            'errors|失败次数' => 'number',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        if (!empty($data['password'])) {
            $len = mb_strlen($data['password']);

            if ($len < 6 || $len > 20) {
                $this->error('密码长度6～20');
            }

            $password = $this->dataModel->passCrypt($data['password']);

            $data['password'] = $password[0];
            $data['salt'] = $password[1];
        } else {
            unset($data['password']);
        }

        if (!empty($data['phone']) && !preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
            $this->error('手机号码格式错误');
        }

        if ($id) {
            $data['update_time'] = date('Y-m-d H:i:s');
            $res = $this->dataModel->where(['id' => $id])->update($data);
        } else {
            if (!isset($data['password']) || empty($data['password'])) {
                $this->error('请输入密码');
            }
            $res = $this->dataModel->create($data);
        }

        if (!$res) {
            $this->error('保存失败');
        }

        return Builder::getInstance()->layer()->closeRefresh(1, '保存成功');
    }

    public function clearErrors()
    {
        $ids = input('ids', '');

        $ids = array_filter(explode(',', $ids), 'strlen');

        if (empty($ids)) {
            $this->error('参数有误');
        }

        $res = 0;

        foreach ($ids as $id) {
            if ($this->dataModel->where(['id' => $id])->update(['errors' => 0])) {
                $res += 1;
            }
        }

        if ($res) {
            $this->success('成功重置' . $res . '个账号的登录失败次数');
        } else {
            $this->error('重置失败');
        }
    }
}

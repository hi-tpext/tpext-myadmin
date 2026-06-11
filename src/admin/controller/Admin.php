<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\facade\Session;
use tpext\builder\traits\HasBuilder;
use tpext\myadmin\common\Module;
use tpext\myadmin\admin\model\AdminRole;
use tpext\myadmin\admin\model\AdminUser;

/**
 * Undocumented class
 * @title 管理员管理
 */
class Admin extends Controller
{
    use HasBuilder;

    /**
     * Undocumented variable
     *
     * @var AdminUser
     */
    protected $dataModel;

    /**
     * Undocumented variable
     *
     * @var AdminRole
     */
    protected $roleModel;

    /**
     * Undocumented variable
     *
     * @var \think\Model
     */
    protected $groupModel;

    protected function initialize()
    {
        Module::getInstance()->loadLang('admin');

        $this->dataModel = new AdminUser;
        $this->roleModel = new AdminRole;
        $this->groupModel = $this->dataModel->getAdminGroupModel();

        $this->pageTitle = __admin_lang('user_management');
        $this->postAllowFields = ['phone', 'name', 'email'];
        $this->delNotAllowed = [1, Session::get('admin_id')];

        $this->selectTextField = '{id}#{name}({username})';
        $this->selectFields = 'id,name,username';
        $this->selectSearch = 'username|name|phone';

        $this->indexWith = ['group', 'role']; //列表页关联模型
    }

    protected function filterWhere()
    {
        $searchData = request()->get();

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
            $where[] = ['role_id', '=', $searchData['role_id']];
        }

        if (!empty($searchData['group_id'])) {
            $where[] = ['group_id', '=', $searchData['group_id']];
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

        $search->text('username')->maxlength(20);
        $search->text('name')->maxlength(20);
        $search->text('phone')->maxlength(20);
        $search->text('email')->maxlength(20);
        $search->select('role_id')->optionsData($this->roleModel->select(), 'name');
        if (method_exists($this->groupModel, 'buildTree')) {
            $search->select('group_id', $this->dataModel->getAdminGroupTitle())->options([0 => __admin_lang('please_select')] + $this->groupModel->buildTree());
        } else {
            $search->select('group_id', $this->dataModel->getAdminGroupTitle())->optionsData($this->groupModel->select(), 'name');
        }
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
        $table->show('username');
        $table->text('name')->autoPost()->getWrapper()->addStyle('max-width:80px');
        $table->show('role.name', __admin_lang('role_id'));
        $table->show('group.name', $this->dataModel->getAdminGroupTitle());
        $table->match('enable')->options([0 => '<label class="label label-danger">' . __admin_lang('disable') . '</label>', 1 => '<label class="label label-success">' . __admin_lang('normal') . '</label>']);
        $table->show('email')->default(__admin_lang('none'));
        $table->show('phone')->default(__admin_lang('none'));
        $table->show('errors');
        $table->show('login_time')->getWrapper()->addStyle('width:180px');
        $table->show('create_time')->getWrapper()->addStyle('width:180px');

        foreach ($data as &$d) {
            $d['__h_del__'] = $d['id'] == 1;
            $d['__h_en__'] = $d['enable'] == 1;
            $d['__h_dis__'] = $d['enable'] != 1 || $d['id'] == 1;
            $d['__h_clr__'] = $d['errors'] < 1;
        }
        unset($d);

        $table->getToolbar()
            ->btnAdd()
            ->btnEnable()
            ->btnDisable()
            ->btnRefresh();

        $table->getActionbar()
            ->btnEdit()
            ->btnEnableAndDisable()
            ->btnView()
            ->btnDelete()
            ->btnPostRowid('clear_errors', url('clearErrors'), '', 'btn-info', 'mdi-backup-restore', 'title="' . __admin_lang('clear_login_errors') . '"')
            ->mapClass([
                'delete' => ['hidden' => '__h_del__'],
                'enable' => ['hidden' => '__h_en__'],
                'disable' => ['hidden' => '__h_dis__'],
                'clear_errors' => ['hidden' => '__h_clr__'],
            ]);
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

        $admin = AdminUser::current();

        $form->tab(__admin_lang('basic_info'));

        $form->text('username')->required()->beforSymbol('<i class="mdi mdi-account-key"></i>');
        $form->text('name')->required()->beforSymbol('<i class="mdi mdi-rename-box"></i>');
        $form->password('password')->required(!$isEdit)->beforSymbol('<i class="mdi mdi-lock"></i>')->help($isEdit ? __admin_lang('pwd_leave_blank') : __admin_lang('pwd_required_add'));
        $form->select('role_id')->required()->optionsData($this->roleModel->select(), 'name')->disabled($isEdit && $data['id'] == 1);

        if (method_exists($this->groupModel, 'asTreeList')) {
            $form->select('group_id', $this->dataModel->getAdminGroupTitle())->options([0 => __admin_lang('please_select')] + $this->groupModel->getOptionsData());
        } else {
            $form->select('group_id', $this->dataModel->getAdminGroupTitle())->optionsData($this->groupModel->select(), 'name');
        }
        $form->radio('enable')->options([0 => __admin_lang('disable'), 1 => __admin_lang('enable')])->disabled($isEdit && $admin['id'] == $data['id'])->default(1)->help(__admin_lang('disabled_no_login'));

        $form->tab(__admin_lang('other_info'));
        $form->image('avatar')->default('/assets/lightyearadmin/images/no-avatar.jpg')->imageResize(200, 200);
        $form->text('email')->beforSymbol('<i class="mdi mdi-email-variant"></i>');
        $form->text('phone')->beforSymbol('<i class="mdi mdi-cellphone-iphone"></i>');
        $form->tags('tags');

        if ($isEdit) {
            $data['password'] = '';
            $form->show('create_time');
            $form->show('update_time');
        }
    }

    /**
     * 保存数据
     *
     * @param integer $id
     * @return void
     */
    protected function save($id = 0)
    {
        if ($id == 1 && Session::get('admin_id') != 1) {
            $this->error(__admin_lang('super_admin_protected'));
        }

        $data = request()->only([
            'name',
            'role_id',
            'group_id',
            'enable',
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
            $this->error(__admin_lang('account_exists'));
        }

        $result = $this->validate($data, [
            'role_id|' . __admin_lang('role_id') => 'require',
            'username|' . __admin_lang('username') => 'require',
            'name|' . __admin_lang('name') => 'require',
            'email|' . __admin_lang('email') => 'email',
            'phone|' . __admin_lang('phone') => 'mobile',
            'errors|' . __admin_lang('errors_count') => 'number',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        if (!empty($data['password'])) {
            $len = mb_strlen($data['password']);

            if ($len < 6 || $len > 20) {
                $this->error(__admin_lang('password_length_6_20'));
            }

            $password = $this->dataModel->passCrypt($data['password']);

            $data['password'] = $password[0];
            $data['salt'] = $password[1];
        } else {
            unset($data['password']);
        }

        if (!empty($data['phone']) && !preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
            $this->error(__admin_lang('phone_format_error'));
        }

        $res = 0;

        if ($id) {
            $exists = $this->dataModel->where([$this->getPk() => $id])->find();
            if ($exists) {
                $res = $exists->force()->save($data);
            }
        } else {
            if (!isset($data['password']) || empty($data['password'])) {
                $this->error(__admin_lang('please_input_password'));
            }
            $res = $this->dataModel->exists(false)->save($data);
        }

        if (!$res) {
            $this->error(__admin_lang('save_failed'));
        }

        return $this->builder()->layer()->closeRefresh(1, __admin_lang('save_success'));
    }

    /**
     * Undocumented function
     *
     * @title 清空错误次数
     * @return mixed
     */
    public function clearErrors()
    {
        $ids = input('ids', '');

        $ids = array_filter(explode(',', $ids), 'strlen');

        if (empty($ids)) {
            $this->error(__admin_lang('invalid_params'));
        }

        $res = 0;

        foreach ($ids as $id) {
            if ($this->dataModel->where(['id' => $id])->update(['errors' => 0])) {
                $res += 1;
            }
        }

        if ($res) {
            $this->success(__admin_lang('reset_errors_success', [$res]));
        } else {
            $this->error(__admin_lang('reset_failed'));
        }
    }
}

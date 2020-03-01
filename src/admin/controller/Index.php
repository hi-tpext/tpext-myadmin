<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\facade\Request;
use tpext\builder\common\Builder;
use tpext\common\ExtLoader;
use tpext\common\Tool;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminUser;

class Index extends Controller
{
    protected $dataModel;

    protected function initialize()
    {
        $this->dataModel = new AdminUser;
    }

    public function index()
    {
        $admin_user = session('admin_user');

        $this->assign('admin_user', $admin_user);

        return $this->fetch();
    }

    public function dashbord()
    {
        return $this->fetch();
    }

    public function logout()
    {
        session('admin_user', null);
        session('admin_id', null);

        $this->success('注销成功', url('login'));
    }

    public function changePwd()
    {
        if (request()->isPost()) {
            $data = request()->only([
                'password_old',
                'password_new',
                'password_confirm',
            ], 'post');

            $result = $this->validate($data, [
                'password_old|原密码' => 'require',
                'password_new|新密码' => 'require',
                'password_confirm|确认新密码' => 'require',
            ]);

            if (true !== $result) {

                $this->error($result);
            }

            $user = $this->dataModel->get(session('admin_id'));

            if (!$this->dataModel->passValidate($user['password'], $user['salt'], $data['password_old'])) {
                $this->error('原密码不正确');
            }

            if ($data['password_new'] != $data['password_confirm']) {
                $this->error('两次输入新密码不匹配');
            }

            if ($data['password_new'] == $data['password_old']) {
                $this->error('新旧密码一样' . json_encode($data));
            }

            $password = $this->dataModel->passCrypt($data['password_new']);

            $editData['password'] = $password[0];
            $editData['salt'] = $password[1];

            $res = $this->dataModel->where(['id' => $user['id']])->update($editData);

            if ($res) {
                ExtLoader::trigger('admin_change_pwd', $user);
                session('admin_user', $this->dataModel->get($user['id']));

                $this->success('修改成功');
            } else {
                $this->error('修改失败');
            }

        } else {
            $builder = Builder::getInstance('个人设置', '修改密码');

            $form = $builder->form();

            $form->password('password_old', '原密码')->required()->help('输入您现在使用代密码');
            $form->password('password_new', '新密码')->required()->help('输入新密码（6～20位）');
            $form->password('password_confirm', '确认新密码')->required()->help('再次输入新密码');

            return $builder->render();
        }
    }

    public function profile()
    {
        if (request()->isPost() && !input('post.is_search', '0')) {
            return $this->saveProfile();
        } else {
            $builder = Builder::getInstance('个人设置', '资料修改');

            $form = $builder->form(4);
            $form->show('username', '登录帐号')->size(3, 9);
            $form->text('name', '姓名')->required()->beforSymbol('<i class="mdi mdi-rename-box"></i>')->size(3, 9);
            $form->image('avatar', '头像')->default('/assets/lightyearadmin/images/no-avatar.jpg')->size(3, 9);
            $form->text('email', '电子邮箱')->beforSymbol('<i class="mdi mdi-email-variant"></i>')->size(3, 9);
            $form->text('phone', '手机号')->beforSymbol('<i class="mdi mdi-cellphone-iphone"></i>')->size(3, 9);
            $form->show('login_time', '登录时间')->size(3, 9);
            $form->show('create_time', '添加时间')->size(3, 9);
            $form->show('update_time', '修改时间')->size(3, 9);

            $form->butonsSizeClass('btn-sm');

            $user = $this->dataModel->get(session('admin_id'));

            $form->fill($user);

            /*******************************/

            $col = $builder->column(8);

            $searchForm = $col->form();

            $searchForm->text('path', '路径', 6)->maxlength(20);
            $searchForm->radio('method', '提交方式', 6)->options(['' => '全部', 'GET' => 'get', 'POST' => 'post']);
            $searchForm->datetimeRange('create_time', '时间', 6);
            $searchForm->hidden('is_search')->value(1);

            $table = $col->table();

            $table->searchForm($searchForm);

            $table->show('id', 'ID');
            $table->show('path', '路径');
            $table->show('method', '提交方式');
            $table->show('create_time', '添加时间')->getWapper()->addStyle('width:180px');
            $table->show('update_time', '修改时间')->getWapper()->addStyle('width:180px');
            $table->getToolbar()
                ->btnRefresh();

            $pagezise = 8;

            $page = input('__page__/d', 1);

            $page = $page < 1 ? 1 : $page;

            $searchData = request()->only([
                'path',
                'method',
                'create_time',
            ], 'post');

            $where = [];
            $whereTime = [];

            if (!empty($searchData['path'])) {
                $where['path'] = ['like' => $searchData['path']];
            }

            if (!empty($searchData['method'])) {
                $where['method'] = ['eq' => $searchData['method']];
            }

            if (!empty($searchData['create_time'])) {
                $whereTime = explode(' ~ ', $searchData['create_time']);
            }

            $count = 0;

            if (count($whereTime)) {
                $count = AdminOperationLog::where($where)->whereBetweenTime('create_time', $whereTime[0], $whereTime[1])->count();
                $data = AdminOperationLog::where($where)->whereBetweenTime('create_time', $whereTime[0], $whereTime[1])->order('id desc')->limit(($page - 1) * $pagezise, $pagezise)->select();
            } else {
                $count = AdminOperationLog::where($where)->count();
                $data = AdminOperationLog::where($where)->order('id desc')->limit(($page - 1) * $pagezise, $pagezise)->select();
            }

            $table->data($data);
            $table->paginator($count, $pagezise);

            if (request()->isAjax()) {
                return $table->partial()->render(false);
            }

            return $builder->render();
        }
    }

    private function saveProfile()
    {
        $data = request()->only([
            'name',
            'avatar',
            'email',
            'phone',
        ], 'post');

        $result = $this->validate($data, [
            'name|姓名' => 'require',
            'email|电子邮箱' => 'email',
            'phone|手机号' => 'mobile',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        $user = $this->dataModel->get(session('admin_id'));

        $data['update_time'] = date('Y-m-d H:i:s');

        $res = $this->dataModel->where(['id' => $user['id']])->update($data);

        if ($res) {

            session('admin_user', $this->dataModel->get($user['id']));

            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    public function clearCache()
    {
        if (request()->isPost()) {
            $types = input('post.types');

            if (empty($types)) {
                $this->error('请选择清除类型');
            }

            if (in_array(1, $types)) {
                Tool::deleteDir(app()->getRuntimePath() . 'cache');
            }
            if (in_array(2, $types)) {
                Tool::deleteDir(app()->getRuntimePath() . 'temp');
            }

            $this->success('操作成功！');

        } else {
            $builder = Builder::getInstance('系统设置', '清空缓存');

            $form = $builder->form();

            $form->checkbox('types', '耀清除代缓存类型')->options([
                1 => 'cache',
                2 => 'temp',
            ])->checkallBtn('全部')->inline(false);

            return $builder->render();
        }
    }

    public function login()
    {
        if (request()->isPost()) {
            $data = request()->only([
                'username',
                'password',
                'captcha',
            ], 'post');

            $result = $this->validate($data, [
                'username|登录帐号' => 'require',
                'password|密码' => 'require',
                'captcha|验证码' => 'require',
            ]);

            if (true !== $result) {

                $this->error($result);
            }

            if (!captcha_check($data['captcha'], 'admin')) {
                $this->error('验证码错误');
            }

            $user = $this->dataModel->where(['username' => $data['username']])->find();

            if (!$user) {
                $this->error('用户帐号不存');
            }

            if ($user['errors'] > 10) {
                $try_login = cache('admin_try_login_' . $user['id']);

                if ($try_login) {

                    $time_gone = time() - $try_login;

                    if ($time_gone < $user['errors']) {
                        $this->error('错误次数过多，请' . ($user['errors'] - $time_gone) . '秒后再试' . $time_gone);
                    }
                }
            }

            if (!$this->dataModel->passValidate($user['password'], $user['salt'], $data['password'])) {

                $this->dataModel->where(['id' => $user['id']])->setInc('errors');

                cache('admin_try_login_' . $user['id'], time());

                $this->error('密码错误');
            }

            $this->dataModel->where(['id' => $user['id']])->update(['login_time' => date('Y-m-d H:i:s'), 'errors' => 0]);
            cache('admin_try_login_' . $user['id'], null);

            session('admin_user', $user);
            session('admin_id', $user['id']);

            AdminOperationLog::create([
                'user_id' => $user['id'],
                'path' => 'admin/index/login',
                'method' => Request::method(),
                'ip' => Request::ip(),
                'data' => json_encode([])
            ]);

            ExtLoader::trigger('admin_login', $user);

            $this->success('登录成功');
        } else {
            return $this->fetch();
        }
    }
}

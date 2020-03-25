<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\Db;
use think\facade\Request;
use tpext\builder\common\Builder;
use tpext\common\ExtLoader;
use tpext\common\Tool;
use tpext\myadmin\admin\model\AdminMenu;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\admin\model\AdminRoleMenu;
use tpext\myadmin\admin\model\AdminRolePermission;
use tpext\myadmin\admin\model\AdminUser;

class Index extends Controller
{
    protected $dataModel;
    protected $menuModel;
    protected $roleMenuModel;
    protected $rolePerModel;
    protected $perModel;

    protected function initialize()
    {
        $this->dataModel = new AdminUser;
        $this->menuModel = new AdminMenu;
        $this->roleMenuModel = new AdminRoleMenu;
        $this->rolePerModel = new AdminRolePermission;
        $this->perModel = new AdminPermission;
    }

    public function index()
    {
        $admin_user = session('admin_user');
        $menus = [];
        if ($admin_user['role_id'] == 1) {
            $list = $this->menuModel->order('parent_id,sort')->all();
            if (count($list) == 0 && $admin_user['id'] == 1) {
                $menus = [
                    [
                        'id' => 1,
                        'name' => '首页',
                        'url' => url('welcome'),
                        'pid' => 0,
                        'icon' => 'mdi mdi-home',
                        'is_out' => 0,
                        'is_home' => 1,
                    ],
                    [
                        'id' => 2,
                        'name' => '菜单管理',
                        'url' => url('menu/index'),
                        'pid' => 0,
                        'icon' => 'mdi mdi-arrange-send-to-back',
                        'is_out' => 0,
                        'is_home' => 0,
                    ],
                    [
                        'id' => 3,
                        'name' => '权限设置',
                        'url' => url('permission/index'),
                        'pid' => 0,
                        'icon' => 'mdi mdi-account-key',
                        'is_out' => 0,
                        'is_home' => 0,
                    ], [
                        'id' => 4,
                        'name' => '管理员',
                        'url' => url('admin/index'),
                        'pid' => 0,
                        'icon' => 'mdi mdi-account-card-details',
                        'is_out' => 0,
                        'is_home' => 0,
                    ], [
                        'id' => 5,
                        'name' => '角色管理',
                        'url' => url('role/index'),
                        'pid' => 0,
                        'icon' => 'mdi mdi-account-multiple',
                        'is_out' => 0,
                        'is_home' => 0,
                    ], [
                        'id' => 6,
                        'name' => '扩展管理',
                        'url' => url('tpext/index'),
                        'pid' => 0,
                        'icon' => 'mdi mdi-blur',
                        'is_out' => 0,
                        'is_home' => 0,
                    ],
                ];
            } else {
                foreach ($list as $li) {
                    $menus[] = [
                        'id' => $li['id'],
                        'name' => $li['title'],
                        'url' => $li['url'],
                        'pid' => $li['parent_id'],
                        'icon' => 'mdi ' . $li['icon'],
                        'is_out' => 0,
                        'is_home' => $li['id'] == 1 ? 1 : 0,
                    ];
                }
            }
        } else {
            $menus = $this->menuModel->buildMenus($admin_user);
        }

        $this->assign('admin_user', $admin_user);
        $this->assign('menus', json_encode($menus));
        $this->assign('dashbord', count($menus) ? $menus[0] : ['url' => url('welcome'), 'name' => '首页']);

        return $this->fetch();
    }

    public function denied()
    {
        return '<p>无权访问</p>';
    }

    public function welcome()
    {
        return $this->fetch();
    }

    public function logout()
    {
        session('admin_user', null);
        session('admin_id', null);

        $this->success('注销成功！', url('login'));
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

                $user = $this->dataModel->get($user['id']);

                unset($user['password'], $user['salt']);

                session('admin_user', $user);

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
        if (request()->isPost() && !input('post.__search__', '0')) {
            return $this->saveProfile();
        } else {
            $builder = Builder::getInstance('个人设置', '资料修改');

            $form = $builder->form(6);
            $form->show('username', '登录帐号')->size(3, 9);
            $form->text('name', '姓名')->required()->beforSymbol('<i class="mdi mdi-rename-box"></i>')->size(3, 9);
            $form->image('avatar', '头像')->default('/assets/lightyearadmin/images/no-avatar.jpg')->size(3, 9);
            $form->text('email', '电子邮箱')->beforSymbol('<i class="mdi mdi-email-variant"></i>')->size(3, 9);
            $form->text('phone', '手机号')->beforSymbol('<i class="mdi mdi-cellphone-iphone"></i>')->size(3, 9);
            $form->show('login_time', '登录时间')->size(3, 9);
            $form->show('create_time', '添加时间')->size(3, 9);
            $form->show('update_time', '修改时间')->size(3, 9);

            $form->butonsSizeClass('btn-xs');

            $user = $this->dataModel->get(session('admin_id'));

            $form->fill($user);

            /*******************************/

            $table = $builder->table(6);

            $table->show('id', 'ID');
            $table->show('path', '路径');
            $table->show('ip', 'IP');
            $table->show('create_time', '登录时间');
            $table->getToolbar()
                ->btnRefresh();
            $table->useActionbar(false);
            $table->rowCheckbox(false);

            $pagesize = 10;

            $page = input('__page__/d', 1);

            $page = $page < 1 ? 1 : $page;

            $count = 0;

            $where['user_id'] = ['eq', session('admin_id')];
            $where['path'] = ['like', 'admin/index/login'];

            $sortOrder = input('__sort__' ,'id desc');

            $count = AdminOperationLog::where($where)->count();
            $data = AdminOperationLog::where($where)->order($sortOrder)->limit(($page - 1) * $pagesize, $pagesize)->select();

            $table->data($data);
            $table->paginator($count, $pagesize);

            if (request()->isAjax()) {
                return $table->partial()->render();
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

            $user = $this->dataModel->get($user['id']);

            unset($user['password'], $user['salt']);

            session('admin_user', $user);

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
            if (in_array(3, $types)) {

                $dirs = ['', 'assets', 'minify', ''];
                $scriptName = $_SERVER['SCRIPT_FILENAME'];
                $minifyDir = realpath(dirname($scriptName)) . implode(DIRECTORY_SEPARATOR, $dirs);

                Tool::deleteDir($minifyDir);
            }

            $this->success('操作成功！');

        } else {
            $builder = Builder::getInstance('系统设置', '清空缓存');

            $form = $builder->form();

            $form->checkbox('types', '要清除的缓存类型')->options([
                1 => '数据缓存[cache]',
                2 => '模板缓存[temp]',
                3 => '资源压缩[minify]',
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

            if ($user['enable'] == 0) {
                $this->error('帐号已禁用');
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
            unset($user['password'], $user['salt']);
            session('admin_user', $user);
            session('admin_id', $user['id']);
            session('admin_last_time', time());

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

            $tableName = config('database.prefix') . 'admin_user';

            $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

            if (empty($isTable)) {
                Tool::deleteDir(app()->getRuntimePath() . 'cache');
            }

            return $this->fetch();
        }
    }
}

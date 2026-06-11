<?php

namespace tpext\myadmin\admin\controller;

use think\facade\Db;
use tpext\think\App;
use think\Controller;
use Workerman\Worker;
use tpext\common\Tool;
use think\facade\Cache;
use think\facade\Session;
use Webman\Http\Response;
use tpext\common\ExtLoader;
use tpext\myadmin\common\Module;
use think\captcha\facade\Captcha;
use tpext\builder\common\Builder;
use tpext\myadmin\admin\model\AdminMenu;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\admin\model\AdminRoleMenu;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminRolePermission;

/**
 * Undocumented class
 * @title 首页
 */
class Index extends Controller
{
    /**
     * Undocumented variable
     *
     * @var AdminUser
     */
    protected $dataModel;
    /**
     * Undocumented variable
     *
     * @var AdminMenu
     */
    protected $menuModel;
    /**
     * AdminRoleMenu
     *
     * @var AdminRolePermission
     */
    protected $roleMenuModel;
    /**
     * Undocumented variable
     *
     * @var AdminRolePermission
     */
    protected $rolePerModel;
    /**
     * Undocumented variable
     *
     * @var AdminPermission
     */
    protected $perModel;

    protected function initialize()
    {
        Module::getInstance()->loadLang('index');

        $this->dataModel = new AdminUser;
        $this->menuModel = new AdminMenu;
        $this->roleMenuModel = new AdminRoleMenu;
        $this->rolePerModel = new AdminRolePermission;
        $this->perModel = new AdminPermission;

        $lang = Module::getInstance()->getLang('index');
        $this->assign('__lang', json_encode($lang));
    }

    public function index()
    {
        $admin_user = Session::get('admin_user');
        $menus = [];
        if ($admin_user['role_id'] == 1) {
            $list = $this->menuModel->where(['enable' => 1])->order('parent_id,sort')->select();
            if (count($list) == 0 && $admin_user['id'] == 1) {
                $menus = [
                    [
                        'id' => 1,
                        'name' => __admin_lang('home'),
                        'url' => url('welcome'),
                        'pid' => 0,
                        'icon' => 'mdi mdi-home',
                        'is_out' => 0,
                        'is_home' => 1,
                    ],
                    [
                        'id' => 2,
                        'name' => __admin_lang('system_management'),
                        'url' => '#',
                        'pid' => 0,
                        'icon' => 'mdi mdi-settings',
                        'is_out' => 0,
                        'is_home' => 0,
                    ],
                    [
                        'id' => 3,
                        'name' => __admin_lang('menu_management'),
                        'url' => url('menu/index'),
                        'pid' => 2,
                        'icon' => 'mdi mdi-arrange-send-to-back',
                        'is_out' => 0,
                        'is_home' => 0,
                    ],
                    [
                        'id' => 4,
                        'name' => __admin_lang('permission_settings'),
                        'url' => url('permission/index'),
                        'pid' => 2,
                        'icon' => 'mdi mdi-account-key',
                        'is_out' => 0,
                        'is_home' => 0,
                    ],
                    [
                        'id' => 5,
                        'name' => __admin_lang('admin_management'),
                        'url' => url('admin/index'),
                        'pid' => 2,
                        'icon' => 'mdi mdi-account-card-details',
                        'is_out' => 0,
                        'is_home' => 0,
                    ],
                    [
                        'id' => 6,
                        'name' => __admin_lang('role_management'),
                        'url' => url('role/index'),
                        'pid' => 2,
                        'icon' => 'mdi mdi-account-multiple',
                        'is_out' => 0,
                        'is_home' => 0,
                    ],
                    [
                        'id' => 7,
                        'name' => __admin_lang('ext_management'),
                        'url' => url('extension/index'),
                        'pid' => 2,
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

        $config = Module::getInstance()->getConfig();

        $this->assign('admin_user', $admin_user);
        $this->assign('menus', json_encode($menus));
        $this->assign('dashbord', count($menus) ? $menus[0] : ['id' => 1, 'url' => url('welcome'), 'name' => __admin_lang('home'), 'pid' => 0, 'icon' => 'mdi mdi-home', 'is_out' => 0, 'is_home' => 1]);
        $this->assign('index_top_menu', $config['index_top_menu'] ?? 1);

        $template = 'index';
        if (!empty($config['index_page_style']) && $config['index_page_style'] != 1) { //下拉选择的其他模板
            $template = $config['index_page_style'];

            $template = str_replace('__WWW__', App::getRootPath(), $template);

            if (!is_file($template)) { //其他模板不存在，回到默认
                $template = 'index';
            }
        }

        $config = [];
        if (preg_match('/(.+?[\/\\\]view[\/\\\]).+?/', $template, $mch)) {
            $config['view_path'] = $mch[1];
        }

        return $this->fetch($template, [], $config);
    }

    public function denied()
    {
        return '<span style="color:#333;font-size:12px;">' . __admin_lang('access_denied') . '</span>';
    }

    /**
     * Undocumented function
     *
     * @title 欢迎页面
     * @return mixed
     */
    public function welcome()
    {
        $sysInfo['os'] = PHP_OS;
        $sysInfo['zlib'] = function_exists('gzclose') ? __admin_lang('yes') : __admin_lang('no');

        $sysInfo['timezone'] = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
        $sysInfo['curl'] = function_exists('curl_init') ? __admin_lang('yes') : __admin_lang('no');
        $sysInfo['web_server'] = 'Workerman/' . Worker::VERSION;
        $sysInfo['user_agent'] = request()->header('User-Agent');
        $sysInfo['php_version'] = phpversion();
        $sysInfo['ip'] = request()->ip();
        $sysInfo['fileupload'] = @ini_get('upload_max_filesize') ?: __admin_lang('unknown');
        $sysInfo['sys_time'] = date('Y-m-d H:i:s', time());
        $sysInfo['max_ex_time'] = @ini_get("max_execution_time") . 's';
        $sysInfo['set_time_limit'] = function_exists("set_time_limit") ? true : false;
        $sysInfo['domain'] = request()->host();
        $sysInfo['memory_limit'] = ini_get('memory_limit');
        $mysqlinfo = Db::query('select VERSION() as version');
        $sysInfo['mysql_version'] = $mysqlinfo[0]['version'];
        if (function_exists('gd_info')) {
            $gd = gd_info();
            $sysInfo['gdinfo'] = $gd['GD Version'];
        } else {
            $sysInfo['gdinfo'] = __admin_lang('unknown');
        }
        return $this->fetch('', ['sys_info' => $sysInfo]);
    }

    public function logout()
    {
        Session::delete('admin_user');
        Session::delete('admin_id');

        $config = Module::getInstance()->getConfig();

        Session::delete('admin_last_time');

        if (isset($config['login_session_key']) && $config['login_session_key'] == '1') {
            $this->success(__admin_lang('logout_success'), '/');
        } else {
            $this->success(__admin_lang('logout_success'), url('/admin/index/login'));
        }
    }

    /**
     * Undocumented function
     *
     * @title 修改个人密码
     * @return mixed
     */
    public function changePwd()
    {
        if (request()->isPost()) {
            $data = request()->only([
                'password_old',
                'password_new',
                'password_confirm',
            ], 'post');

            $result = $this->validate($data, [
                'password_old|' . __admin_lang('old_password') => 'require',
                'password_new|' . __admin_lang('new_password') => 'require',
                'password_confirm|' . __admin_lang('confirm_password') => 'require',
            ]);

            if (true !== $result) {

                $this->error($result);
            }

            $this->checkToken();

            $user = $this->dataModel->find(Session::get('admin_id'));

            if (!$this->dataModel->passValidate($user['password'], $user['salt'], $data['password_old'])) {
                $this->error(__admin_lang('old_password_incorrect'));
            }

            if ($data['password_new'] != $data['password_confirm']) {
                $this->error(__admin_lang('password_not_match'));
            }

            if ($data['password_new'] == $data['password_old']) {
                $this->error(__admin_lang('same_as_old_password'));
            }

            $password = $this->dataModel->passCrypt($data['password_new']);

            $editData['password'] = $password[0];
            $editData['salt'] = $password[1];

            $res = $user->force()->save($editData);

            if ($res) {
                ExtLoader::trigger('admin_change_pwd', $user);

                $user = $this->dataModel->find($user['id']);

                unset($user['password'], $user['salt']);

                Session::set('admin_user', $user->toArray());

                $this->success(__admin_lang('modify_success'));
            } else {
                $this->error(__admin_lang('modify_failed'));
            }
        } else {
            $builder = Builder::getInstance(__admin_lang('personal_settings'), __admin_lang('change_password'));

            $form = $builder->form();

            $form->password('password_old', __admin_lang('old_password'))->required()->help(__admin_lang('old_password_placeholder'));
            $form->password('password_new', __admin_lang('new_password'))->required()->help(__admin_lang('new_password_placeholder'));
            $form->password('password_confirm', __admin_lang('confirm_password'))->required()->help(__admin_lang('confirm_password_placeholder'));

            return $builder->render();
        }
    }

    /**
     * Undocumented function
     *
     * @title 个人资料
     * @return mixed
     */
    public function profile()
    {
        if (request()->isPost() && !input('post.__search__', '0')) {
            return $this->saveProfile();
        } else {
            $builder = Builder::getInstance(__admin_lang('personal_settings'), __admin_lang('profile_edit'));

            $form = $builder->form(6);
            $form->show('username')->size(3, 9);
            $form->text('name')->required()->beforSymbol('<i class="mdi mdi-rename-box"></i>')->size(3, 9);
            $form->image('avatar')->default('/assets/lightyearadmin/images/no-avatar.jpg')->size(3, 9)->imageResize(200, 200);
            $form->text('email')->beforSymbol('<i class="mdi mdi-email-variant"></i>')->size(3, 9);
            $form->text('phone')->beforSymbol('<i class="mdi mdi-cellphone-iphone"></i>')->size(3, 9);
            $form->show('login_time')->size(3, 9);
            $form->show('create_time')->size(3, 9);
            $form->show('update_time')->size(3, 9);

            $form->butonsSizeClass('btn-xs');

            $user = $this->dataModel->find(Session::get('admin_id'));

            $form->fill($user);

            /*******************************/

            $table = $builder->table(6);

            $table->show('id');
            $table->show('path');
            $table->show('ip');
            $table->show('create_time', __admin_lang('login_time'));
            $table->getToolbar()
                ->btnRefresh();
            $table->useActionbar(false);
            $table->useCheckbox(false);

            $pagesize = input('__pagesize__/d');

            $pagesize = $pagesize ? $pagesize : 10;

            $page = input('__page__/d', 1);

            $page = $page < 1 ? 1 : $page;

            $count = 0;

            $where['user_id'] = ['=', Session::get('admin_id')];
            $where['path'] = ['like', 'admin/index/login'];

            $sortOrder = input('__sort__', 'id desc');

            $count = AdminOperationLog::where($where)->count();
            $data = AdminOperationLog::where($where)->order($sortOrder)->limit(($page - 1) * $pagesize, $pagesize)->select();

            $table->data($data);
            $table->paginator($count, $pagesize);
            $table->useExport(false);
            $table->sortOrder($sortOrder);

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
            'name|' . __admin_lang('name') => 'require',
            'email|' . __admin_lang('email') => 'email',
            'phone|' . __admin_lang('phone') => 'mobile',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        $this->checkToken();

        $res = $this->dataModel->where(['id' => Session::get('admin_id')])->save($data);

        if ($res) {

            $user = $this->dataModel->find(Session::get('admin_id'));

            unset($user['password'], $user['salt']);

            Session::set('admin_user', $user->toArray());

            $this->success(__admin_lang('modify_success'));
        } else {
            $this->error(__admin_lang('modify_failed'));
        }
    }

    /**
     * Undocumented function
     *
     * @title 清空缓存
     * @return mixed
     */
    public function clearCache()
    {
        if (request()->isPost()) {

            $this->checkToken();

            $types = input('post.types');

            if (empty($types)) {
                $this->error(__admin_lang('select_clear_type'));
            }

            if (in_array(1, $types)) {
                Cache::clear();
            }
            if (in_array(2, $types)) {
                Tool::deleteDir(App::getRuntimePath() . 'temp');
            }
            if (in_array(3, $types)) {

                $dirs = ['', 'assets', 'minify', ''];

                $minifyDir = App::getPublicPath() . implode(DIRECTORY_SEPARATOR, $dirs);

                Tool::deleteDir($minifyDir);
            }

            $this->success(__admin_lang('operation_success'));
        } else {
            $builder = Builder::getInstance(__admin_lang('system_settings'), __admin_lang('clear_cache'));

            $form = $builder->form();

            $form->checkbox('types', __admin_lang('cache_types'))->options([
                1 => __admin_lang('data_cache'),
                2 => __admin_lang('template_cache'),
                3 => __admin_lang('minify_cache'),
            ])->checkallBtn(__admin_lang('all_types'))->inline(false);

            return $builder->render();
        }
    }

    public function login()
    {
        $config = Module::getInstance()->getConfig();

        if (isset($config['login_session_key']) && $config['login_session_key'] == '1') {
            if (!Session::has('login_session_key')) {
                if (request()->cookie('tpext_myadmin_entry')) {
                    $tpext_myadmin_entry = rawurldecode(request()->cookie('tpext_myadmin_entry'));
                    return $this->error(__admin_lang('hidden_login_redirect') . request()->domain() . url($tpext_myadmin_entry, [], false) . __admin_lang('reenter_after_browser_change'), $tpext_myadmin_entry, '', 20);
                }
                return new Response(403, [], '<div style="text-align:center"><h1>' . __admin_lang('verification_failed') . '</h1><hr>' . __admin_lang('please_use_admin_entrance') . '</div>');
            }
        }

        if (request()->isPost()) {
            $data = request()->only([
                'username',
                'password',
                'captcha',
            ], 'post');

            $result = $this->validate($data, [
                'username|' . __admin_lang('username') => 'require',
                'password|' . __admin_lang('password') => 'require',
                'captcha|' . __admin_lang('captcha') => 'require',
            ]);

            if (true !== $result) {

                $this->error($result);
            }

            if (!Captcha::check($data['captcha'])) {
                $this->error(__admin_lang('captcha_error'));
            }

            $user = $this->dataModel->where(['username' => $data['username']])->find();

            if (!$user) {
                $this->error(__admin_lang('account_not_exist'));
            }

            if ($user['enable'] == 0) {
                $this->error(__admin_lang('account_disabled'));
            }

            if ($user['errors'] > 10) {

                $errors = $user['errors'] > 300 ? 300 : $user['errors'];

                $try_login = Cache::get('admin_try_login_' . $user['id']);

                if ($try_login) {

                    $time_gone = time() - $try_login;

                    if ($time_gone < $errors) {
                        $this->error(__admin_lang('login_error_limit', [$errors - $time_gone]));
                    }
                }
            }

            if (!$this->dataModel->passValidate($user['password'], $user['salt'], $data['password'])) {

                $this->dataModel->where(['id' => $user['id']])->inc('errors');

                Cache::set('admin_try_login_' . $user['id'], time());

                $this->error(__admin_lang('password_error'));
            }

            $upData = ['login_time' => date('Y-m-d H:i:s'), 'errors' => 0];

            if ($user['salt'] != 'pwd_hash') {
                $password = $this->dataModel->passCrypt($data['password']);
                $upData['password'] = $password[0];
                $upData['salt'] = $password[1];
            }

            $this->dataModel->where(['id' => $user['id']])->update($upData);

            Cache::delete('admin_try_login_' . $user['id']);
            unset($user['password'], $user['salt']);
            Session::set('admin_user', $user->toArray());
            Session::set('admin_id', $user['id']);
            Session::delete('login_session_key');

            Session::set('admin_last_time', time());

            AdminOperationLog::create([
                'user_id' => $user['id'],
                'path' => 'admin/index/login',
                'method' => request()->method(),
                'ip' => request()->ip(),
                'data' => json_encode([])
            ]);

            ExtLoader::trigger('admin_login', $user);

            $after_login_url = Session::get('after_login_url', '/admin');

            Session::delete($after_login_url);

            $this->success(__admin_lang('login_success'), $after_login_url);
        } else {

            if (!Module::isInstalled()) {
                Cache::clear();
            }

            $this->assign(['login_in_top' => $config['login_in_top'], 'login_css_file' => $config['login_css_file']]);

            $rootPath = App::getRootPath();

            $template = '';
            if (!empty($config['login_page_view_path']) && file_exists($rootPath . $config['login_page_view_path'])) { //直接填写的模板路径
                $template = $rootPath . $config['login_page_view_path'];
            } else { //下拉选择模板路径
                $template = 'login';
                if (!empty($config['login_page_style'])) {
                    $template = $config['login_page_style'];
                    $template = str_replace('__WWW__', $rootPath, $template);
                    if (!is_file($template)) { //其他模板不存在，回到默认
                        $template = 'login';
                    }
                }
            }

            $config = [];
            if (preg_match('/(.+?[\/\\\]view[\/\\\]).+?/', $template, $mch)) {
                $config['view_path'] = $mch[1];
            }

            return $this->fetch($template, [], $config);
        }
    }

    public function captcha()
    {
        $config = Module::getInstance()->getConfig();

        if (isset($config['login_session_key']) && $config['login_session_key'] == '1') {
            if (!Session::has('login_session_key')) {
                return new Response(404, [], '404 Not found');
            }
        }

        return Captcha::create();
    }

    protected function checkToken()
    {
        $token = Session::get('_csrf_token_');

        if (empty($token) || $token != input('__token__')) {
            $this->error(__admin_lang('token_error'));
        }
    }
}

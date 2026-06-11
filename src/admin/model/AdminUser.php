<?php

namespace tpext\myadmin\admin\model;

use think\Model;
use tpext\builder\inface\Auth;
use tpext\myadmin\common\Module;
use think\facade\Session;

class AdminUser extends Model implements Auth
{
    protected $autoWriteTimestamp = 'datetime';

    protected static $adminGroupTitle = null;

    protected $hidden = ['group', 'role', 'password', 'salt'];

    public function getAdminGroupModel()
    {
        return new AdminGroup;
    }

    public function getAdminGroupTitle()
    {
        if (is_null(self::$adminGroupTitle)) {
            self::$adminGroupTitle = '分组';
            $instance = Module::getInstance();
            $config = $instance->getConfig();
            if (!empty($config['admin_group_title'])) {
                self::$adminGroupTitle = $config['admin_group_title'];
            }
        }

        return self::$adminGroupTitle;
    }

    public function group()
    {
        return $this->belongsTo(AdminGroup::class, 'group_id', 'id');
    }

    public function role()
    {
        return $this->belongsTo(AdminRole::class, 'role_id', 'id');
    }

    /**
     * Undocumented function
     *
     * @return $this
     */
    public static function current()
    {
        $admin_id = Session::get('admin_id');

        return static::find($admin_id);
    }

    /**
     * Undocumented function
     *
     * @param string $pwd
     * @return array
     */
    public function passCrypt($pwd)
    {
        $hash = password_hash($pwd, PASSWORD_BCRYPT, ['cost' => 12]);

        return [$hash, 'pwd_hash'];
        // $pwd = md5($pwd);
        // $salt = substr(md5(time()), mt_rand(0, 22), 10);
        // $pwd = md5($salt . $pwd . $salt);

        // return [$pwd, $salt];
    }

    /**
     * Undocumented function
     *
     * @param string $savedCryptPwd
     * @param string $savedSalt
     * @param string $inputPwd
     * @return boolean
     */
    public function passValidate($savedCryptPwd, $savedSalt, $inputPwd)
    {
        if ($savedSalt == 'pwd_hash') {
            return password_verify($inputPwd, $savedCryptPwd);
        }

        $inputPwd = md5($inputPwd);

        return $savedCryptPwd == md5($savedSalt . $inputPwd . $savedSalt);
    }

    /**
     * Undocumented function
     *
     * @param int $admin_id
     * @param string $controller
     * @param string $action
     * @return boolean
     */
    public function checkPermission($admin_id, $controller, $action)
    {
        $user = static::find($admin_id);

        if (!$user) {
            Session::delete('admin_user');
            Session::delete('admin_id');
            return false;
        }

        if ($user['enable'] == 0) {
            Session::delete('admin_user');
            Session::delete('admin_id');
            return false;
        }

        unset($user['password'], $user['salt']);

        Session::set('admin_user', $user->toArray());

        $url = "/admin/$controller/$action";

        return static::checkUrl($url, $user);
    }

    /**
     * Undocumented function
     *
     * @param string $url
     * @param array $user
     * @return boolean
     */
    public static function checkUrl($url, $user = null)
    {
        $url = preg_replace('/\.html/i', '', $url);
        $url = preg_replace('/\?.*$/i', '', $url);

        if (!Module::isInstalled()) {
            if (preg_match('/^\/admin\/extension\/\w+/i', $url)) {
                return true;
            }
        }
        $user = $user ? $user : Session::get('admin_user');

        if (!$user) {
            return false;
        }

        if ($user['role_id'] == 1) {
            return true;
        }

        $path = explode('/', trim($url, '/'));

        if (count($path) < 3) {
            return false;
        }

        $url = '/' . strtolower(implode('/', $path));

        $noNeed = [
            '/admin/index/index',
            '/admin/index/captcha',
            '/admin/index/welcome',
            '/admin/index/denied',
            '/admin/index/logout',
            '/admin/index/login',
            '/admin/index/profile',
            '/admin/index/changepwd',
        ];

        if (in_array($url, $noNeed)) {
            return true;
        }

        $role = AdminRole::find($user['role_id']);

        if (!$role) {
            return false;
        }

        $prmission = AdminPermission::where(['url' => $url])->find();

        if (!$prmission) {
            return false;
        }

        $rolePrmission = AdminRolePermission::where(['role_id' => $user['role_id'], 'permission_id' => $prmission['id']])->find();

        if (!$rolePrmission) {
            return false;
        }

        return true;
    }
}

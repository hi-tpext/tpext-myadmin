<?php

namespace tpext\myadmin\admin\model;

use think\helper\Str;
use think\Model;
use tpext\builder\inface\Auth;
use tpext\myadmin\common\Module;

class AdminUser extends Model implements Auth
{
    protected $autoWriteTimestamp = 'datetime';

    protected static $adminGroupModel;

    protected static $adminGroupTitle = '分组';

    protected static function init()
    {
        $instance = Module::getInstance();

        $config = $instance->getConfig();

        if (!empty($config['admin_group_model']) && class_exists($config['admin_group_model'])) {
            self::$adminGroupModel = new $config['admin_group_model'];
        } else {
            self::$adminGroupModel = new AdminGroup;
        }

        if (!empty($config['admin_group_title'])) {
            self::$adminGroupTitle = $config['admin_group_title'];
        }
    }

    public function getAdminGroupModel()
    {
        return self::$adminGroupModel;
    }

    public function getAdminGroupTitle()
    {
        return self::$adminGroupTitle;
    }

    public function getRoleNameAttr($value, $data)
    {
        $role = AdminRole::find($data['role_id']);
        return $role ? $role['name'] : '--';
    }

    public function getGroupNameAttr($value, $data)
    {
        $group = self::$adminGroupModel->find($data['group_id']);
        return $group ? $group['name'] : '--';
    }

    /**
     * Undocumented function
     *
     * @return $this
     */
    public static function current()
    {
        $admin_id = session('admin_id');

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
        $pwd = md5($pwd);
        $salt = substr($pwd, 7 + mt_rand(5, 10), 7);
        $pwd = md5($salt . $pwd . $salt);

        return [$pwd, $salt];
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
        $controller = Str::studly($controller);
        $user = static::find($admin_id);

        if (!$user) {
            return false;
        }

        if ($user['enable'] == 0) {
            session('admin_user', null);
            session('admin_id', null);
            return false;
        }

        unset($user['password'], $user['salt']);

        session('admin_user', $user);

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
        $url = str_replace('.html', '', $url);
        $url = str_replace('.', '/', $url);

        if (!Module::isInstalled()) {
            if (preg_match('/^\/admin\/extension\/\w+/i', $url)) {
                return true;
            }
        }
        $user = $user ? $user : session('admin_user');

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

        $url = implode('/', ['', $path[0], Str::snake($path[1]), strtolower($path[2])]);

        $noNeed = [
            '/admin/index/index', '/admin/index/captcha', '/admin/index/welcome', '/admin/index/denied',
            '/admin/index/logout', '/admin/index/login', '/admin/index/profile', '/admin/index/changepwd',
        ];

        if (in_array($url, $noNeed)) {
            return true;
        }

        $role = AdminRole::find($user['role_id']);

        if (!$role) {
            return false;
        }

        $prmission = AdminPermission::where(['url' => $url])->find();

        if (!$prmission && count($path) > 3) {
            $url = implode('/', ['', Str::snake($path[0] . '/' . $path[1]), $path[2], $path[3]]);
            $prmission = AdminPermission::where(['url' => $url])->find();
        }

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

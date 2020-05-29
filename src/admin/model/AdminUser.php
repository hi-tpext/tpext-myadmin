<?php

namespace tpext\myadmin\admin\model;

use think\Db;
use think\Model;
use tpext\builder\inface\Auth;
use tpext\myadmin\common\Module;

class AdminUser extends Model implements Auth
{
    protected $autoWriteTimestamp = 'dateTime';

    protected $adminGroupModel;

    protected $adminGroupTitle = '分组';

    protected function initialize()
    {
        parent::initialize();

        $instance = Module::getInstance();

        $config = $instance->getConfig();

        if (!empty($config['admin_group_model']) && class_exists($config['admin_group_model'])) {
            $this->adminGroupModel = new $config['admin_group_model'];
        } else {
            $this->adminGroupModel = new AdminGroup;
        }

        if (!empty($config['admin_group_title'])) {
            $this->adminGroupTitle = $config['admin_group_title'];
        }
    }

    public function getAdminGroupModel()
    {
        return $this->adminGroupModel;
    }

    public function getAdminGroupTitle()
    {
        return $this->adminGroupTitle;
    }

    public static function current()
    {
        $admin_id = session('admin_id');

        return static::get($admin_id);
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

    public function getRoleNameAttr($value, $data)
    {
        $role = AdminRole::get($data['role_id']);
        return $role ? $role['name'] : '--';
    }

    public function getGroupNameAttr($value, $data)
    {
        $group = $this->adminGroupModel->get($data['group_id']);
        return $group ? $group['name'] : '--';
    }

    public function checkPermission($admin_id, $controller, $action)
    {
        $user = static::get($admin_id);

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

    public static function checkUrl($url, $user = null)
    {
        if (!Module::isInstalled()) {
            if (preg_match('/^\/admin\/extension\/\w+$/i', $url)) {
                return true;
            }
        }
        $user = $user ? $user: session('admin_user');
        
        if (!$user) {
            return false;
        }

        $url = str_replace('.html', '', $url);

        if (in_array($url, ['/admin/index/index', '/admin/index/welcome', '/admin/index/denied', '/admin/index/logout', '/admin/index/login'])) {
            return true;
        }

        if ($user['role_id'] == 1) {
            return true;
        }

        $role = AdminRole::get($user['role_id']);

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

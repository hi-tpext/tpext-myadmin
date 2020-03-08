<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminUser extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

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
        $group = AdminGroup::get($data['group_id']);
        return $group ? $group['name'] : '--';
    }

    public function checkPermission($admin_id, $controller, $action)
    {
        $data = static::get($admin_id);

        if (!$data) {
            return false;
        }

        session('admin_user', $data);

        if ($data['role_id'] == 1) {
            return true;
        }

        $url = "/admin/$controller/$action";

        if (in_array($url, ['/admin/index/index', '/admin/index/denied', '/admin/index/logout', '/admin/index/login'])) {
            return true;
        }

        if ($data['enable'] == 0) {
            session('admin_user', null);
            session('admin_id', null);
            return false;
        }

        $role = AdminRole::get($data['role_id']);

        if (!$role) {
            return false;
        }

        $prmission = AdminPermission::where(['url' => $url])->find();

        if (!$prmission) {
            return false;
        }

        $rolePrmission = AdminRolePermission::where(['role_id' => $data['role_id'], 'permission_id' => $prmission['id']])->find();

        if (!$rolePrmission) {
            return false;
        }

        return true;
    }
}

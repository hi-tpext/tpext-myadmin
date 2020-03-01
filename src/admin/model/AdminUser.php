<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminUser extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

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
     * @return void
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
}

<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminRole extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    public function getUsersAttr($value, $data)
    {
        $count = AdminUser::where('role_id', $data['id'])->count();
        return $count ? $count : 0;
    }

    protected static function init()
    {
        self::afterDelete(function ($role) {
            AdminRolePermission::where(['role_id' => $role['id']])->delete();
        });
    }
}

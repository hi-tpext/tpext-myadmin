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
}

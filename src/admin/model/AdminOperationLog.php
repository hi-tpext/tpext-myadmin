<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminOperationLog extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    public function getNameAttr($value, $data)
    {
        $user = AdminUser::get($data['user_id']);

        return $user ? $user['name'] : '';
    }

    public function getUsernameAttr($value, $data)
    {
        $user = AdminUser::get($data['user_id']);

        return $user ? $user['username'] : '';
    }
}

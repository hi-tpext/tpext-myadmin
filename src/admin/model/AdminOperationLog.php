<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminOperationLog extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    protected $user;

    public function getNameAttr($value, $data)
    {
        $user = $this->getUser($data);

        return $user ? $user['name'] : '';
    }

    public function getUsernameAttr($value, $data)
    {
        $user = $this->getUser($data);

        return $user ? $user['username'] : '';
    }

    private function getUser($data)
    {
        if ($this->user) {
            return $this->user;
        }

        $user = AdminUser::get($data['user_id']);
        return $user;
    }
}

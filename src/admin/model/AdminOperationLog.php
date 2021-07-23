<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminOperationLog extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    public function admin()
    {
        return $this->hasOne('AdminUser', 'id', 'user_id');
    }
}

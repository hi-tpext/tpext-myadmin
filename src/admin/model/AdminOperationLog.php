<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminOperationLog extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    public function admin()
    {
        return $this->hasOne('AdminUser', 'id', 'user_id');
    }
}

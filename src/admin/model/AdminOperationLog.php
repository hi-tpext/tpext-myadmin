<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminOperationLog extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'user_id', 'id');
    }

    public function action()
    {
        return $this->belongsTo(AdminPermission::class, 'path', 'url');
    }
}

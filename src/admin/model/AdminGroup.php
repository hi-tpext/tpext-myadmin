<?php

namespace tpext\myadmin\admin\model;

use think\Model;
use tpext\builder\traits\TreeModel;

class AdminGroup extends Model
{
    use TreeModel;
    protected $autoWriteTimestamp = 'dateTime';

    protected static function init()
    {
        self::afterDelete(function ($group) {
            static::where(['parent_id' => $group['id']])->update(['parent_id' => $group['parent_id']]);
        });
    }

    protected function treeInit()
    {
        $this->treeTextField = 'name';
        $this->treeSortField = 'sort';
    }

    public function getUsersAttr($value, $data)
    {
        $count = AdminUser::where('group_id', $data['id'])->count();
        return $count ? $count : 0;
    }
}

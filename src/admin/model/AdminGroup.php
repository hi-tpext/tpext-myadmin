<?php

namespace tpext\myadmin\admin\model;

use think\Model;
use tpext\builder\traits\TreeModel;

class AdminGroup extends Model
{
    use TreeModel;

    protected $autoWriteTimestamp = 'datetime';

    public static function onAfterDelete($group)
    {
		static::where(['parent_id' => $group['id']])->update(['parent_id' => $group['parent_id']]);
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

    public function buildList($parent = 0, $deep = 0)
    {
        $roots = static::where(['parent_id' => $parent])->order('sort')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            $root['title_show'] = str_repeat('&nbsp;', ($deep - 1) * 6) . '├─' . $root['name'];

            $root['title_show'];

            $data[] = $root;

            $data = array_merge($data, $this->buildList($root['id'], $deep));
        }

        return $data;
    }

    public function buildTree($parent = 0, $deep = 0, $except = 0)
    {
        $roots = static::where(['parent_id' => $parent])->order('sort')->field('id,name,parent_id')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            $root['title_show'] = '|' . str_repeat('──', $deep) . $root['name'];

            if ($root['id'] == $except) {
                continue;
            }

            $data[$root['id']] = $root['title_show'];

            $data += $this->buildTree($root['id'], $deep, $except);
        }

        return $data;
    }
}

<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminGroup extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    protected static function init()
    {
        self::afterDelete(function ($group) {
            static::where(['parent_id' => $group['id']])->update(['parent_id' => $group['parent_id']]);
        });
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

            if ($deep == 1) {
                $root['title_show'] = '├─' . $root['name'];
            } else if ($deep == 2) {
                $root['title_show'] = str_repeat('&nbsp;', 8) . '├─' . $root['name'];
            } else if ($deep == 3) {
                $root['title_show'] = str_repeat('&nbsp;', 16) . '├─' . $root['name'];
            } else if ($deep == 4) {
                $root['title_show'] = str_repeat('&nbsp;', 24) . '├─' . $root['name'];
            } else if ($deep == 5) {
                $root['title_show'] = str_repeat('&nbsp;', 32) . '├─' . $root['name'];
            } else if ($deep == 5) {
                $root['title_show'] = str_repeat('&nbsp;', 40) . '├─' . $root['name'];
            }

            $root['title_show'];

            $data[] = $root;

            $data = array_merge($data, $this->buildList($root->id, $deep));
        }

        return $data;
    }

    public function buildTree($parent = 0, $deep = 0, $except = 0)
    {
        $roots = static::where(['parent_id' => $parent])->order('sort')->field('id,name,parent_id')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            if ($deep == 1) {
                $root['title_show'] = '├─' . $root['name'];
            } else if ($deep == 2) {
                $root['title_show'] = '──├─' . $root['name'];
            } else if ($deep == 3) {
                $root['title_show'] = '────├─' . $root['name'];
            } else if ($deep == 4) {
                $root['title_show'] = '──────├─' . $root['name'];
            } else if ($deep == 5) {
                $root['title_show'] = '────────├─' . $root['name'];
            } else if ($deep == 5) {
                $root['title_show'] = '──────────├─' . $root['name'];
            }

            if ($root['id'] == $except) {
                continue;
            }

            $root['title_show'];

            $data[$root['id']] = $root['title_show'];

            $data += $this->buildTree($root->id, $deep);
        }
        return $data;
    }
}

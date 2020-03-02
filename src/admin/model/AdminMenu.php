<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminMenu extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    public function buildList($parent = 0, $deep = 0)
    {
        $roots = static::where('parent_id', $parent)->order('sort')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            if ($deep == 1) {
                $root['title_show'] = '├─' . $root['title'];
            } else if ($deep == 2) {
                $root['title_show'] = str_repeat('&nbsp;', 8) . '├─' . $root['title'];
            } else if ($deep == 3) {
                $root['title_show'] = str_repeat('&nbsp;', 16) . '├─' . $root['title'];
            } else if ($deep == 4) {
                $root['title_show'] = str_repeat('&nbsp;', 24) . '├─' . $root['title'];
            } else if ($deep == 5) {
                $root['title_show'] = str_repeat('&nbsp;', 32) . '├─' . $root['title'];
            } else if ($deep == 5) {
                $root['title_show'] = str_repeat('&nbsp;', 40) . '├─' . $root['title'];
            }

            $root['title_show'];
            $root['icon_show'] = '<i class="mdi ' . $root['icon'] . '"></i>';

            $data[] = $root;

            $data = array_merge($data, $this->buildList($root->id, $deep));
        }

        return $data;
    }

    public function buildTree($parent = 0, $deep = 0, $except = 0)
    {
        $roots = static::where('parent_id', $parent)->order('sort')->field('id,title,parent_id')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            if ($deep == 1) {
                $root['title_show'] = '├─' . $root['title'];
            } else if ($deep == 2) {
                $root['title_show'] = '──├─' . $root['title'];
            } else if ($deep == 3) {
                $root['title_show'] = '────├─' . $root['title'];
            } else if ($deep == 4) {
                $root['title_show'] = '──────├─' . $root['title'];
            } else if ($deep == 5) {
                $root['title_show'] = '────────├─' . $root['title'];
            } else if ($deep == 5) {
                $root['title_show'] = '──────────├─' . $root['title'];
            }

            if ($root['id'] == $except) {
                continue;
            }

            $root['title_show'];

            $data[$root['id']] = $root['title_show'];

            $data = array_merge($data, $this->buildTree($root->id, $deep));
        }

        return $data;
    }
}

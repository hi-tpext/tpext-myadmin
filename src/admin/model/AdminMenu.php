<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminMenu extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    protected static function init()
    {
        self::afterDelete(function ($menu) {
            static::where(['parent_id' => $menu['id']])->update(['parent_id' => $menu['parent_id']]);
        });
    }

    public function buildList($parent = 0, $deep = 0)
    {
        $roots = static::where(['parent_id' => $parent])->order('sort')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            $root['title_show'] = str_repeat('&nbsp;', ($deep - 1) * 6) . '├─' . $root['title'];

            $data[] = $root;

            $data = array_merge($data, $this->buildList($root['id'], $deep));
        }

        return $data;
    }

    public function buildTree($parent = 0, $deep = 0, $except = 0)
    {
        $roots = static::where(['parent_id' => $parent])->order('sort')->field('id,title,parent_id,url')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            $root['title_show'] = '|' . str_repeat('──', $deep) . $root['title'];

            if ($root['id'] == $except) {
                continue;
            }

            if ($root['url'] != '#') {
                continue;
            }

            $root['title_show'];

            $data[$root['id']] = $root['title_show'];

            $data += $this->buildTree($root['id'], $deep, $except);
        }

        return $data;
    }

    public function buildMenus($admin_user)
    {
        $roleMenus = AdminRoleMenu::where(['role_id' => $admin_user['role_id']])->column('menu_id');
        $roots = static::where(['parent_id' => 0, 'enable' => 1])->select();
        $list = [];

        foreach ($roots as $root) {
            if ($root['url'] == '#' && !in_array($root['id'], $roleMenus)) {
                continue;
            }

            $list = array_merge($list, $this->getChildren($root, $admin_user['role_id']));
        }
        $menus = [];
        foreach ($list as $li) {
            $menus[] = [
                'id' => $li['id'],
                'name' => $li['title'],
                'url' => $li['url'],
                'pid' => $li['parent_id'],
                'icon' => 'mdi ' . $li['icon'],
                'is_out' => 0,
                'is_home' => $li['id'] == 1 ? 1 : 0,
            ];
        }

        return $menus;
    }

    private function getChildren($root, $role_id)
    {
        if ($root['url'] == '#') {
            $data = [];

            $data[] = $root;
            $children = static::where(['parent_id' => $root['id'], 'enable' => 1])->order('sort')->select();
            foreach ($children as $child) {
                $data = array_merge($data, $this->getChildren($child, $role_id));
            }
            if (count($data) > 1) {
                return $data;
            }

            return [];
        } else {

            $prmission = AdminPermission::where(['url' => $root['url']])->find();

            if (!$prmission) {
                return [];
            }

            $rolePrmission = AdminRolePermission::where(['role_id' => $role_id, 'permission_id' => $prmission['id']])->find();

            if (!$rolePrmission) {
                return [];
            }

            return [$root];
        }
    }
}

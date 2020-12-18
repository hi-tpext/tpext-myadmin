<?php

namespace tpext\myadmin\admin\model;

use think\Model;

class AdminMenu extends Model
{
    protected $autoWriteTimestamp = 'datetime';

    public static function onAfterDelete($menu)
    {
		static::where(['parent_id' => $menu['id']])->update(['parent_id' => $menu['parent_id']]);
    }

    public function buildList($parent = 0, $deep = 0)
    {
        $roots = static::where(['parent_id' => $parent])->order('sort')->select();
        $data = [];

        $deep += 1;

        foreach ($roots as $root) {

            if ($parent == 0) {
                if ($root['url'] != '#') {
                    $root['title_show'] = str_repeat('&nbsp;', 1 * 6) . '├─' . $root['title'];
                } else {
                    $root['title_show'] = $root['title'];
                }
            } else {
                $root['title_show'] = str_repeat('&nbsp;', ($deep - 1) * 6) . '├─' . $root['title'];
            }

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
        $roots = [];

        $allData = AdminMenu::where(['enable' => 1])->select();
        $allPermissions = AdminPermission::select();
        $allRolePermissions = AdminRolePermission::where(['role_id' => $admin_user['role_id']])->select();

        foreach ($allData as $d) {

            if ($d['parent_id'] != 0 || $d['url'] == '#' && !in_array($d['id'], $roleMenus)) {
                continue;
            }

            $roots[] = $d;
        }

        $list = [];

        foreach ($roots as $root) {
            if ($root['url'] == '#' && !in_array($root['id'], $roleMenus)) {
                continue;
            }

            $list = array_merge($list, $this->getChildren($allData, $allPermissions, $allRolePermissions, $root, $admin_user['role_id']));
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

    private function getChildren($allData, $allPermissions, $allRolePermissions, $menu, $role_id)
    {
        if ($menu['url'] == '#') {
            $data = [];

            $data[] = $menu;

            foreach ($allData as $d) {

                if ($d['parent_id'] == $menu['id']) {
                    $data = array_merge($data, $this->getChildren($allData, $allPermissions, $allRolePermissions, $d, $role_id));
                }
            }

            if (count($data) > 1) {
                return $data;
            }

            return [];
        } else {

            $prmission = null;

            foreach ($allPermissions as $pm) {
                if ($pm['url'] == $menu['url']) {
                    $prmission = $pm;
                    break;
                }
            }

            if (!$prmission) {
                return [];
            }

            $rolePrmission = null;

            foreach ($allRolePermissions as $rpm) {
                if ($rpm['permission_id'] == $prmission['id']) {
                    $rolePrmission = $rpm;
                    break;
                }
            }

            if (!$rolePrmission) {
                return [];
            }

            return [$menu];
        }
    }
}

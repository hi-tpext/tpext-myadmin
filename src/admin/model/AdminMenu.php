<?php

namespace tpext\myadmin\admin\model;

use think\Model;
use tpext\builder\traits\TreeModel;

class AdminMenu extends Model
{
    use TreeModel;

    protected $autoWriteTimestamp = 'dateTime';

    protected static function init()
    {
        self::afterDelete(function ($menu) {
            static::where(['parent_id' => $menu['id']])->update(['parent_id' => $menu['parent_id']]);
        });
    }

    protected function treeInit()
    {
        $this->treeTextField = 'title';
        $this->treeSortField = 'sort';
    }

    public function buildMenus($admin_user)
    {
        $roleMenus = AdminRoleMenu::where(['role_id' => $admin_user['role_id']])->column('menu_id');
        $roots = [];

        $allData = AdminMenu::where(['enable' => 1])->order('parent_id,sort')->select();
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

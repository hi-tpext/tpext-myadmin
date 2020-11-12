<?php
namespace tpext\myadmin\common\behavior;

use think\Db;
use tpext\myadmin\admin\model\AdminMenu;

class Menu
{
    public function run($data = [])
    {
        $tableName = config('database.prefix') . 'admin_menu';

        $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

        if (empty($isTable)) {
            return false;
        }

        $action = $data[0];
        $module = $data[1];
        $menus = $data[2];

        Db::startTrans();

        if ($action == 'create') {

            foreach ($menus as $menu) {
                $this->createMenu($menu, $module);
            }

        } else if ($action == 'delete') {

            foreach ($menus as $menu) {
                $this->deleteMenu($module);
            }

        } else if ($action == 'enable') {

            foreach ($menus as $menu) {
                $this->enableMenu($module, 1);
            }

        } else if ($action == 'disable') {

            foreach ($menus as $menu) {
                $this->enableMenu($module, 0);
            }
        }

        Db::commit();
    }

    private function createMenu($menu, $module = '', $parent_id = 0)
    {
        if ($parent_id == 0) {
            $menu['sort'] = AdminMenu::where(['parent_id' => 0])->max('sort') + 5;
        }

        $data = [
            'parent_id' => $parent_id,
            'sort' => isset($menu['sort']) ? $menu['sort'] : 1,
            'title' => $menu['title'],
            'url' => isset($menu['children']) && count($menu['children']) ? '#' : $menu['url'],
            'icon' => $menu['icon'],
            'module' => $module,
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $id = Db::name('admin_menu')->insertGetId($data);

        if ($id && isset($menu['children'])) {

            foreach ($menu['children'] as $sub_menu) {
                $this->createMenu($sub_menu, $module, $id);
            }
        }
    }

    private function deleteMenu($module = '')
    {
        if (empty($module)) {
            return;
        }

        AdminMenu::where(['module' => $module])->delete();
    }

    private function enableMenu($module = '', $enable)
    {
        if (empty($module)) {
            return;
        }

        AdminMenu::where(['module' => $module])->update(['enable' => $enable]);
    }
}

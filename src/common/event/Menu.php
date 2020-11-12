<?php
namespace tpext\myadmin\common\event;

use think\facade\Db;
use tpext\myadmin\admin\model\AdminMenu;

class Menu
{
    public function handle($data)
    {
        $type = Db::getConfig('default', 'mysql');

        $connections = Db::getConfig('connections');

        $config = $connections[$type] ?? [];

        if (empty($config) || empty($config['database'])) {
            return false;
        }

        $tableName = $config['prefix'] . 'admin_menu';

        $isTable = Db::query("SHOW TABLES LIKE '{$tableName}'");

        if (empty($isTable)) {
            cache('tpextmyadmin_installed', 0);
            return false;
        }

        $action = $data[0];
        $menus = $data[1];
        $parent_id = isset($data[2]) ? $data[2] : 0;

        Db::startTrans();

        if ($action == 'create') {

            foreach ($menus as $menu) {
                $this->createMenu($menu, $parent_id);
            }

        } else if ($action == 'delete') {

            foreach ($menus as $menu) {
                $this->deleteMenu($menu);
            }

        } else if ($action == 'enable') {

            foreach ($menus as $menu) {
                $this->enableMenu($menu, 1);
            }

        } else if ($action == 'disable') {

            foreach ($menus as $menu) {
                $this->enableMenu($menu, 0);
            }
        }

        Db::commit();

        return true;
    }

    private function createMenu($menu, $parent_id = 0)
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
            'module' => isset($menu['module']) ? $menu['module'] : '',
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $id = Db::name('admin_menu')->insertGetId($data);

        if ($id && isset($menu['children'])) {

            foreach ($menu['children'] as $sub_menu) {
                $sub_menu['module'] = isset($menu['module']) ? $menu['module'] : '';
                $this->createMenu($sub_menu, $id);
            }
        }
    }

    private function deleteMenu($menu)
    {
        if ($menu['url'] != '#') {

            AdminMenu::where(['url' => $menu['url']])->delete();
        } else if (isset($menu['module']) && !empty($menu['module'])) {

            AdminMenu::where(['url' => '#', 'module' => $menu['module']])->delete();
        }

        if (isset($menu['children'])) {

            foreach ($menu['children'] as $sub_menu) {
                $this->deleteMenu($sub_menu);
            }
        }
    }

    private function enableMenu($menu, $enable)
    {
        $m = AdminMenu::where(['url' => $menu['url'], 'module' => $menu['module']])->find();

        if ($m && $m['parent_id']) {
            AdminMenu::where(['id' => $m['parent_id']])->update(['enable' => $enable]);
        }

        if (isset($menu['children'])) {

            foreach ($menu['children'] as $sub_menu) {
                $this->enableMenu($sub_menu, $enable);
            }
        }
    }
}

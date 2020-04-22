<?php
namespace tpext\myadmin\common\behavior;

use think\Db;
use tpext\myadmin\admin\model\AdminMenu;

class Menu
{
    public function run($data = [])
    {
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
        }

        Db::commit();
    }

    private function createMenu($menu, $parent_id = 0)
    {
        $data = [
            'parent_id' => $parent_id,
            'sort' => isset($menu['sort']) ? $menu['sort'] : 99,
            'title' => $menu['title'],
            'url' => isset($menu['children']) && count($menu['children']) ? '#' : $menu['url'],
            'icon' => $menu['icon'],
            'create_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        $id = Db::name('admin_menu')->insertGetId($data);

        if ($id && isset($menu['children'])) {

            foreach ($menu['children'] as $sub_menu) {
                $this->createMenu($sub_menu, $id);
            }
        }
    }

    private function deleteMenu($menu)
    {
        if ($menu['url'] != '#') {
            AdminMenu::where(['url' => $menu['url']])->delete();
        }

        if (isset($menu['children'])) {

            foreach ($menu['children'] as $sub_menu) {
                $this->deleteMenu($sub_menu);
            }
        }
    }
}

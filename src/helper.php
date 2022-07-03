<?php

use tpext\common\ExtLoader;
use tpext\myadmin\common\event\Log;
use tpext\myadmin\common\event\Menu;
use tpext\myadmin\common\event\Assets;

$classMap = [
    'tpext\\myadmin\\common\\Module',
];

ExtLoader::addClassMap($classMap);

ExtLoader::watch('tpext_menus', Menu::class, false, '接收菜单创建/删除事件');
ExtLoader::watch('tpext_copy_assets', Assets::class, false, '监视资源刷新，修改版本号');
ExtLoader::watch('admin_log', Log::class, false, '记录日志');

if (!function_exists('checkUrl')) {
    function checkUrl($url)
    {
        return \tpext\myadmin\admin\model\AdminUser::checkUrl($url);
    }
}
<?php
use tpext\lightyearadmin\common\Plugin;

$assetsDir = Plugin::getInstance()->assetsDirName();

return [
    'position' => '首页',
    'page_title' => 'Tpext后台管理系统',
    'desc' => 'Tpext后台管理系统',
    'logo' => '<img src="/assets/' . $assetsDir . '/images/logo.png" alt="Admin logo" title="Tpext后台管理系统">',
    'favicon' => '/assets/' . $assetsDir . '/favicon.ico',
    'copyright' => 'Copyright &copy; 2020. <a target="_blank" href="#">Tpext后台管理系统</a> All rights reserved.',
    'minify' => false,
];

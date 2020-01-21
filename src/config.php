<?php
use tpext\lyatadmin\common\Module;

$assetsDir = Module::getInstance()->assetsDir();

return [
    'page_title' => 'Tpext后台管理系统',
    'desc' => 'Tpext后台管理系统',
    'logo' => '<img src="/assets/' . $assetsDir . '/images/logo.png" alt="Admin logo" title="Tpext后台管理系统">',
    'favicon' => '/assets/' . $assetsDir . '/favicon.ico',
    'copyright' => 'Copyright &copy; 2020. <a target="_blank" href="#">Tpext后台管理系统</a> All rights reserved.',
];

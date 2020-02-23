<?php
use tpext\lightyearadmin\common\Module;

$assetsDir = Module::getInstance()->assetsDirName();

return [
    'name' => 'Tpext后台管理系统',
    'description' => 'Tpext后台管理系统',
    'logo' => '<img src="/assets/' . $assetsDir . '/images/logo.png" alt="Admin logo" title="Tpext后台管理系统">',
    'favicon' => '/assets/' . $assetsDir . '/favicon.ico',
    'copyright' => 'Copyright &copy; 2020. <a target="_blank" href="#">Tpext后台管理系统</a> All rights reserved.',
    'minify' => 0,
    //配置描述
    '__config__' => [
        'name' => ['type' => 'text', 'label' => '名称'],
        'description' => ['type' => 'textarea', 'label' => '描述'],
        'logo' => ['type' => 'text', 'label' => 'Logo'],
        'favicon' => ['type' => 'text', 'label' => 'Favicon'],
        'copyright' => ['type' => 'textarea', 'label' => '版权'],
        'minify' => ['type' => 'radio', 'label' => '', 'options' => [0 => '是', 1 => '否']],
    ],
];

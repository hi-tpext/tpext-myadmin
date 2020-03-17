<?php

return [
    'name' => 'Tpext后台管理系统',
    'description' => 'Tpext后台管理系统',
    'logo' => '<img src="/assets/lightyearadmin/images/logo.png" alt="Admin logo" title="Tpext后台管理系统">',
    'favicon' => '/assets/lightyearadmin/favicon.ico',
    'copyright' => 'Copyright &copy; 2020. <a target="_blank" href="#">Tpext后台管理系统</a> All rights reserved.',
    'login_logo' => '/assets/lightyearadmin/images/logo-ico.png',
    'login_background_img' => '',
    'login_timeout' => 20,
    'assets_ver' => '1.0',
    'minify' => 0,
    //配置描述
    '__config__' => [
        'name' => ['type' => 'text', 'label' => '名称'],
        'description' => ['type' => 'textarea', 'label' => '描述'],
        'logo' => ['type' => 'text', 'label' => '左上角Logo'],
        'favicon' => ['type' => 'text', 'label' => 'Favicon图片'],
        'copyright' => ['type' => 'textarea', 'label' => '版权'],
        'login_logo' => ['type' => 'text', 'label' => '登录页面Logo'],
        'login_background_img' => ['type' => 'text', 'label' => '登录页面背景图片'],
        'login_timeout' => ['type' => 'number', 'label' => '登录超时(分钟)', 'help' => '后台用户在一段时间没有操作后自动注销'],
        'assets_ver' => ['type' => 'text', 'label' => '静态资源版本号', 'size' => [2, 4]],
        'minify' => ['type' => 'radio', 'label' => '资源压缩', 'options' => [0 => '否', 1 => '是'], 'help' => '压缩css、js资源'],
    ],
];

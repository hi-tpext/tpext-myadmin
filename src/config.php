<?php

return [
    'name' => 'Tpext后台管理系统',
    'description' => 'Tpext后台管理系统',
    'copyright' => 'Copyright &copy; 2020. <a target="_blank" href="#">Tpext后台管理系统</a> All rights reserved.',
    '__hr__1' => '注意，涉及到`/assets/`目录中的图片等资源文件，不要直接上传文件替换，请在根目录`public/static/`中创建目录然后上传，然后修改链接',
    'logo' => '<img src="/assets/lightyearadmin/images/logo.png" alt="Admin logo" title="Tpext后台管理系统">',
    'favicon' => '/assets/lightyearadmin/favicon.ico',
    '__br__1' => '',
    'login_logo' => '/assets/lightyearadmin/images/logo-ico.png',
    'login_background_img' => '/assets/lightyearadmin/images/login-bg.jpg',
    '__br__2' => '',
    'login_in_top' => 0,
    'login_timeout' => 20,
    '__br__3' => '',
    'login_session_key' => 0,
    'login_css_file' => '',
    '__br__4' => '',
    'assets_ver' => '1.0',
    'minify' => 0,
    '__br__5' => '',
    'admin_group_title' => '分组',
    'admin_group_model' => '',
    'operation_log_catch' => ['POST', 'PUT', 'PATCH', 'DELETE'],
    'login_page_style' => '1',
    'login_page_view_path' => '',
    //配置描述
    '__config__' => [
        'name' => ['type' => 'text', 'label' => '名称'],
        'description' => ['type' => 'textarea', 'label' => '描述'],
        'copyright' => ['type' => 'textarea', 'label' => '版权'],
        'logo' => ['type' => 'textarea', 'label' => '左上角Logo', 'col_size' => 6, 'size' => [4, 8]],
        'favicon' => ['type' => 'image', 'label' => 'Favicon图片', 'col_size' => 6, 'size' => [4, 8]],
        'login_logo' => ['type' => 'image', 'label' => '登录页面Logo', 'col_size' => 6, 'size' => [4, 8]],
        'login_background_img' => ['type' => 'image', 'label' => '登录页面背景图片', 'col_size' => 6, 'size' => [4, 8]],
        'login_in_top' => ['type' => 'radio', 'label' => '登录超时整体跳转', 'options' => [0 => '否', 1 => '是'], 'col_size' => 6, 'size' => [4, 8], 'help' => '若为是，登录超时后整体页面跳转到登录，反之则仅触发超时的页码跳转。'],
        'login_timeout' => ['type' => 'number', 'label' => '登录超时(分钟)', 'help' => '后台用户在一段时间没有操作后自动注销', 'col_size' => 6, 'size' => [4, 8]],
        'login_session_key' => ['type' => 'radio', 'label' => '隐藏登录页面', 'options' => [0 => '否', 1 => '是'], 'col_size' => 6, 'size' => [4, 8], 'help' => '若为是，登录页面将检查session("login_session_key")值，没有设置则拒绝登录。'],
        'login_css_file' => ['type' => 'text', 'label' => '登录页面css', 'help' => '可以填写一个css文件路径。如`/static/admin/login.css`', 'col_size' => 6, 'size' => [4, 8]],
        'assets_ver' => ['type' => 'text', 'label' => '静态资源版本号', 'col_size' => 6, 'size' => [4, 8]],
        'minify' => ['type' => 'radio', 'label' => '资源压缩', 'options' => [0 => '否', 1 => '是'], 'help' => '压缩css、js资源', 'col_size' => 6, 'size' => [4, 8]],
        'admin_group_title' => ['type' => 'text', 'label' => '管理员分组名称', 'help' => '如:`部门，分店`', 'col_size' => 6, 'size' => [4, 8]],
        'admin_group_model' => ['type' => 'text', 'label' => '管理员分组模型', 'help' => '如:`\tpext\myadmin\admin\model\AdminGroup`，你可以自己实现分组。树形结构需要配合\tpext\builder\traits\TreeModel', 'col_size' => 6, 'size' => [4, 8]],
        'operation_log_catch' => ['type' => 'checkbox', 'label' => '操作日志记录类型', 'col_size' => 6, 'size' => [4, 8], 'options' => ['GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE']],
        'login_page_style' => ['type' => 'radio', 'label' => '登录页面风格', 'col_size' => 6, 'size' => [4, 8], 'options' => ['1' => '风格1', '2' => '风格2', '3' => '风格3', '4' => '风格4']],
        'login_page_view_path' => ['type' => 'text', 'label' => '自定义登录页面', 'col_size' => 6, 'size' => [4, 8],'help' => '设置后不再使用以上４种风格。自定登录模板路径，如`application/admin/view/login.html`。仍然发送`post`请求到`/admin/index/login`'],
    ],
];

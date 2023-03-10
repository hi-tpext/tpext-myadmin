<?php

use tpext\myadmin\common\Module;
use tpext\builder\common\Form;

return [
    'name' => 'Tpext后台管理系统',
    'description' => 'Tpext后台管理系统',
    'copyright' => 'Copyright &copy; ' . date('Y') . '. <a target="_blank" href="#">Tpext后台管理系统</a> All rights reserved.',
    'logo' => '<img src="/assets/lightyearadmin/images/logo.png" alt="Admin logo" title="Tpext后台管理系统">',
    'favicon' => '/assets/lightyearadmin/favicon.ico',
    'login_logo' => '/assets/lightyearadmin/images/logo-ico.png',
    'login_background_img' => '/assets/lightyearadmin/images/login-bg.jpg',
    'login_in_top' => 0,
    'login_timeout' => 20,
    'login_session_key' => 0,
    'login_css_file' => '',
    'assets_ver' => '1.0',
    'minify' => 0,
    'admin_group_title' => '分组',
    'admin_group_model' => '',
    'operation_log_catch' => ['POST', 'PUT', 'PATCH', 'DELETE'],
    'operation_log_fields_except' => '*:content',
    'index_page_style' => '',
    'login_page_style' => '1',
    'login_page_view_path' => '',
    'index_top_menu' => 1,
    //配置描述
    '__config__' => function (Form $form) {

        $form->defaultDisplayerSize(12, 12);

        $form->left(4)->with(function () use ($form) {
            $form->radio('login_in_top', '登录超时整体跳转')->options([0 => '否', 1 => '是'])->help('若为是，登录超时后整体页面跳转到登录，反之则仅触发超时的页码跳转。');
            $form->number('login_timeout', '登录超时(分钟)')->help('后台用户在一段时间没有操作后自动注销(需要在config/session.php配置中[修改/添加]`expire`(秒)参数，使session超时长于本配置)');
            $form->radio('login_session_key', '隐藏登录页面')->options([0 => '否', 1 => '是'])->help('若为是，登录页面将检查session("login_session_key")值，没有设置则拒绝登录。');
            $form->text('assets_ver', '静态资源版本号');
            $form->radio('minify', '资源压缩')->options([0 => '否', 1 => '是'])->help('压缩css、js资源');
            $form->text('admin_group_title', '管理员分组名称')->help('如:`部门，分店');
            $form->text('admin_group_model', '管理员分组模型')->help('如:`\tpext\myadmin\admin\model\AdminGroup`，你可以自己实现分组。树形结构需要配合\tpext\builder\traits\TreeModel');
            $form->checkbox('operation_log_catch', '操作日志记录类型')->options(['GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE']);
            $form->textarea('operation_log_fields_except', '记录日志排除字段')->help("避免记录一些内容很长但意义不大的字段到操作日志里面，如文章内容、产品详情等。规则`path:fields`,path可用`*`代表所有，多个规则用换行分割。例如:<pre>*:content\nadmin/shopgoods/edit:description</pre>");
            $form->select('index_page_style', 'index主体页面风格')->options(Module::getInstance()->getIndexViews());
            $form->select('login_page_style', '登录页面风格')->options(Module::getInstance()->getLoginViews());
        });

        $form->right(8)->with(function () use ($form) {
            $form->text('name', '名称');
            $form->textarea('description', '描述')->rows(2);
            $form->textarea('logo', '左上角Logo')->rows(2);
            $form->radio('index_top_menu', '顶部菜单')->options([0 => '否', 1 => '是'])->help('菜单第一层放在页面顶部，点击切换左侧菜单');
            $form->textarea('copyright', '底部版权')->rows(2);
            $form->image('favicon', 'Favicon图片');
            $form->image('login_logo', '登录页面Logo');
            $form->image('login_background_img', '登录页面背景图片');
            $form->text('login_page_view_path', '自定义登录页面')->help('设置后不再使用下拉选项的风格。自定登录模板路径，如`app/admin/view/login.html`。仍然发送`post`请求到`/admin/index/login`');
            $form->text('login_css_file', '登录页面css')->help('如果想调整登录页面样式，可以填写一个css文件路径，如`/static/admin/login.css');
        });
    },
];

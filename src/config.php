<?php

use tpext\myadmin\common\Module;
use tpext\builder\common\Form;
use tpext\myadmin\common\RouteMaker;

Module::getInstance()->loadLang('extension');

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
    'admin_group_title' => __admin_lang('group_name'),
    'operation_log_catch' => ['POST', 'PUT', 'PATCH', 'DELETE'],
    'operation_log_fields_except' => '*:content',
    'index_page_style' => '',
    'login_page_style' => '0',
    'login_page_view_path' => '',
    'index_top_menu' => 1,
    //配置描述
    '__config__' => function (Form $form, $data) {

        $form->defaultDisplayerSize(12, 12);

        $form->left(4)->with(function () use ($form, $data) {
            $form->radio('login_in_top')->options([0 => __admin_lang('no'), 1 => __admin_lang('yes')])->help(__admin_lang('login_in_top_help'));
            $form->number('login_timeout')->help(__admin_lang('login_timeout_help'));
            $form->radio('login_session_key')->options([0 => __admin_lang('no'), 1 => __admin_lang('yes')])->help(__admin_lang('hide_login_help'));
            if ($data['login_session_key'] == 1) {
                RouteMaker::make();
                $url = RouteMaker::getEntrance();
                $form->raw('route_tips')->value($url . __admin_lang('admin_entrance_tips'));
            }
            $form->text('assets_ver');
            $form->text('admin_group_title')->help(__admin_lang('admin_group_title_help'));
            $form->checkbox('operation_log_catch')->options(['GET' => 'GET', 'POST' => 'POST', 'PUT' => 'PUT', 'PATCH' => 'PATCH', 'DELETE' => 'DELETE']);
            $form->textarea('operation_log_fields_except')->help(__admin_lang('operation_log_fields_except_help') . "<pre>*:content\nadmin/shopgoods/edit:description</pre>");
            $form->select('index_page_style')->options(Module::getInstance()->getIndexViews());
            $form->select('login_page_style')->options(Module::getInstance()->getLoginViews());
        });

        $form->right(8)->with(function () use ($form) {
            $form->text('name');
            $form->textarea('description')->rows(2);
            $form->textarea('logo')->rows(2);
            $form->radio('index_top_menu')->options([0 => __admin_lang('no'), 1 => __admin_lang('yes')])->help(__admin_lang('top_menu_help'));
            $form->textarea('copyright')->rows(2);
            $form->image('favicon');
            $form->image('login_logo');
            $form->image('login_background_img');
            $form->text('login_page_view_path')->help(__admin_lang('custom_login_page_help'));
            $form->text('login_css_file')->help(__admin_lang('login_css_file_help'));
        });
    }
];

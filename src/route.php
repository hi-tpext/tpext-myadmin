<?php

use think\facade\Route;
use tpext\myadmin\common\Entrance;
use think\middleware\SessionInit;
use think\facade\App;

if (config('session.type') == 'file' && empty(config('session.path'))) {
    App::setRuntimePath(App::getRootPath() . 'runtime/admin' . DIRECTORY_SEPARATOR); //解决session驱动为file时存储路径不一致的问题
}

// 后台隐藏登录页面的中转地址
$entrance = '/__entrance__';
//入口为：http://yourdomain/__entrance__
Route::get($entrance, Entrance::class . '@run')->append(['entrance' => $entrance])->middleware(SessionInit::class);
Route::get($entrance . '/', Entrance::class . '@run')->append(['entrance' => $entrance])->middleware(SessionInit::class);
<?php

use Webman\Route;
use tpext\myadmin\common\Entrance;
use tpext\myadmin\webman\MergeParameters;

// 后台隐藏登录页面的中转地址
$entrance = '/__entrance__';
//入口为：http://yourdomain/__entrance__
Route::get($entrance, Entrance::class . '@run')->setParams(['entrance' => $entrance])->middleware(MergeParameters::class);
Route::get($entrance . '/', Entrance::class . '@run')->setParams(['entrance' => $entrance])->middleware(MergeParameters::class);

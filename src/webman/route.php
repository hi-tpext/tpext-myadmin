<?php

use Webman\Route;

Route::group('/admin', function () {
    Route::any('', [tpext\myadmin\admin\controller\Index::class, 'index']);
    Route::any('/', [tpext\myadmin\admin\controller\Index::class, 'index']);
    Route::any('/index', [tpext\myadmin\admin\controller\Index::class, 'index']);
    Route::any('/index/index', [tpext\myadmin\admin\controller\Index::class, 'index']);
});

<?php

namespace tpext\myadmin\common;

use think\Service as BaseService;
use tpext\myadmin\common\middleware\Auth;

/**
 * for tp6
 */
class Service extends BaseService
{
    public function boot()
    {
        $this->app->event->listen('HttpRun', function () {
            $this->app->middleware->add(Auth::class, 'route');
        });
    }
}

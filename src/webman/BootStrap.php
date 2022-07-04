<?php

namespace tpext\myadmin\webman;

use tpext\myadmin\common\Module;
use Webman\Route;

class BootStrap implements \Webman\Bootstrap
{
    public static function start($worker)
    {
        if ($worker->name == 'monitor') {
            return;
        }

        if (!Module::isInstalled()) {
            Route::load(base_path() . "/config/plugin/tpext/myadmin/");
        }
    }
}

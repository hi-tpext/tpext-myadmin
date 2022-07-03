<?php

namespace tpext\myadmin\webman;

use tpext\myadmin\common\Module;

class BootStrap implements \Webman\Bootstrap
{
    public static function start($worker)
    {
        if ($worker->name == 'monitor') {
            return;
        }

        $route = base_path() . "/config/plugin/tpext/myadmin/route.php";

        if (!Module::isInstalled()) {
            if (!is_file($route)) {
                copy(__DIR__ . "/route.php",$route);
            }
        } else {
            if (is_file($route)) {
                unlink($route);
            }
        }
    }
}

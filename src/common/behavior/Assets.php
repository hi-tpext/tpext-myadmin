<?php

namespace tpext\myadmin\common\behavior;

use tpext\common\model\WebConfig;
use tpext\common\Tool;
use tpext\myadmin\common\Module;
use tpext\think\App;

class Assets
{
    public function run($data = [])
    {
        $key = Module::getInstance()->getId();
        $config = Module::getInstance()->getConfig();
        $config['assets_ver'] = date('Y-m-d-H:i:s');
        WebConfig::where('key', $key)->update(['config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        WebConfig::clearCache($key);

        if ($config['minify']) {
            $dirs = ['', 'assets', 'minify', ''];

            $minifyDir = App::getPublicPath() . implode(DIRECTORY_SEPARATOR, $dirs);
            Tool::deleteDir($minifyDir);
        }
    }
}

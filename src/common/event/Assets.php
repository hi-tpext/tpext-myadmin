<?php

namespace tpext\myadmin\common\event;

use tpext\common\model\WebConfig;
use tpext\common\Tool;
use tpext\myadmin\common\Module;

class Assets
{
    public function handle($data)
    {
        $key = Module::getInstance()->getId();
        $config = Module::getInstance()->getConfig();
        $config['assets_ver'] = date('Y-m-d-H:i:s');
        WebConfig::where('key', $key)->update(['config' => json_encode($config, JSON_UNESCAPED_UNICODE)]);
        WebConfig::clearCache($key);

        if ($config['minify']) {
            $dirs = ['', 'assets', 'minify', ''];
            $scriptName = $_SERVER['SCRIPT_FILENAME'];
            $minifyDir = realpath(dirname($scriptName)) . implode(DIRECTORY_SEPARATOR, $dirs);
            Tool::deleteDir($minifyDir);
        }

        return true;
    }
}

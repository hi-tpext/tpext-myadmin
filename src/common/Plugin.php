<?php

namespace tpext\myadmin\common;

use tpext\common\ExtLoader;
use tpext\common\Plugin as basePlugin;
use tpext\myadmin\common\hooks\Setup;

class Plugin extends basePlugin
{
    protected $name = 'tpext.myadmin';

    protected $__root__ = __DIR__ . '/../../';

    public function pluginInit($info = [])
    {
        ExtLoader::watch('module_init', Setup::class, '替换错误及跳转模板', false);

        return parent::pluginInit($info);
    }
}

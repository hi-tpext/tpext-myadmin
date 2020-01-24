<?php

namespace tpext\lyatadmin\common;

use tpext\common\ExtLoader;
use tpext\common\Plugin as basePlugin;
use tpext\lyatadmin\common\hooks\Setup;

class Plugin extends basePlugin
{
    protected $name = 'tpext.lyatadmin.plugin';

    protected $__root__ = __DIR__ . '/../../';

    public function pluginInit($info = [])
    {
        ExtLoader::watch('module_init', Setup::class, '替换错误及跳转模板', false);

        return true;
    }
}

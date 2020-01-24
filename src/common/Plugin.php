<?php

namespace tpext\lyatadmin\common;

use think\facade\View;
use tpext\common\ExtLoader;
use tpext\common\Plugin as basePlugin;
use tpext\lyatadmin\common\hooks\Tpl;

class Plugin extends basePlugin
{
    protected $name = 'tpext.lyatadmin.plugin';

    protected $__root__ = __DIR__ . '/../../';

    public function pluginInit($info = [])
    {
        ExtLoader::watch('module_init', Tpl::class, '替换错误及跳转模板', false);

        $config = config($this->getId());

        View::share('$__ADMIN__', $this->assetsDirName());

        View::share('admin', $config);

        return true;
    }
}

<?php

namespace tpext\lyatadmin\common;

use tpext\common\Module as baseModule;

class Module extends baseModule
{
    protected $name = 'tpext.lyatadmin';

    protected $__root__ = __DIR__ . '/../../';

    protected $assets = 'assets';

    protected $modules = [
        'admin' => ['index'],
    ];

    public function moduleInit($info = [])
    {
        return parent::moduleInit($info);
    }
}

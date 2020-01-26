<?php

namespace tpext\myadmin\common;

use tpext\common\Module as baseModule;

class Module extends baseModule
{
    protected $name = 'tpext.myadmin';

    protected $__root__ = __DIR__ . '/../../';

    protected $modules = [
        'admin' => ['index'],
    ];

    public function moduleInit($info = [])
    {
        parent::moduleInit($info);

        return true;
    }
}

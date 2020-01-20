<?php

namespace tpext\lyatadmin\common;

use tpext\common\Plugin as basePlugin;

class Plugin extends basePlugin
{
    public static function pluginInit($info = [])
    {
        return true;
    }
}

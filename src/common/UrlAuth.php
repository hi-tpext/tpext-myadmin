<?php

namespace tpext\myadmin\common;

use tpext\myadmin\admin\model\AdminUser;
use tpext\builder\inface\Auth;

class UrlAuth implements Auth
{
    public static function checkUrl($url)
    {
        return AdminUser::checkUrl($url);
    }
}

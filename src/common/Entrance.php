<?php

namespace tpext\myadmin\common;

use think\facade\Session;

class Entrance
{
    public function run($entrance)
    {
        Session::set('login_session_key', 1);

        $admin_id = Session::get('admin_id');
        if (!empty($admin_id) && is_numeric($admin_id) && $admin_id > 0) {
            return redirect(url('/admin/index/index'))
                ->cookie('tpext_myadmin_entry', $entrance, 3600 * 24 * 365, '', '', false, true);
        }

        return redirect(url('/admin/index/login', [], false))
            ->cookie('tpext_myadmin_entry', $entrance, 3600 * 24 * 365, '', '', false, true);
    }
}

<?php

namespace tpext\myadmin\common;

class Entrance
{
    public function run($entrance)
    {
        session('login_session_key', 1);
        cookie('tpext_myadmin_entry', $entrance, ['expire' => 3600 * 24 * 365, 'httponly' => true]);

        $admin_id = session('admin_id');
        if (!empty($admin_id) && is_numeric($admin_id) && $admin_id > 0) {
            return redirect(url('/admin/index/index'));
        }
        
        return redirect(url('/admin/index/login', [], false));
    }
}

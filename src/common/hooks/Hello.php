<?php
namespace tpext\helloworld\common\hooks;

use \think\facade\Log;

class Hello
{
    public function run($data)
    {
        Log::info('app_end . writed from ' . __FILE__);
    }
}

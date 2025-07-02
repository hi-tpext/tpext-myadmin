<?php

namespace tpext\myadmin\common;

use tpext\think\App;

class RouteMaker
{
    /**
     * 生成路由文件
     * @param mixed $force
     * @return bool|int
     */
    public static function make($force = false)
    {
        $file = App::getRootPath() . 'route/tpext-myadmin.php';
        if (!$force && file_exists($file)) {
            return true;
        }

        if (!is_dir(App::getRootPath() . 'route/')) {
            mkdir(App::getRootPath() . 'route/', 0755, true);
        }

        $randomString = self::randomString();

        $root = Module::getInstance()->getRoot();
        $tpl = file_get_contents($root . 'src/route.php');
        $tpl = str_replace('__entrance__', $randomString, $tpl);

        $res = @file_put_contents($file, $tpl);

        if (!$res) {
            return false;
        }

        $routeConfig = file_get_contents(config_path() . '/route.php');

        if (!strstr($routeConfig, 'route/tpext-myadmin.php')) {
            $routeConfig .= PHP_EOL . '//引入tpext-myadmin路由（隐藏后台登录入口）';
            $routeConfig .= PHP_EOL . "if (file_exists(base_path('route/tpext-myadmin.php'))) {";
            $routeConfig .= PHP_EOL . "    require_once base_path('route/tpext-myadmin.php');";
            $routeConfig .= PHP_EOL . '}';
        }

        return @file_put_contents(config_path() . '/route.php', $routeConfig);
    }

    /**
     * 获取入口地址
     * @return string
     */
    public static function getEntrance()
    {
        $file = App::getRootPath() . 'route/tpext-myadmin.php';
        if (!file_exists($file)) {
            return '路由不存在';
        }

        $content = file_get_contents($file);
        if (preg_match('/.*?\$entrance\s*=\s*[\'\"]([^\'\"]+?)[\'\"].*?/i', $content, $matches)) {
            return 'http://' . request()->host() . $matches[1];
        }

        return '';
    }

    /**
     * 移除路由文件
     * @return bool
     */
    public static function remove()
    {
        $file = App::getRootPath() . 'route/tpext-myadmin.php';
        if (file_exists($file)) {
            return @unlink($file);
        }

        return true;
    }

    public static function randomString($length = 10)
    {
        $characters = '23456789abcdefghijkmnpqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i += 1) {
            $randomIndex = mt_rand(0, $charactersLength - 1);
            $randomString .= $characters[$randomIndex];
        }

        return $randomString;
    }
}

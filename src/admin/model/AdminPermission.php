<?php

namespace tpext\myadmin\admin\model;

use think\Model;
use tpext\common\Extension;
use tpext\common\ExtLoader;
use tpext\common\Tool;

class AdminPermission extends Model
{
    protected $autoWriteTimestamp = 'dateTime';

    /**
     * Undocumented function
     *
     * @param \ReflectionClass $reflection
     * @return array
     */
    private function getMethods($reflection)
    {
        $methods = [];
        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class == $reflection->getName() && !in_array($method->name, ['__construct', '_initialize'])) {
                $methods[] = $method->name;
            }
        }

        return $methods;
    }

    public function getControllers()
    {
        $appPath = app()->getRootPath() . 'application' . DIRECTORY_SEPARATOR . 'admin/controller';

        $modControllers = [];
        $baseControllers = $this->scanControllers($appPath);

        if (!empty($baseControllers)) {
            $modControllers['app']['controllers'] = $this->scanControllers($appPath);
            $modControllers['app']['title'] = '基础';
        }

        $modControllers = array_merge($modControllers, $this->scanextEnsionControllers());

        ksort($modControllers);

        return $modControllers;
    }

    public function scanextEnsionControllers()
    {
        $controllers = [];

        $extensions = Extension::extensionsList();

        $installed = ExtLoader::getInstalled();

        foreach ($extensions as $key => $instance) {
            $is_enable = 0;

            if (!empty($installed)) {
                foreach ($installed as $ins) {
                    if ($ins['key'] == $key) {
                        $is_enable = $ins['enable'];
                        break;
                    }
                }
            }
            if (!$is_enable) {
                continue;
            }

            $mods = $instance->getModules();

            $namespaceMap = $instance->getNameSpaceMap();

            if (empty($namespaceMap) || count($namespaceMap) != 2) {
                $namespaceMap = Tool::getNameSpaceMap($key);
            }

            $namespace = rtrim($namespaceMap[0], '\\');
            $url_controller_layer = 'controller';

            if (!empty($mods)) {

                $controllers[$instance->getId()]['title'] = $instance->getTitle();

                foreach ($mods as $module => $modControllers) {

                    if (strtolower($module) != 'admin') {
                        continue;
                    }

                    foreach ($modControllers as $modController) {

                        if (false !== strpos($modController, '\\')) {
                            $class = '\\' . $module . '\\' . $modController . ltrim($module, '\\');

                        } else {
                            $class = '\\' . $module . '\\' . $url_controller_layer . '\\' . ucfirst($modController);
                        }

                        if (class_exists($namespace . $class)) {

                            $controller = $namespace . $class;
                            $reflectionClass = new \ReflectionClass($controller);
                            $methods = $this->getMethods($reflectionClass);

                            $controllers[$instance->getId()]['controllers'][$controller] = $methods;
                        }
                    }
                }
            }
        }

        return $controllers;
    }

    public function scanControllers($path, $controllers = [])
    {
        if(!is_dir($path))
        {
            return [];
        }
        $dir = opendir($path);

        while (false !== ($file = readdir($dir))) {

            if (($file != '.') && ($file != '..')) {

                $sonDir = $path . DIRECTORY_SEPARATOR . $file;

                if (is_dir($sonDir)) {
                    //$controllers = array_merge($controllers, $this->scanControllers($sonDir));
                } else {
                    $sonDir = str_replace('/', '\\', $sonDir);

                    if (preg_match('/.+?\\\application(\\\admin\\\controller\\\.+?)\.php$/i', $sonDir, $mtches)) {
                        if (class_exists('app' . $mtches[1])) {
                            $controller = 'app' . $mtches[1];
                            $reflectionClass = new \ReflectionClass($controller);
                            $methods = $this->getMethods($reflectionClass);

                            $controllers[$controller] = $methods;
                        }
                    }
                }
            }
        }

        return $controllers;
    }
}

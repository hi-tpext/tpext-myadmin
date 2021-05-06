<?php

namespace tpext\myadmin\common;

use MatthiasMullie\Minify;
use tpext\builder\common\Builder;
use tpext\builder\common\Module as BModule;
use tpext\builder\common\Wrapper;
use tpext\common\ExtLoader;
use tpext\common\Tool;

class MinifyTool
{
    protected static $js = [
        '/assets/lightyearadmin/js/jquery.min.js',
        '/assets/lightyearadmin/js/bootstrap.min.js',
        '/assets/lightyearadmin/js/jquery.lyear.loading.js',
        '/assets/lightyearadmin/js/bootstrap-notify.min.js',
        '/assets/lightyearadmin/js/jconfirm/jquery-confirm.min.js',
        '/assets/lightyearadmin/js/lightyear.js',
        '/assets/lightyearadmin/js/main.min.js',
    ];

    protected static $css = [
        '/assets/lightyearadmin/css/bootstrap.min.css',
        '/assets/lightyearadmin/css/materialdesignicons.min.css',
        '/assets/lightyearadmin/css/animate.css',
        '/assets/lightyearadmin/css/style.min.css',
        '/assets/lightyearadmin/js/jconfirm/jquery-confirm.min.css',
    ];

    private $path;
    private $public;

    public function __construct()
    {
        $this->path = $this->checkAssetsDir('minify');
        $this->public = dirname(dirname($this->path));
    }

    /**
     * Undocumented function
     *
     * @param array|string $val
     * @return void
     */
    public static function addJs($val)
    {
        if (!is_array($val)) {
            $val = [$val];
        }
        static::$js = array_merge(static::$js, $val);
    }

    /**
     * Undocumented function
     *
     * @param array|string $val
     * @return void
     */
    public static function addCss($val)
    {
        if (!is_array($val)) {
            $val = [$val];
        }

        static::$css = array_merge(static::$css, $val);
    }

    /**
     * Undocumented function
     *
     * @param array|string $val
     * @return void
     */
    public static function removeJs($val)
    {
        if (!is_array($val)) {
            $val = [$val];
        }

        foreach (static::$js as $k => $j) {
            if (in_array($j, $val)) {
                unset(static::$js[$k]);
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param array|string $val
     * @return void
     */
    public static function removeCss($val)
    {
        if (!is_array($val)) {
            $val = [$val];
        }

        foreach (static::$css as $k => $c) {
            if (in_array($c, $val)) {
                unset(static::$css[$k]);
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param string $val
     * @param string $newVal
     * @return void
     */
    public static function replaceJs($val, $newVal)
    {
        foreach (static::$js as $k => $j) {
            if ($val == $j) {
                static::$js[$k] = $newVal;
            }
        }
    }

    /**
     * Undocumented function
     *
     * @param string $val
     * @param string $newVal
     * @return void
     */
    public static function replaceCss($val, $newVal)
    {
        foreach (static::$css as $k => $c) {
            if ($val == $c) {
                static::$css[$k] = $newVal;
            }
        }
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public static function getJs()
    {
        return static::$js;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public static function getCss()
    {
        return static::$css;
    }

    public function minify()
    {
        $this->minifyJs();
        $this->minifyCss();

        static::$js = ['/assets/minify/min.js'];
        static::$css = ['/assets/minify/min.css'];

        Builder::getInstance()->minify(true);
    }

    private function minifyJs()
    {
        if (is_file($this->path . 'min.js')) {
            return;
        }

        $minifier = new Minify\JS;

        $this->createBuilder();

        foreach (static::$js as $j) {
            if (is_file($this->public . $j)) {
                $minifier->addFile($this->public . $j);
            }
        }

        $minifier->minify($this->path . 'min.js');
    }

    private function minifyCss()
    {
        if (is_file($this->path . 'min.css')) {
            return;
        }

        $minifier = new Minify\CSS();

        foreach (static::$css as $c) {
            if (is_file($this->public . $c)) {
                $minifier->addFile($this->public . $c);
            }
        }

        $minifier->minify($this->path . 'min.css');
    }

    private function createBuilder()
    {
        $displayerMap = Wrapper::getDisplayerMap();

        foreach ($displayerMap as $name => $class) {

            $field = new $class($name, $name);
            if ($field->canMinify()) {
                $this->addJs($field->getJs());
            }
            $this->addCss($field->getCss());
        }

        $builder = Builder::getInstance();

        $this->addJs($builder->commonJs());
        $this->addCss($builder->commonCss());

        ExtLoader::trigger('befor_minify', $this->path);

        $dirs = ['assets', 'js', 'layer', 'theme'];
        $layerDir = BModule::getInstance()->getRoot() . implode(DIRECTORY_SEPARATOR, $dirs);
        Tool::copyDir($layerDir, $this->path . DIRECTORY_SEPARATOR . 'theme');

        ExtLoader::trigger('after_minify', $this->path);
    }

    private function checkAssetsDir($dirName)
    {
        $dirs = ['', 'assets', $dirName, ''];

        $scriptName = $_SERVER['SCRIPT_FILENAME'];

        $minifyDir = realpath(dirname($scriptName)) . implode(DIRECTORY_SEPARATOR, $dirs);

        if (is_dir($minifyDir)) {

            return $minifyDir;
        }

        mkdir($minifyDir, 0755, true);

        return $minifyDir;
    }
}

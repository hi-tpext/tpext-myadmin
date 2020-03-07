<?php

namespace tpext\myadmin\common;

use MatthiasMullie\Minify\css;
use MatthiasMullie\Minify\JS;
use tpext\builder\common\Builder;
use tpext\builder\form\Wapper;
use tpext\common\ExtLoader;

class MinifyTool
{
    protected static $js = [
        '/assets/lightyearadmin/js/jquery.min.js',
        '/assets/lightyearadmin/js/bootstrap.min.js',
        '/assets/lightyearadmin/js/jquery.lyear.loading.js',
        '/assets/lightyearadmin/js/bootstrap-notify.min.js',
        '/assets/lightyearadmin/js/lightyear.js',
        '/assets/lightyearadmin/js/main.min.js',
        '/assets/lightyearadmin/js/jconfirm/jquery-confirm.min.js',
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
     * @param array $val
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
     * @param array $val
     * @return $this
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

        require_once app()->getRootPath() . implode(DIRECTORY_SEPARATOR, ['vendor', 'matthiasmullie', 'minify', 'src', 'JS.php']);
        $minifier = new JS;

        $this->createBuilder();

        foreach (static::$js as $j) {
            if ($j == '/assets/tpextbuilder/js/layer/layer.js') {
                continue;
            }

            $minifier->addFile($this->public . $j);
        }

        $minifier->minify($this->path . 'min.js');
    }

    private function minifyCss()
    {
        if (is_file($this->path . 'min.css')) {
            return;
        }

        require_once app()->getRootPath() . implode(DIRECTORY_SEPARATOR, ['vendor', 'matthiasmullie', 'minify', 'src', 'CSS.php']);
        $minifier = new Css();

        foreach (static::$css as $c) {
            $minifier->addFile($this->public . $c);
        }

        $minifier->minify($this->path . 'min.css');
    }

    private function createBuilder()
    {
        $builder = Builder::getInstance();
        $form = $builder->row()->column(12)->form();
        $table = $builder->row()->column(12)->table();

        $displayerMap = Wapper::getDisplayerMap();

        foreach ($displayerMap as $name => $class) {
            $form->$name($name)->value($name);
            $table->$name($name)->value($name);
        }

        ExtLoader::watch('builder_render', static::class);
        $builder->render();
    }

    private function checkAssetsDir($dirName)
    {
        $dirs = ['public', 'assets', $dirName, ''];

        $minifyDir = app()->getRootPath() . implode(DIRECTORY_SEPARATOR, $dirs);

        if (is_dir($minifyDir)) {

            return $minifyDir;
        }

        mkdir($minifyDir, 0775);

        return $minifyDir;
    }

    public function run($data)
    {
        if (is_array($data) && count($data) == 2) {
            $this->addJs($data[0]);
            $this->addCss($data[1]);
        }
    }
}

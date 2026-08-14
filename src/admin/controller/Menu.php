<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\helper\Str;
use tpext\builder\traits\actions;
use tpext\myadmin\common\Module;
use tpext\myadmin\admin\model\AdminMenu;
use tpext\myadmin\admin\model\AdminPermission;

/**
 * Undocumented class
 * @title 菜单管理
 */
class Menu extends Controller
{
    use actions\HasIAED;
    use actions\HasAutopost;

    /**
     * Undocumented variable
     *
     * @var AdminMenu
     */
    protected $dataModel;

    /**
     * Undocumented variable
     *
     * @var AdminPermission
     */
    protected $permModel;

    protected function initialize()
    {
        Module::getInstance()->loadLang('menu');

        $this->dataModel = new AdminMenu;
        $this->permModel = new AdminPermission;

        $this->pageTitle = __admin_lang('menu_management');
        $this->sortOrder = 'id desc';
        $this->postAllowFields = ['title', 'sort', 'enable'];

        $this->selectTextField = '{title}';
        $this->selectFields = 'id,title';
        $this->selectSearch = 'title';
    }

    /**
     * 构建表单
     *
     * @param boolean $isEdit
     * @param array $data
     */
    protected function buildForm($isEdit, &$data = [])
    {
        $form = $this->form;

        $tree = [0 => __admin_lang('root_menu')];

        $list = $this->dataModel->getLineData($isEdit ? $data['id'] : 0);

        $options = [];

        foreach ($list as $li) {
            if ($li['url'] == '#') {
                $options[$li['id']] = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;', '──', $li['__text__']);
            }
        }

        $tree += $options; //数组合并不要用 array_merge , 会重排数组键 ，作为options导致bug

        $modControllers = $this->permModel->getControllers();

        $urls = [];

        $urls[''] = [
            'label' => __admin_lang('is_menu_dir'),
            'options' => [
                '' => __admin_lang('please_select'),
                '#' => __admin_lang('is_dir_with_children'),
            ],
        ];

        $contrl = null;
        $contrlPerm = null;
        $actionPerm = null;
        $arr = null;

        $permissionList = $this->permModel->select();

        foreach ($modControllers as $key => $modController) {

            $urls[$key]['label'] = '[' . $modController['title'] . ']';
            $urls[$key]['options'] = [];

            foreach ($modController['controllers'] as $controller => $info) {

                $contrlPerm = null;

                $contrl = preg_replace('/.+?\\\controller\\\(.+?)(controller)?$/i', '$1', $controller);
                if (strpos($contrl, '\\') !== false) {
                    $arr = explode('\\', $contrl);
                    $contrl = $arr[0] . '/' . $arr[1];
                }

                foreach ($permissionList as $prow) {
                    if ($prow['controller'] == $controller . '::class' && $prow['action'] == '#') {
                        $contrlPerm = $prow;
                        break;
                    }
                }

                $urls[$key . '_' . $contrl]['label'] = ($contrlPerm ? $contrlPerm['action_name'] : $contrl);

                $options = [];

                foreach ($info['methods'] as $method) {

                    $actionPerm = null;
                    $url = url('/admin/' . $contrl . '/' . strtolower($method->name), [], false)->__toString();

                    foreach ($permissionList as $prow) {
                        if ($prow['controller'] == $controller . '::class' && $prow['url'] == $url) {
                            $actionPerm = $prow;
                            break;
                        }
                    }

                    if ($actionPerm && $actionPerm['action_type'] != 1) {
                        continue;
                    }
                    $options[$url] = $url;
                }

                $urls[$key . '_' . $contrl]['options'] = $options;
            }
        }

        $form->text('title')->required();
        $form->select('parent_id')->required()->options($tree);
        $form->select('url')->required()->options($urls);
        $form->icon('icon')->required()->default('mdi mdi-access-point');
        $form->radio('enable')->default(1)->required()->options([1 => __admin_lang('enabled'), 0 => __admin_lang('disabled')])
            ->disabled($isEdit && $data['url'] == '/admin/menu/index');
        $form->text('sort')->default(1)->required();

        if ($isEdit) {
            $form->show('create_time');
            $form->show('update_time');
        }
    }

    /**
     * 构建表格
     *
     * @return void
     */
    protected function buildTable(&$data = [])
    {
        $table = $this->table;
        $table->show('id');
        $table->raw('__text__', __admin_lang('structure'))->getWrapper()->addStyle('text-align:left;');
        $table->show('url');
        $table->raw('icon')->to('<i class="{val}"></i>');
        $table->text('title')->autoPost('', true)->getWrapper()->addStyle('max-width:80px');
        $table->switchBtn('enable')->default(1)->autoPost()->mapClass('/admin/menu/index', 'hidden', 'url')->getWrapper()->addStyle('max-width:120px');
        $table->text('sort')->autoPost('', true)->getWrapper()->addStyle('max-width:40px');
        $table->show('create_time')->getWrapper()->addStyle('width:180px');
        $table->show('update_time')->getWrapper()->addStyle('width:180px');

        $table->sortable([]);

        foreach ($data as &$d) {
            $d['__dis_del__'] = $d['url'] == '/admin/menu/index';
        }

        unset($d);

        $table->getActionbar()->mapClass([
            'delete' => ['disabled' => '__dis_del__'],
        ]);
    }

    protected function save($id = 0)
    {
        $data = request()->only([
            'title',
            'url',
            'icon',
            'sort',
            'enable',
            'parent_id',
        ], 'post');

        $result = $this->validate($data, [
            'title|' . __admin_lang('title') => 'require',
            'url|' . __admin_lang('url') => 'require',
            'icon|' . __admin_lang('icon') => 'require',
            'sort|' . __admin_lang('sort') => 'require|number',
            'parent_id|' . __admin_lang('parent_id') => 'require',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        if ($id && $data['parent_id'] == $id) {
            $this->error(__admin_lang('parent_cannot_be_self'));
        }

        return $this->doSave($data, $id);
    }
}

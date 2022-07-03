<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\helper\Arr;
use think\helper\Str;
use tpext\builder\traits\actions;
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
        $this->dataModel = new AdminMenu;
        $this->permModel = new AdminPermission;

        $this->pageTitle = '菜单管理';
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

        $tree = [0 => '根菜单'];

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
            'label' => '是否菜单目录？',
            'options' => [
                '' => '请选择',
                '#' => '是目录，拥有下级节点',
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

                $contrl = preg_replace('/.+?\\\controller\\\(.+)$/', '$1', $controller);
                if (strpos($contrl, '\\') !== false) {
                    $arr = explode('\\', $contrl);
                    $contrl = $arr[0] . '.' . Str::snake($arr[1]);
                } else {
                    $contrl = Str::snake($contrl);
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

        $form->text('title', '名称')->required();
        $form->select('parent_id', '上级')->required()->options($tree);
        $form->select('url', 'url')->required()->options($urls);
        $form->icon('icon', '图标')->required()->default('mdi mdi-access-point');
        $form->radio('enable', '启用')->default(1)->required()->options([1 => '已启用', 0 => '未启用'])
            ->disabled($isEdit && $data['url'] == '/admin/menu/index');
        $form->text('sort', '排序')->default(1)->required();

        if ($isEdit) {
            $form->show('create_time', '添加时间');
            $form->show('update_time', '修改时间');
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
        $table->show('id', 'ID');
        $table->raw('__text__', '结构')->getWrapper()->addStyle('text-align:left;');
        $table->show('url', 'url');
        $table->raw('icon', '图标')->to('<i class="{val}"></i>');
        $table->text('title', '名称')->autoPost('', true)->getWrapper()->addStyle('max-width:80px');
        $table->switchBtn('enable', '启用')->default(1)->autoPost()->mapClassWhen('/admin/menu/index', 'hidden', 'url')->getWrapper()->addStyle('max-width:120px');
        $table->text('sort', '排序')->autoPost('', true)->getWrapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWrapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWrapper()->addStyle('width:180px');

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
            'title|名称' => 'require',
            'url|url' => 'require',
            'icon|图标' => 'require',
            'sort|排序' => 'require|number',
            'parent_id|上级' => 'require',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        if ($id && $data['parent_id'] == $id) {
            $this->error('上级不能是自己');
        }

        return $this->doSave($data, $id);
    }
}

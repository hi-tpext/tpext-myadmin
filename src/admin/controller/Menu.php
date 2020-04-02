<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;
use tpext\builder\traits\actions\HasIAED;
use tpext\builder\traits\actions\HasAutopost;
use tpext\myadmin\admin\model\AdminMenu;
use tpext\myadmin\admin\model\AdminPermission;

class Menu extends Controller
{
    use HasIAED;
    use HasAutopost;

    protected $dataModel;
    protected $roleModel;

    protected function initialize()
    {
        $this->dataModel = new AdminMenu;
        $this->permModel = new AdminPermission;

        $this->pageTitle = '菜单管理';
        $this->sortOrder = 'id desc';
        $this->pagesize = 999;
        $this->postAllowFields = ['title', 'sort'];
        $this->delNotAllowed = [1];
    }

    /**
     * 构建表单
     *
     * @param boolean $isEdit
     * @param array $data
     */
    protected function builForm($isEdit, &$data = [])
    {
        $form = $this->form;

        $tree = [0 => '根菜单'];

        $tree += $this->dataModel->buildTree(0, 0, $isEdit ? $data['id'] : 0); //数组合并不要用 array_merge , 会重排数组键 ，作为options导致bug

        $modControllers = $this->permModel->getControllers();

        $urls = [];

        $urls[''] = [
            'label' => '是否菜单目录？',
            'options' => [
                '' => '请选择',
                '#' => '是目录，拥有下级节点',
            ],
        ];
        foreach ($modControllers as $key => $modController) {

            $urls[$key]['label'] = '[' . $modController['title'] . ']';
            $urls[$key]['options'] = [];

            foreach ($modController['controllers'] as $controller => $methods) {

                $contrl = preg_replace('/.+?\\\controller\\\(\w+)$/', '$1', $controller);

                $permission = $this->permModel->where(['controller' => $controller, 'action' => '#'])->find();

                $urls[$key . '_' . $contrl]['label'] = ($permission ? $permission['action_name'] : $contrl);

                foreach ($methods as $method) {
                    $url = url('/admin/' . strtolower($contrl) . '/' . $method, '', false);

                    $perm = $this->permModel->where(['url' => $url])->find();

                    if ($perm && $perm['action_type'] != 1) {
                        continue;
                    }

                    $urls[$key . '_' . $contrl]['options'][$url] = $url;
                }
            }
        }
        $form->text('title', '名称')->required();
        $form->select('parent_id', '上级')->required()->options($tree);
        $form->select('url', 'url')->required()->options($urls);
        $form->icon('icon', '图标')->required()->default('mdi mdi-access-point');
        $form->text('sort', '排序')->default(1)->required();

        if ($isEdit) {
            $form->show('create_time', '添加时间');
            $form->show('update_time', '修改时间');
        }
    }

    /**
     * Undocumented function
     *
     * @param Table $table
     * @return void
     */
    protected function buildDataList()
    {
        $table = $this->table;

        $table->sortable([]);

        $data = $this->dataModel->buildList(0, 0);
        $this->buildTable($data);
        $table->fill($data);
        $table->paginator(count($data), $this->pagesize);
    }

    /**
     * 构建表格
     *
     * @return void
     */
    protected function buildTable()
    {
        $table = $this->table;
        $table->show('id', 'ID');
        $table->raw('title_show', '名称')->getWapper()->addStyle('text-align:left;');
        $table->show('url', 'url');
        $table->raw('icon_show', '图标');
        $table->text('title', '名称')->autoPost()->getWapper()->addStyle('max-width:80px');
        $table->text('sort', '排序')->autoPost()->getWapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWapper()->addStyle('width:180px');

        $table->sortable([]);
    }

    private function save($id = 0)
    {
        $data = request()->only([
            'title',
            'url',
            'icon',
            'sort',
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

        if ($id) {
            if ($data['parent_id'] == $id) {
                $this->error('上级不能是自己');
            }
            $data['update_time'] = date('Y-m-d H:i:s');
            $res = $this->dataModel->where(['id' => $id])->update($data);
        } else {
            $res = $this->dataModel->create($data);
        }

        if (!$res) {
            $this->error('保存失败');
        }

        return Builder::getInstance()->layer()->closeRefresh(1, '保存成功');
    }

    protected function filterWhere()
    {
        $where = [];

        return $where;
    }

    /**
     * 构建搜索
     *
     * @return void
     */
    protected function builSearch()
    {
        $search = $this->search;
    }
}

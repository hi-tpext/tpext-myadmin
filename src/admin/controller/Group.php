<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;
use tpext\builder\traits\actions\HasIAED;
use tpext\builder\traits\actions\HasAutopost;
use tpext\myadmin\admin\model\AdminGroup;

class Group extends Controller
{
    use HasIAED;
    use HasAutopost;

    protected $dataModel;

    protected function initialize()
    {
        $this->dataModel = new AdminGroup;

        $this->pageTitle = '分组管理';
        $this->sortOrder = 'id desc';
        $this->pagesize = 999;
        $this->postAllowFields = ['name', 'sort'];
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

        $tree = [0 => '顶级分组'];

        $tree += $this->dataModel->buildTree(0, 0, $isEdit ? $data['id'] : 0); //数组合并不要用 array_merge , 会重排数组键 ，作为options导致bug

        $form->text('name', '名称')->required();

        $form->textarea('description', '描述')->maxlength(100);
        $form->select('parent_id', '上级')->required()->options($tree);
        $form->tags('tags', '标签');
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
    protected function buildTable(&$data = [])
    {
        $table = $this->table;
        $table->show('id', 'ID');
        $table->raw('title_show', '名称')->getWapper()->addStyle('text-align:left;');
        $table->show('users', '用户数');
        $table->show('description', '描述')->default('无描述');
        $table->text('name', '名称')->autoPost()->getWapper()->addStyle('max-width:80px');
        $table->text('sort', '排序')->autoPost()->getWapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWapper()->addStyle('width:180px');

        $table->sortable([]);
    }

    private function save($id = 0)
    {
        $data = request()->only([
            'name',
            'description',
            'tags',
            'sort',
            'parent_id',
        ], 'post');

        $result = $this->validate($data, [
            'name|名称' => 'require',
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
}

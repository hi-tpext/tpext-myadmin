<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\traits\actions;
use tpext\myadmin\admin\model\AdminGroup;
use tpext\myadmin\admin\model\AdminUser;
use tpext\myadmin\common\Module;

/**
 * Undocumented class
 * @title 分组管理
 */
class Group extends Controller
{
    use actions\HasIAED;
    use actions\HasAutopost;

    /**
     * Undocumented variable
     *
     * @var AdminGroup
     */
    protected $dataModel;

    /**
     * Undocumented variable
     *
     * @var AdminUser
     */
    protected $userModel;

    protected $adminGroupTitle = '分组';

    protected function initialize()
    {
        $instance = Module::getInstance();

        $config = $instance->getConfig();

        if (!empty($config['admin_group_title'])) {
            $this->adminGroupTitle = $config['admin_group_title'];
        }

        $this->userModel = new AdminUser;

        $this->dataModel = $this->userModel->getAdminGroupModel();

        $this->pageTitle = $this->adminGroupTitle . '管理';
        $this->sortOrder = 'id desc';
        $this->pagesize = 999;
        $this->postAllowFields = ['name', 'sort'];

        $this->selectTextField = '{id}#{name}';
        $this->selectFields = 'id,name';
        $this->selectSearch = 'name';
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

        $tree = [0 => '顶级' . $this->adminGroupTitle];
        $tree += $this->dataModel->getOptionsData($isEdit ? $data['id'] : 0); //数组合并不要用 array_merge , 会重排数组键 ，作为options导致bug

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
     * 构建表格
     *
     * @return void
     */
    protected function buildTable(&$data = [])
    {
        $table = $this->table;
        $table->show('id', 'ID');
        $table->raw('__text__', '名称')->getWrapper()->addStyle('text-align:left;');
        $table->show('users', '用户数');
        $table->show('description', '描述')->default('无描述');
        $table->text('name', '名称')->autoPost('', true)->getWrapper()->addStyle('max-width:80px');
        $table->text('sort', '排序')->autoPost('', true)->getWrapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWrapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWrapper()->addStyle('width:180px');

        $table->sortable([]);
    }

    protected function save($id = 0)
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

        if ($id && $data['parent_id'] == $id) {
            $this->error('上级不能是自己');
        }

        return $this->doSave($data, $id);
    }
}

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

    protected $adminGroupTitle = '';

    protected function initialize()
    {
        Module::getInstance()->loadLang('group');

        $instance = Module::getInstance();

        $config = $instance->getConfig();

        if (!empty($config['admin_group_title'])) {
            $this->adminGroupTitle = $config['admin_group_title'];
        } else {
            $this->adminGroupTitle = __admin_lang('group');
        }

        $this->userModel = new AdminUser;

        $this->dataModel = $this->userModel->getAdminGroupModel();

        $this->pageTitle = $this->adminGroupTitle . __admin_lang('management');
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

        $tree = [0 => __admin_lang('top_level') . $this->adminGroupTitle];
        $tree += $this->dataModel->getOptionsData($isEdit ? $data['id'] : 0); //数组合并不要用 array_merge , 会重排数组键 ，作为options导致bug

        $form->text('name')->required();

        $form->textarea('description')->maxlength(100);
        $form->select('parent_id')->required()->options($tree);
        $form->tags('tags');
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
        $table->raw('__text__', __admin_lang('name'))->getWrapper()->addStyle('text-align:left;');
        $table->show('users', __admin_lang('users_count'));
        $table->show('description')->default(__admin_lang('no_description'));
        $table->text('name')->autoPost('', true)->getWrapper()->addStyle('max-width:80px');
        $table->text('sort')->autoPost('', true)->getWrapper()->addStyle('max-width:40px');
        $table->show('create_time')->getWrapper()->addStyle('width:180px');
        $table->show('update_time')->getWrapper()->addStyle('width:180px');

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
            'name|' . __admin_lang('name') => 'require',
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

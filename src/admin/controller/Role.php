<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\Db;
use tpext\builder\traits\HasBuilder;
use tpext\myadmin\admin\model\AdminMenu;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\admin\model\AdminRole;
use tpext\myadmin\admin\model\AdminRoleMenu;
use tpext\myadmin\admin\model\AdminRolePermission;

/**
 * Undocumented class
 * @title 角色管理
 */
class Role extends Controller
{
    use HasBuilder;

    /**
     * Undocumented variable
     *
     * @var AdminRole
     */
    protected $dataModel;
    /**
     * Undocumented variable
     *
     * @var AdminPermission
     */
    protected $permModel;
    /**
     * Undocumented variable
     *
     * @var AdminRolePermission
     */
    protected $rolePermModel;
    /**
     * Undocumented variable
     *
     * @var AdminMenu
     */
    protected $menuModel;
    /**
     * Undocumented variable
     *
     * @var AdminRoleMenu
     */
    protected $roleMenuModel;

    protected function initialize()
    {
        $this->dataModel = new AdminRole;
        $this->permModel = new AdminPermission;
        $this->rolePermModel = new AdminRolePermission;
        $this->menuModel = new AdminMenu;
        $this->roleMenuModel = new AdminRoleMenu;

        $this->pageTitle = '角色管理';
        $this->postAllowFields = ['sort', 'name'];
        $this->delNotAllowed = [1];
        $this->sortOrder = 'sort asc';

        $this->selectTextField = '{name}';
        $this->selectFields = 'id,name';
        $this->selectSearch = 'name';
    }

    protected function filterWhere()
    {
        $searchData = request()->post();

        $where = [];
        if (!empty($searchData['name'])) {
            $where[] = ['name', 'like', '%' . $searchData['name'] . '%'];
        }

        return $where;
    }

    /**
     * 构建搜索
     *
     * @return void
     */
    protected function buildSearch()
    {
        $search = $this->search;

        $search->text('name', '名称', 3)->maxlength(20);
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
        $table->show('name', '名称');
        $table->show('users', '用户数');
        $table->show('description', '描述')->default('无描述');
        $table->text('sort', '排序')->autoPost()->getWrapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWrapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWrapper()->addStyle('width:180px');
        $table->sortable('id,sort');

        foreach ($data as &$d) {
            $d['__h_del__'] = $d['id'] == 1;
        }

        unset($d);

        $table->getActionbar()
            ->btnEdit()
            ->btnDelete()
            ->mapClass([
                'delete' => ['hidden' => '__h_del__'],
            ]);
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

        $this->builder()->addStyleSheet('
        .form-horizontal .control-label.permission-item
        {
            text-align : left;
        }
');

        $form->text('name', '名称')->maxlength(25)->required();
        $form->textarea('description', '描述')->maxlength(100);
        $form->text('sort', '排序')->required()->default(1);
        $form->tags('tags', '标签');

        if ($isEdit) {
            $form->show('create_time', '添加时间');
            $form->show('update_time', '修改时间');
        }
        if ($isEdit && $data['id'] == 1) {
            $form->raw('menus', '菜单')->value('<label class="label label-warning">拥有所有菜单</label>');
            $form->raw('permission', '权限')->value('<label class="label label-warning">拥有所有权限</label>');
        } else {

            $menuIds = [];
            if ($isEdit) {
                $menuIds = $this->roleMenuModel->where(['role_id' => $data['id']])->column('menu_id');
            } else {
                $menuIds = $this->menuModel->where(['parent_id' => 0, 'url' => '#'])->column('id');
            }

            $form->checkbox('menus', '菜单')->required()->optionsData($this->menuModel->where(['parent_id' => 0, 'url' => '#'])->select(), 'title')->default($menuIds)->checkallBtn('全部菜单');

            $form->raw('permission', '权限')->required()->value('<label class="label label-info">请选择权限：</label><small> 若权限显示不全，请到【权限设置】页面刷新</small>');

            $tree = $this->menuModel->buildList();

            $perIds = [];
            if ($isEdit) {
                $perIds = $this->rolePermModel->where(['role_id' => $data['id']])->column('permission_id');
            }

            foreach ($tree as $tr) {
                if ($tr['parent_id'] == '0') {
                    $form->divider('', '', 12)->value('<h4><label class="label label-secondary">' . $tr['title'] . '</label></h4>')->size(0, 12)->showLabel(false);
                } else if ($tr['url'] == '#') {
                    $form->raw('title', $tr['title_show'])
                        ->labelClass('permission-item');
                }

                $controllerPerm = $this->permModel->where(['url' => $tr['url']])->find();
                if (!$controllerPerm) {
                    continue;
                }

                $permissions = $this->permModel->where([
                    ['controller', 'eq', $controllerPerm['controller']],
                    ['action', 'neq', '#'],
                ])->order('action desc')->field('id,action_name,url')->select();

                $form->checkbox("permissions" . $controllerPerm['id'], $tr['title_show'])
                    ->default($perIds)
                    ->labelClass('permission-item')
                    ->optionsData($permissions, 'action_name')
                    ->inline()
                    ->checkallBtn();
            }
        }
    }

    private function save($id = 0)
    {
        $data = request()->only([
            'name',
            'description',
            'sort',
            'tags',
        ], 'post');

        $result = $this->validate($data, [
            'name|名称' => 'require',
            'sort|排序' => 'require|number',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        if ($id) {
            $res = $this->dataModel->save($data, [$this->getPk() => $id]);
        } else {
            $res = $this->dataModel->save($data);
            if ($res) {
                $id = $this->dataModel->id;
            }
        }

        if (!$res) {
            $this->error('保存失败');
        }

        if ($id > 1) {
            $this->saveMenus($id);
            $this->savePermissions($id);
        }

        return $this->builder()->layer()->closeRefresh(1, '保存成功');
    }

    private function saveMenus($roleId)
    {
        $menuIds = array_filter(request()->post('menus/a'), 'strlen');

        if (empty($menuIds)) {
            $menuIds = [];
        }

        $allIds = $this->roleMenuModel->where(['role_id' => $roleId])->column('id');
        $existIds = [];

        Db::startTrans();

        foreach ($menuIds as $id) {
            $exist = $this->roleMenuModel->where(['menu_id' => $id, 'role_id' => $roleId])->find();

            if ($exist) {
                $existIds[] = $exist['id'];
                continue;
            } else {
                $this->roleMenuModel->create([
                    'menu_id' => $id,
                    'role_id' => $roleId,
                ]);
            }
        }

        $delIds = array_diff($allIds, $existIds);

        if (!empty($delIds)) {
            $this->roleMenuModel->destroy(array_values($delIds));
        }

        Db::commit();
    }

    private function savePermissions($roleId)
    {
        $data = request()->post();

        $allIds = $this->rolePermModel->where(['role_id' => $roleId])->column('id');
        $existIds = [];

        Db::startTrans();

        $tree = $this->menuModel->buildList();

        foreach ($tree as $tr) {

            $controllerPerm = $this->permModel->where(['url' => $tr['url']])->find();
            if (!$controllerPerm) {
                continue;
            }

            if (isset($data['permissions' . $controllerPerm['id']])) {

                $saveIds = array_filter($data['permissions' . $controllerPerm['id']], 'strlen');

                foreach ($saveIds as $id) {
                    $exist = $this->rolePermModel->where(['permission_id' => $id, 'role_id' => $roleId])->find();

                    if ($exist) {
                        $existIds[] = $exist['id'];
                        continue;
                    } else {
                        $this->rolePermModel->create([
                            'permission_id' => $id,
                            'role_id' => $roleId,
                            'controller_id' => $controllerPerm['id'],
                        ]);
                    }
                }
            }
        }

        $delIds = array_diff($allIds, $existIds);

        if (!empty($delIds)) {
            $this->rolePermModel->destroy(array_values($delIds));
        }

        Db::commit();
    }
}

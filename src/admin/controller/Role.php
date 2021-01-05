<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\facade\Db;
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
            $permissions = [];
            $controllerPermList = $this->permModel->order('controller,action')->select();

            if ($isEdit) {
                $menuIds = $this->roleMenuModel->where(['role_id' => $data['id']])->column('menu_id');
            } else {
                $menuIds = $this->menuModel->where(['parent_id' => 0, 'url' => '#'])->column('id');
            }

            $form->checkbox('menus', '菜单')->required()->optionsData($this->menuModel->where(['parent_id' => 0, 'url' => '#'])->select(), 'title')->default($menuIds)->checkallBtn('全部菜单');

            $form->raw('permission', '权限')->required()->value('<label class="label label-info">请选择权限：</label><small> 若权限显示不全，请到【权限设置】页面刷新</small>');

            $tree = $this->menuModel->getLineData();

            $perIds = [];
            if ($isEdit) {
                $perIds = $this->rolePermModel->where(['role_id' => $data['id']])->column('permission_id');
            }

            foreach ($tree as $tr) {
                if ($tr['parent_id'] == '0') {
                    $form->divider('', '', 12)->value('<h4><label class="label label-secondary">' . $tr['title'] . '</label></h4>')->size(0, 12)->showLabel(false);
                } else if ($tr['url'] == '#') {
                    $form->raw('title', $tr['__text__'])
                        ->labelClass('permission-item');
                }

                $controllerPerm = null;
                $permissions = [];

                foreach ($controllerPermList as $cprow) {
                    if ($cprow['url'] == $tr['url']) {
                        $controllerPerm = $cprow;
                        break;
                    }
                }

                if (!$controllerPerm) {
                    continue;
                }

                foreach ($controllerPermList as $cprow) {
                    if ($cprow['controller'] == $controllerPerm['controller'] && $cprow['action'] != '#') {
                        $permissions[] = $cprow;
                    }
                }

                $form->checkbox("permissions" . $controllerPerm['id'], $tr['__text__'])
                    ->default($perIds)
                    ->labelClass('permission-item')
                    ->optionsData($permissions, 'action_name')
                    ->inline()
                    ->size(2, 10)
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

        $res = 0;

        if ($id) {
            $exists = $this->dataModel->where([$this->getPk() => $id])->find();
            if ($exists) {
                $res = $exists->force()->save($data);
            }
        } else {
            $res = $this->dataModel->exists(false)->save($data);
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

        $allIds = [];

        $roleMenuList = $this->roleMenuModel->where(['role_id' => $roleId])->select();

        foreach ($roleMenuList as $rmenu) {
            $allIds[] = $rmenu['id'];
        }

        $existIds = [];

        Db::startTrans();

        $roleMenu = null;

        foreach ($menuIds as $id) {
            $roleMenu = null;

            foreach ($roleMenuList as $rmenu) {
                if ($rmenu['menu_id'] == $id) {
                    $roleMenu = $rmenu;
                    break;
                }
            }

            if ($roleMenu) {
                $existIds[] = $roleMenu['id'];
            } else {
                $roleMenu = new AdminRoleMenu;
                $roleMenu->save([
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

        $rolePermList = $this->rolePermModel->where(['role_id' => $roleId])->select();
        $controllerPermList = $this->permModel->select();

        $allIds = [];
        foreach ($rolePermList as $rprow) {
            $allIds[] = $rprow['id'];
        }

        $existIds = [];

        Db::startTrans();

        $tree = $this->menuModel->getLineData();

        $controllerPerm = null;
        $rolePerm = null;
        $saveIds = null;

        foreach ($tree as $tr) {

            $controllerPerm = null;

            foreach ($controllerPermList as $cprow) {
                if ($cprow['url'] == $tr['url']) {
                    $controllerPerm = $cprow;
                    break;
                }
            }

            if (!$controllerPerm) {
                continue;
            }

            if (isset($data['permissions' . $controllerPerm['id']])) {

                $saveIds = array_filter($data['permissions' . $controllerPerm['id']], 'strlen');

                foreach ($saveIds as $id) {
                    $rolePerm = null;
                    foreach ($rolePermList as $rprow) {
                        if ($rprow['permission_id'] == $id) {
                            $rolePerm = $rprow;
                            break;
                        }
                    }

                    if ($rolePerm) {
                        $existIds[] = $rolePerm['id'];
                    } else {
                        $rolePerm = new AdminRolePermission;
                        $rolePerm->save([
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

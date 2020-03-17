<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\Db;
use tpext\builder\common\Builder;
use tpext\builder\traits\HasBuilder;
use tpext\myadmin\admin\model\AdminMenu;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\admin\model\AdminRole;
use tpext\myadmin\admin\model\AdminRoleMenu;
use tpext\myadmin\admin\model\AdminRolePermission;

class Role extends Controller
{
    use HasBuilder;

    protected $dataModel;
    protected $permModel;
    protected $rolePermModel;
    protected $menuModel;

    protected function initialize()
    {
        $this->dataModel = new AdminRole;
        $this->permModel = new AdminPermission;
        $this->rolePermModel = new AdminRolePermission;
        $this->menuModel = new AdminMenu;
        $this->roleMenuModel = new AdminRoleMenu;

        $this->pageTitle = '用户管理';
        $this->postAllowFields = ['sort', 'name'];
        $this->delNotAllowed = [1];
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
    protected function builSearch()
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
        $table->text('sort', '排序')->autoPost()->getWapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWapper()->addStyle('width:180px');
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

        $form->hidden('id');
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

            $form->checkbox('menus', '菜单')->required()->optionsData($this->menuModel->where(['parent_id' => 0, 'url' => '#'])->select(), 'title')->default($menuIds)->size(2, 10)->checkallBtn('全部菜单');

            $form->raw('permission', '权限')->required()->value('<label class="label label-info">请选择权限：</label><small> 若权限显示不全，请到【权限设置】页面刷新</small>');

            $modControllers = $this->permModel->getControllers();

            foreach ($modControllers as $modController) {

                $form->divider('', '', 12)->value('<h4><label class="label label-secondary">' . $modController['title'] . '</label></h4>')->size(0, 12)->showLabel(false);

                foreach ($modController['controllers'] as $controller => $methods) {

                    $controllerPerm = $this->permModel->where(['controller' => $controller . '::class', 'action' => '#'])->find();

                    if (!$controllerPerm) {
                        continue;
                    }

                    if (empty($methods)) {
                        continue;
                    }

                    $options = [];

                    foreach ($methods as $method) {

                        $actionPerm = $this->permModel->where(['controller' => $controller . '::class', 'action' => '@' . $method])->find();

                        if (!$actionPerm || $actionPerm['action_type'] == 0) {
                            continue;
                        }

                        if (!$actionPerm['action_name']) {
                            $actionPerm['action_name'] = $method;
                        }

                        $options[$actionPerm['id']] = $actionPerm['action_name'];
                    }

                    $perIds = [];
                    if ($isEdit) {
                        $perIds = $this->rolePermModel->where(['controller_id' => $controllerPerm['id'], 'role_id' => $data['id']])->column('permission_id');
                    }

                    $form->checkbox("permissions" . $controllerPerm['id'], $controllerPerm['action_name'])
                        ->default($perIds)->size(2, 10)
                        ->options($options)
                        ->inline()
                        ->checkallBtn();
                }
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
            $data['update_time'] = date('Y-m-d H:i:s');
            $res = $this->dataModel->where(['id' => $id])->update($data);
        } else {
            $res = Db::name('admin_role')->insertGetId($data);
        }

        if (!$res) {
            $this->error('保存失败');
        }

        if (!$id) {
            $id = $res;
        }

        if ($id > 1) {
            $this->saveMenus($id);
            $this->savePermissions($id);
        }

        return Builder::getInstance()->layer()->closeRefresh(1, '保存成功');

    }

    private function saveMenus($roleId)
    {
        $menuIds = request()->post('menus/a');
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

        $modControllers = $this->permModel->getControllers();

        Db::startTrans();

        foreach ($modControllers as $modController) {

            foreach ($modController['controllers'] as $controller => $methods) {

                $controllerPerm = $this->permModel->where(['controller' => $controller . '::class', 'action' => '#'])->find();

                if (!$controllerPerm || empty($methods)) {
                    continue;
                }

                if (isset($data['permissions' . $controllerPerm['id']])) {

                    $saveIds = $data['permissions' . $controllerPerm['id']];

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
        }

        $delIds = array_diff($allIds, $existIds);

        if (!empty($delIds)) {
            $this->rolePermModel->destroy(array_values($delIds));
        }

        Db::commit();
    }
}

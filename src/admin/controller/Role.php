<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\facade\Db;
use tpext\builder\traits\HasBuilder;
use tpext\myadmin\common\Module;
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
        Module::getInstance()->loadLang('role');

        $this->dataModel = new AdminRole;
        $this->permModel = new AdminPermission;
        $this->rolePermModel = new AdminRolePermission;
        $this->menuModel = new AdminMenu;
        $this->roleMenuModel = new AdminRoleMenu;

        $this->pageTitle = __admin_lang('role_management');
        $this->postAllowFields = ['sort', 'name'];
        $this->delNotAllowed = [1];
        $this->sortOrder = 'sort asc';

        $this->selectTextField = '{name}';
        $this->selectFields = 'id,name';
        $this->selectSearch = 'name';
    }

    protected function filterWhere()
    {
        $searchData = request()->get();

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

        $search->text('name', '', 3)->maxlength(20);
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
        $table->show('name');
        $table->show('users', __admin_lang('users_count'));
        $table->show('description')->default(__admin_lang('no_description'));
        $table->text('sort')->autoPost()->getWrapper()->addStyle('max-width:40px');
        $table->show('create_time')->getWrapper()->addStyle('width:180px');
        $table->show('update_time')->getWrapper()->addStyle('width:180px');
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

        $form->text('name')->maxlength(25)->required();
        $form->textarea('description')->maxlength(100);
        $form->text('sort')->required()->default(1);
        $form->tags('tags');

        if ($isEdit) {
            $form->show('create_time');
            $form->show('update_time');
        }
        if ($isEdit && $data['id'] == 1) {
            $form->raw('menus', __admin_lang('menu'))->value('<label class="label label-warning">' . __admin_lang('has_all_menus') . '</label>');
            $form->raw('permission', __admin_lang('permission'))->value('<label class="label label-warning">' . __admin_lang('has_all_permissions') . '</label>');
        } else {

            $menuIds = [];
            $permissions = [];
            $urlBase = '';
            $ids = [];
            $controllerPermList = $this->permModel->order('controller,action')->select();
            $menuUlrs = [];
            $allPerIds = [];
            $perIds = [];

            if ($isEdit) {
                $menuIds = $this->roleMenuModel->where(['role_id' => $data['id']])->column('menu_id');
            } else {
                $menuIds = $this->menuModel->where(['parent_id' => 0, 'url' => '#'])->column('id');
            }

            $form->checkbox('menus', __admin_lang('menu'))->required()->optionsData($this->menuModel->where(['parent_id' => 0, 'url' => '#'])->select(), 'title')->default($menuIds)->checkallBtn(__admin_lang('all_menus'));

            $form->raw('permission', __admin_lang('permission'))->required()->value('<label class="label label-info">' . __admin_lang('please_select_permissions') . '</label><small> ' . __admin_lang('permissions_incomplete') . '</small>');

            $tree = $this->menuModel->getLineData();

            if ($isEdit) {
                $allPerIds = $this->rolePermModel->where(['role_id' => $data['id']])->column('permission_id');
            }

            foreach ($tree as $tr) {
                $menuUlrs[] = $tr['url'];
            }

            foreach ($tree as $tr) {
                if ($tr['parent_id'] == '0') {
                    $form->divider('', '', 12)->value('<h4><label class="label label-secondary">' . $tr['title'] . '</label></h4>')->size(0, 12)->showLabel(false);
                } else if ($tr['url'] == '#') {
                    $form->raw('title' . $tr['id'], $tr['__text__'])
                        ->labelClass('permission-item');
                }

                $controllerPerm = null;
                $permissions = [];
                $perIds = [];

                foreach ($controllerPermList as $cprow) {
                    if ($cprow['url'] == $tr['url']) {
                        $ids[] = $cprow['id'];
                        $controllerPerm = $cprow;
                        break;
                    }
                }

                if (!$controllerPerm) {
                    continue;
                }

                $urlBase = preg_replace('/^(.+?\/)\w+$/', '$1', $controllerPerm['url']);

                foreach ($controllerPermList as $cprow) {

                    if ($cprow['controller'] == $controllerPerm['controller'] || ($cprow['url'] && strstr($cprow['url'], $urlBase))) {
                        $ids[] = $cprow['id'];
                        if ($cprow['action'] == '#') {
                            continue;
                        }
                        if (in_array($cprow['url'], $menuUlrs) && $cprow['url'] != $tr['url']) {
                            continue;
                        }
                        if (isset($cprow['c']) && $cprow['url'] != $tr['url']) {
                            continue;
                        }
                        $permissions[] = $cprow;
                        if (in_array($cprow['id'], $allPerIds)) {
                            $perIds[] = $cprow['id'];
                        }
                        $cprow['c'] = 1;
                    }
                }

                $form->checkbox("permissions" . $controllerPerm['id'], $tr['parent_id'] ? $tr['__text__'] : str_repeat('&nbsp;', 5) . '├─' . $tr['title'])
                    ->default($perIds)
                    ->labelClass('permission-item')
                    ->optionsData($permissions, 'action_name')
                    ->inline()
                    ->size(2, 10)
                    ->checkallBtn(count($permissions) > 1 ? __admin_lang('select_all') : '');
            }

            $otherPermList = $this->permModel->where('id', 'not in', $ids)->order('controller,action')->select();

            if (count($otherPermList)) {
                $form->divider('', '', 12)->value('<h4><label class="label label-secondary">' . __admin_lang('other') . '</label></h4>')->size(0, 12)->showLabel(false);

                foreach ($otherPermList as $cprow) {
                    if ($cprow['action'] == '#') {
                        $permissions = [];
                        $perIds = [];
                        foreach ($otherPermList as $cprow_) {
                            if ($cprow_['action'] == '#') {
                                continue;
                            }
                            if ($cprow_['controller'] == $cprow['controller'] || ($urlBase && $cprow_['url'] && strstr($cprow_['url'], $urlBase))) {
                                $permissions[] = $cprow_;
                                if (in_array($cprow_['id'], $allPerIds)) {
                                    $perIds[] = $cprow_['id'];
                                }
                                if (!$urlBase) {
                                    $urlBase = preg_replace('/^(.+?\/)\w+$/', '$1', $cprow_['url']);
                                }
                            }
                        }

                        if (count($permissions)) {
                            $form->checkbox("permissions" . $cprow['id'], str_repeat('&nbsp;', 5) . '├─' . $cprow['action_name'])
                                ->default($perIds)
                                ->labelClass('permission-item')
                                ->optionsData($permissions, 'action_name')
                                ->inline()
                                ->size(2, 10)
                                ->checkallBtn();
                        }
                    }
                }
            }
        }
    }

    protected function save($id = 0)
    {
        $data = request()->only([
            'name',
            'description',
            'sort',
            'tags',
        ], 'post');

        $result = $this->validate($data, [
            'name|' . __admin_lang('name') => 'require',
            'sort|' . __admin_lang('sort') => 'require|number',
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
            $id = $this->dataModel['id'] ?? 0;
        }

        if (!$res) {
            $this->error(__admin_lang('save_failed'));
        }

        if ($id > 1) {
            $this->saveMenus($id);
            $this->savePermissions($id);
        }

        return $this->builder()->layer()->closeRefresh(1, __admin_lang('save_success'));
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

        $rolePerm = null;
        $saveIds = null;

        foreach ($controllerPermList as $controllerPerm) {
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

<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\Db;
use tpext\builder\common\Builder;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\admin\model\AdminRole;
use tpext\myadmin\admin\model\AdminRolePermission;

class Role extends Controller
{
    protected $dataModel;
    protected $permModel;
    protected $rolePermModel;

    protected function initialize()
    {
        $this->dataModel = new AdminRole;
        $this->permModel = new AdminPermission;
        $this->rolePermModel = new AdminRolePermission;
    }

    public function index()
    {
        $builder = Builder::getInstance('权限管理', '列表');

        $form = $builder->form();

        $form->text('name', '名称', 3)->maxlength(20);

        $table = $builder->table();

        $table->searchForm($form);

        $table->show('id', 'ID');
        $table->show('name', '名称');
        $table->show('description', '描述')->default('无描述');
        $table->text('sort', '排序')->autoPost()->getWapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWapper()->addStyle('width:180px');

        $pagezise = 10;

        $page = input('__page__/d', 1);

        $page = $page < 1 ? 1 : $page;

        $searchData = request()->only([
            'name',
        ], 'post');

        $where = [];

        if (!empty($searchData['name'])) {
            $where[] = ['name', 'like', '%' . $searchData['name'] . '%'];
        }

        $sortOrder = 'id asc';

        $sort = input('__sort__');
        if ($sort) {
            $arr = explode(':', $sort);
            if (count($arr) == 2) {
                $sortOrder = implode(' ', $arr);
            }
        }

        $data = $this->dataModel->where($where)->order($sortOrder)->limit(($page - 1) * $pagezise, $pagezise)->select();

        foreach ($data as &$d) {
            $d['__h_del__'] = $d['id'] == 1;
        }

        $table->data($data);
        $table->paginator($this->dataModel->where($where)->count(), $pagezise);

        $table->getActionbar()->mapClass([
            'delete' => ['hidden' => '__h_del__'],
        ]);

        if (request()->isAjax()) {
            return $table->partial()->render(false);
        }

        return $builder->render();
    }

    public function add()
    {
        if (request()->isPost()) {
            return $this->save();
        } else {
            return $this->form('添加');
        }
    }

    public function edit($id)
    {
        if (request()->isPost()) {
            return $this->save($id);
        } else {
            $data = $this->dataModel->get($id);
            if (!$data) {
                $this->error('数据不存在');
            }

            return $this->form('编辑', $data);
        }
    }

    private function save($id = 0)
    {
        $data = request()->only([
            'name',
            'description',
            'sort',
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
            $res = $this->dataModel->create($data);
        }

        if (!$res) {
            $this->error('保存失败');
        }

        if (!$id) {
            $id = $res;
        }

        $this->savePermissions($id);

        return Builder::getInstance()->layer()->closeRefresh(1, '保存成功');

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

                $controllerPerm = $this->permModel->where(['controller' => $controller, 'action' => '#'])->find();

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

    private function form($title, $data = [])
    {
        $isEdit = isset($data['id']);

        $builder = Builder::getInstance('权限管理', $title);

        $form = $builder->form();

        $form->hidden('id');
        $form->text('name', '名称')->maxlength(25)->required();
        $form->textarea('description', '描述')->maxlength(100);
        $form->text('sort', '排序')->required()->default(1);

        if ($isEdit) {
            $form->show('create_time', '添加时间');
            $form->show('update_time', '修改时间');
        }
        if ($isEdit && $data['id'] == 1) {
            $form->raw('permission', '权限')->value('<label class="label label-warning">拥有所有权限</label>');
        } else {

            $form->raw('permission', '权限')->required()->value('<label class="label label-info">请选择权限：</label><small> 若权限显示不全，请到【权限设置】页面刷新</small>');

            $modControllers = $this->permModel->getControllers();

            foreach ($modControllers as $modController) {

                $form->divider('', '', 12)->value('<h4><label class="label label-secondary">' . $modController['title'] . '</label></h4>')->size(0, 12)->showLabel(false);

                foreach ($modController['controllers'] as $controller => $methods) {

                    $controllerPerm = $this->permModel->where(['controller' => $controller, 'action' => '#'])->find();

                    if (!$controllerPerm) {
                        continue;
                    }

                    if (empty($methods)) {
                        continue;
                    }

                    $options = [];

                    foreach ($methods as $method) {

                        $actionPerm = $this->permModel->where(['controller' => $controller, 'action' => '@' . $method])->find();

                        if (!$actionPerm || $actionPerm['action_type'] == 0) {
                            continue;
                        }

                        if (!$actionPerm['action_name']) {
                            $actionPerm['action_name'] = $method;
                        }

                        $options[$actionPerm['id']] = $actionPerm['action_name'];
                    }

                    $ids = [];
                    if ($isEdit) {
                        $ids = $this->rolePermModel->where(['controller_id' => $controllerPerm['id'], 'role_id' => $data['id']])->column('permission_id');
                    }

                    $form->checkbox("permissions" . $controllerPerm['id'], $controllerPerm['action_name'])
                        ->default($ids)->size(2, 10)
                        ->options($options)
                        ->inline()
                        ->checkallBtn();
                }
            }
        }

        $form->fill($data);

        return $builder->render();
    }

    public function autopost()
    {
        $id = input('id/d', '');
        $name = input('name', '');
        $value = input('value', '');

        if (empty($id) || empty($name)) {
            $this->error('参数有误');
        }

        $allow = ['sort', 'name'];

        if (!in_array($name, $allow)) {
            $this->error('不允许的操作');
        }

        $res = $this->dataModel->where(['id' => $id])->update([$name => $value]);

        if ($res) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }

    public function delete()
    {
        $ids = input('ids');

        $ids = array_filter(explode(',', $ids), 'strlen');

        if (empty($ids)) {
            $this->error('参数有误');
        }

        $res = 0;

        foreach ($ids as $id) {
            if ($id == 1) {
                continue;
            }
            if ($this->dataModel->destroy($id)) {
                $res += 1;
            }
        }

        if ($res) {
            $this->success('成功删除' . $res . '条数据');
        } else {
            $this->error('删除失败');
        }
    }
}

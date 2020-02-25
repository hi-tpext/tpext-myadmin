<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\admin\model\AdminRole;

class Role extends Controller
{
    protected $dataModel;
    protected $pemModel;

    public function __construct()
    {
        $this->dataModel = new AdminRole;
        $this->pemModel = new AdminPermission;
    }

    public function index()
    {
        $builder = Builder::getInstance('权限管理', '列表');

        $table = $builder->table();

        $table->field('id', 'ID');
        $table->field('name', '名称');
        $table->field('description', '描述')->default('无描述');
        $table->text('order', '排序')->autoPost()->getWapper()->addStyle('max-width:80px');
        $table->field('create_time', '添加时间')->getWapper()->addStyle('width:180px');
        $table->field('update_time', '修改时间')->getWapper()->addStyle('width:180px');

        $pagezise = 10;

        $page = input('__page__/d', 1);

        $data = $this->dataModel->order('order')->limit(($page - 1) * $pagezise, $pagezise)->select();
        $table->data($data);
        $table->paginator($this->dataModel->count(), $pagezise);
        $table->getToolbar()
            ->btnAdd()
            ->btnDelete();

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

            return $this->form('编辑', $this->dataModel->get($id));
        }
    }

    private function save($id = 0)
    {
        $data = request()->only([
            'name',
            'description',
            'order',
        ]);

        $result = $this->validate($data, [
            'name|名称' => 'require',
        ]);

        if (true !== $result) {

            $this->error($result);
        }

        if ($id) {
            $res = $this->dataModel->where(['id' => $id])->update($data);
        } else {
            $res = $this->dataModel->create($data);
        }

        if (!$res) {
            $this->error('保存失败');
        }

        return Builder::getInstance()->layer()->closeRefresh(1, '保存成功');

    }

    private function form($title, $data = [])
    {
        $builder = Builder::getInstance('权限管理', $title);

        $form = $builder->form();

        $form->hidden('id');
        $form->text('name', '名称')->maxlength(25)->required();
        $form->textarea('description', '名称')->maxlength(100);
        $form->number('order', '排序')->default(1);
        if (isset($data['id'])) {
            $form->raw('create_time', '添加时间');
            $form->raw('update_time', '修改时间');
        }

        $modControllers = $this->pemModel->getControllers();

        foreach ($modControllers as $key => $modController) {

            $form->html('', '', 2);
            $form->checkbox($key, '', 10)->options([1 => $modController['title']])->showLabel(false);

            foreach ($modController['controllers'] as $controller => $methods) {

                $form->html('', '', 2);

                $form->checkbox($controller, '', 9)->options([1 => $this->getPerName($controller, '————')])->showLabel(false)->getWapper()->attr('style="margin-left:20px;"');

                $options = [];

                foreach ($methods as $method) {

                    $options[$controller . '@' . $method] = $this->getPerName($controller, '@' . $method);
                }

                $form->html('', '', 2);
                $form->checkbox($controller, '', 9)->options($options)->showLabel(false)->size(0, 12)->inline(true)->getWapper()->attr('style="margin-left:40px;"');
            }
        }

        $form->fill($data);

        return $builder->render();
    }

    protected function getPerName($controller, $action)
    {
        $perm = $this->pemModel->where(['controller' => $controller, 'action' => $action])->find();

        if ($perm && $perm['action_name']) {
            return $perm['action_name'];
        }

        return $controller . $action;
    }

    public function autopost()
    {
        $id = input('id/d', '');
        $name = input('name', '');
        $value = input('value', '');

        if (empty($id) || empty($name)) {
            $this->error('参数有误');
        }

        $allow = ['order', 'name'];

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

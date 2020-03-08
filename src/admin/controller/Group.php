<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;
use tpext\myadmin\admin\model\AdminGroup;

class Group extends Controller
{
    protected $dataModel;

    protected function initialize()
    {
        $this->dataModel = new AdminGroup;
    }

    public function index()
    {
        $builder = Builder::getInstance('分组管理', '列表');

        $table = $builder->table();
        $table->show('id', 'ID');
        $table->raw('title_show', '名称')->getWapper()->addStyle('text-align:left;');
        $table->show('users', '用户数');
        $table->show('description', '描述')->default('无描述');
        $table->text('name', '名称')->autoPost()->getWapper()->addStyle('max-width:80px');
        $table->text('sort', '排序')->autoPost()->getWapper()->addStyle('max-width:40px');
        $table->show('create_time', '添加时间')->getWapper()->addStyle('width:180px');
        $table->show('update_time', '修改时间')->getWapper()->addStyle('width:180px');

        $table->sortable([]);

        $data = $this->dataModel->buildList(0, 0);
        $table->data($data);

        if (request()->isAjax()) {
            return $table->partial()->render();
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

    private function form($title, $data = [])
    {
        $isEdit = isset($data['id']);

        $builder = Builder::getInstance('分组管理', $title);

        $form = $builder->form();

        $tree = [0 => '顶级分组'];

        $tree += $this->dataModel->buildTree(0, 0, $isEdit ? $data['id'] : 0); //数组合并不要用 array_merge , 会重排数组键 ，作为options导致bug

        $form->text('name', '名称')->required();
        $form->select('parent_id', '上级')->required()->options($tree);
        $form->tags('tags', '标签');
        $form->text('sort', '排序')->default(1)->required();

        if ($isEdit) {
            $form->show('create_time', '添加时间');
            $form->show('update_time', '修改时间');
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

        $allow = ['name', 'sort'];

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
                $this->dataModel->where(['parent_id' => $id])->update(['parent_id' => 0]);
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

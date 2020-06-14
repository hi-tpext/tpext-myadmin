<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\Loader;
use tpext\builder\common\Builder;
use tpext\myadmin\admin\model\AdminPermission;

/**
 * Undocumented class
 * @title 权限管理 
 */
class Permission extends Controller
{
    protected $dataModel;

    protected function initialize()
    {
        $this->dataModel = new AdminPermission;
    }

    public function index()
    {
        $modControllers = $this->dataModel->getControllers();

        $data = [];

        $builder = Builder::getInstance('权限管理', '动作设置');

        $table = $builder->table();

        $table->useCheckbox(false);

        $reflectionClass = null;

        $contrl = null;
        $controllerName = null;
        $controllerDoc = null;
        $action = null;
        $actionName = null;
        $actionDoc = null;

        $actionNames = [
            'index' => '列表',
            'list' => '列表',
            'add' => '添加',
            'create' => '新建',
            'edit' => '修改',
            'view' => '查看',
            'update' => '更新',
            'delete' => '删除',
            'enable' => '启用',
            'disable' => '禁用',
            'status' => '状态',
            'install' => '安装',
            'uninstall' => '卸载',
            'login' => '登录',
            'logout' => '注销',
            'dashbord' => '仪表盘',
            'upload' => '上传',
            'download' => '下载',
            'autopost' => '字段编辑',
            'import' => '导入',
            'export' => '导出',
            'welcom' => '欢迎',
        ];

        foreach ($modControllers as $key => $modController) {

            $row = [
                'id' => $key,
                'controller' => '<lable class="label label-success">' . $modController['title'] . '<label/>',
                'action' => '',
                'url' => '',
                '_url' => '',
                'action_name' => '',
                'action_type' => '',
            ];

            $data[] = $row;

            if (empty($modController['controllers'])) {
                $data[] =  [
                    'id' => $key . '_empty',
                    'controller' => '<lable class="label label-default">无控制器～<label/>',
                    'action' => '#',
                    'url' => '--',
                    '_url' => '--',
                    'action_name' => '',
                    'action_type' => '',
                ];
                continue;
            }

            foreach ($modController['controllers'] as $controller => $info) {

                $controllerName = $contrl = preg_replace('/.+?\\\controller\\\(\w+)$/', '$1', $controller);

                $reflectionClass = $info['reflection'];

                $controllerDoc = $reflectionClass->getDocComment();

                if ($controllerDoc && preg_match('/@title\s(.+?)[\r\n]/i', $controllerDoc, $cname)) {
                    $controllerName = trim($cname[1]);
                }

                $row_ = array_merge($row, ['controller' => $controller . '::class', 'action_name' => $controllerName, 'action_type' => '', 'action' => '#']);

                $data[] = $row_;

                if (empty($info['methods'])) {
                    continue;
                }

                foreach ($info['methods'] as $method) {

                    $actionName = $action = strtolower($method->name);

                    $url = url('/admin/' . Loader::parseName($contrl) . '/' . $action, '', false);

                    if (in_array($url, ['/admin/index/index', '/admin/index/denied', '/admin/index/logout', '/admin/index/login'])) {
                        continue;
                    }

                    $actionDoc = $method->getDocComment();

                    if (isset($actionNames[$action])) {
                        $actionName = $actionNames[$action];
                    } else if ($actionDoc && preg_match('/@title\s(.+?)[\r\n]/i', $actionDoc, $aname)) {
                        $actionName = trim($aname[1]);
                    }

                    $row__ = array_merge($row_, ['action' => '@' . $action, '_url' => '<a target="_blank" href="' . $url . '">' . $url . '</a>', 'url' => $url, 'action_name' => $actionName, 'action_type' => 1]);

                    $data[] = $row__;
                }
            }
        }

        unset($reflectionClass);

        $allIds = $this->dataModel->column('id');
        $activeIds = [];

        foreach ($data as &$row) {
            if ($row['action'] != '') {
                $perm = $this->dataModel->where(['controller' => $row['controller'], 'action' => $row['action']])->find();
                if ($perm) {
                    $row['action_type'] = $perm['action_type'];
                    $row['action_name'] = $perm['action_name'] ? $perm['action_name'] : $row['action_name'];
                    $row['id'] = $perm['id'];
                    $activeIds[] = $perm['id'];
                } else {
                    $row['id'] = $this->dataModel->create([
                        'module_name' => $modController['title'],
                        'controller' => $row['controller'],
                        'action' => $row['action'],
                        'url' => $row['url'],
                        'action_type' => $row['action_type'],
                        'action_name' => $row['action_name'],
                    ]);
                }
            }

            if ($row['action'] == '' || $row['action'] == '#') {
                $row['action_type'] = '-1';
            }
        }

        $delIds = array_diff($allIds, $activeIds);

        if (!empty($delIds)) {
            $this->dataModel->destroy(array_values($delIds));
        }

        $table->field('controller', '控制器');
        $table->field('action', '动作');
        $table->field('_url', 'url链接');
        $table->text('action_name', '动作名称')->mapClassWhen([''], 'hidden')->autoPost('', false)->getWrapper()->addStyle('max-width:100px');
        $table->switchBtn('action_type', '是权限')->autoPost('', false)->mapClassWhen(['-1'], 'hidden')->getWrapper()->addStyle('max-width:80px');

        $table->data($data);
        $table->getToolbar()->btnRefresh();
        $table->useActionbar(false);

        if (request()->isAjax()) {
            return $table->partial()->render();
        }

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

        $allow = ['action_name', 'action_type'];

        if (!in_array($name, $allow)) {
            $this->error('不允许的操作');
        }

        $res = $this->dataModel->update([$name => $value], ['id' => $id]);

        if ($res) {
            $this->success('修改成功');
        } else {
            $this->error('修改失败');
        }
    }
}

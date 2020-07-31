<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use think\Loader;
use tpext\builder\traits\actions\HasAutopost;
use tpext\builder\traits\actions\HasBase;
use tpext\builder\traits\actions\HasIndex;
use tpext\myadmin\admin\model\AdminPermission;

/**
 * Undocumented class
 * @title 权限设置
 */
class Permission extends Controller
{
    use HasBase;
    use HasIndex;
    use HasAutopost;

    /**
     * Undocumented variable
     *
     * @var AdminPermission
     */
    protected $dataModel;

    protected function initialize()
    {
        $this->dataModel = new AdminPermission;
        $this->pageTitle = '权限设置';
    }

    protected function buildDataList()
    {
        $table = $this->table;

        $data = [];

        $reflectionClass = null;

        $contrl = null;
        $controllerName = null;
        $controllerDoc = null;
        $action = null;
        $actionName = null;
        $actionDoc = null;
        $arr = null;

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
            'selectpage' => '下拉选择',
        ];

        $modControllers = $this->dataModel->getControllers();

        foreach ($modControllers as $key => $modController) {

            $row = [
                'id' => $key,
                'controller' => '<label class="label label-success">' . $modController['title'] . '</label>',
                'action' => '',
                'url' => '',
                'action_name' => '',
                'action_type' => '',
            ];

            $data[] = $row;

            if (empty($modController['controllers'])) {
                $data[] = [
                    'id' => $key . '_empty',
                    'controller' => '<label class="label label-default">无控制器～</label>',
                    'action' => '#',
                    'url' => '--',
                    'action_name' => '',
                    'action_type' => '',
                ];
                continue;
            }

            foreach ($modController['controllers'] as $controller => $info) {
                $contrl = preg_replace('/.+?\\\controller\\\(.+)$/', '$1', $controller);
                if (strpos($contrl, '\\') !== false) {
                    $arr = explode('\\', $contrl);
                    $controllerName = $arr[1];
                    $contrl = $arr[0] . '/' . Loader::parseName($arr[1]);
                } else {
                    $controllerName = $contrl;
                }

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

                    $url = url('/admin/' . $contrl . '/' . $action, '', false);

                    if (in_array($url, ['/admin/index/index', '/admin/index/denied', '/admin/index/logout', '/admin/index/login'])) {
                        continue;
                    }

                    $actionDoc = $method->getDocComment();

                    if (isset($actionNames[$action])) {
                        $actionName = $actionNames[$action];
                    } else if ($actionDoc && preg_match('/@title\s(.+?)[\r\n]/i', $actionDoc, $aname)) {
                        $actionName = trim($aname[1]);
                    }

                    $row__ = array_merge($row_, ['action' => '@' . $action, 'url' => $url, 'action_name' => $actionName, 'action_type' => 1]);

                    $data[] = $row__;
                }
            }
        }

        unset($reflectionClass);

        $allIds = $this->dataModel->column('id');
        $activeIds = [];
        $perm = null;
        foreach ($data as &$row) {
            if ($row['action'] != '') {
                $perm = $this->dataModel->where(['controller' => $row['controller'], 'action' => $row['action']])->find();
                if ($perm) {
                    $row['action_type'] = $perm['action_type'];
                    $row['action_name'] = $perm['action_name'] ? $perm['action_name'] : $row['action_name'];
                    $row['id'] = $perm['id'];
                    $activeIds[] = $perm['id'];
                } else {
                    $res = $this->dataModel->create([
                        'module_name' => $modController['title'],
                        'controller' => $row['controller'],
                        'action' => $row['action'],
                        'url' => $row['url'],
                        'action_type' => $row['action_type'],
                        'action_name' => $row['action_name'],
                    ]);
                    if ($res) {
                        $perm = $this->dataModel->where(['controller' => $row['controller'], 'action' => $row['action']])->find();
                        $row['id'] = $perm ? $perm['id'] : $row['id'];
                    }
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

        if ($this->isExporting) {
            $__ids__ = input('post.__ids__');
            if (!empty($__ids__)) {
                $ids = explode(',', $__ids__);
                $newd = [];
                foreach ($data as $d) {
                    if (in_array($d['id'], $ids)) {
                        $newd[] = $d;
                    }
                }
                $data = $newd;
            }
        }

        $this->buildTable($data);
        $table->data($data);

        return $data;
    }

    /**
     * 构建表格
     *
     * @return void
     */
    protected function buildTable(&$data = [])
    {
        $table = $this->table;

        $table->field('controller', '控制器');
        $table->field('action', '动作');
        $table->field('url', 'url链接')->to('<a target="_blank" href="{val}">{val}</a>');
        $table->text('action_name', '动作名称')->mapClassWhen([''], 'hidden')->autoPost('', false)->getWrapper()->addStyle('max-width:100px');
        $table->switchBtn('action_type', '是权限')->autoPost('', false)->mapClassWhen(['-1'], 'hidden')->getWrapper()->addStyle('max-width:80px');

        $table->data($data);
        $table->getToolbar()->btnRefresh();
        $table->useActionbar(false);
        $table->useCheckbox(false);
    }
}

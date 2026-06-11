<?php

namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\traits\actions\HasAutopost;
use tpext\builder\traits\actions\HasBase;
use tpext\builder\traits\actions\HasIndex;
use tpext\myadmin\admin\model\AdminPermission;
use tpext\myadmin\common\Module;

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
        Module::getInstance()->loadLang('permission');

        $this->dataModel = new AdminPermission;
        $this->pageTitle = __admin_lang('permission_settings');
        $this->pagesize = 9999; //不产生分页
    }

    protected function buildDataList($where = [], $sortOrder = '', $page = 1, &$total = -1)
    {
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
            'index' => __admin_lang('list_view'),
            'list' => __admin_lang('list_view'),
            'add' => __admin_lang('add'),
            'create' => __admin_lang('create'),
            'edit' => __admin_lang('edit'),
            'view' => __admin_lang('view'),
            'update' => __admin_lang('update'),
            'delete' => __admin_lang('delete'),
            'enable' => __admin_lang('enable'),
            'disable' => __admin_lang('disable'),
            'status' => __admin_lang('status'),
            'install' => __admin_lang('install'),
            'uninstall' => __admin_lang('uninstall'),
            'login' => __admin_lang('login'),
            'logout' => __admin_lang('logout'),
            'dashbord' => __admin_lang('dashboard'),
            'upload' => __admin_lang('upload'),
            'download' => __admin_lang('download'),
            'autopost' => __admin_lang('field_edit'),
            'import' => __admin_lang('import'),
            'export' => __admin_lang('export'),
            'welcom' => __admin_lang('welcome'),
            'selectpage' => __admin_lang('select_dropdown'),
            'upgrade' => __admin_lang('upgrade'),
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
                'module_name' => $modController['title'],
            ];

            $data[] = $row;

            if (empty($modController['controllers'])) {
                $data[] = [
                    'id' => $key . '_empty',
                    'controller' => '<label class="label label-default">' . __admin_lang('no_controllers') . '</label>',
                    'action' => '#',
                    'url' => '--',
                    'action_name' => '',
                    'action_type' => '',
                    'module_name' => $modController['title'],
                ];
                continue;
            }

            foreach ($modController['controllers'] as $controller => $info) {
                $contrl = preg_replace('/.+?\\\controller\\\(.+)$/', '$1', $controller);
                if (strpos($contrl, '\\') !== false) {
                    $arr = explode('\\', $contrl);
                    $controllerName = $arr[1];
                    $contrl = $arr[0] . '/' . $arr[1];
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

                    $url = url('/admin/' . $contrl . '/' . $action, [], false)->__toString();

                    if (in_array($url, ['/admin/index/index', '/admin/index/denied', '/admin/index/logout', '/admin/index/login', '/admin/index/captcha'])) {
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

        $allIds = [];
        $activeIds = [];
        $perm = null;

        $permissionList = $this->dataModel->select();

        foreach ($permissionList as $prow) {
            $allIds[] = $prow['id'];
        }

        foreach ($data as &$row) {
            $perm = null;
            if ($row['action'] != '') {

                foreach ($permissionList as $prow) {
                    if ($prow['controller'] == $row['controller'] && $prow['action'] == $row['action']) {
                        $perm = $prow;
                        break;
                    }
                }

                if ($perm) {
                    $row['action_type'] = $perm['action_type'];
                    $row['action_name'] = $perm['action_name'] ? $perm['action_name'] : $row['action_name'];
                    $row['id'] = $perm['id'];
                    $activeIds[] = $perm['id'];
                } else {
                    $perm = new AdminPermission;
                    $res = $perm->save([
                        'module_name' => $row['module_name'],
                        'controller' => $row['controller'],
                        'action' => $row['action'],
                        'url' => $row['url'],
                        'action_type' => $row['action_type'],
                        'action_name' => $row['action_name'],
                    ]);
                    if ($res) {
                        $row['id'] = $perm['id'];
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

        $total = count($data);

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

        $table->field('controller');
        $table->field('action');
        $table->field('url', __admin_lang('url_link'))->to('<a target="_blank" href="{val}">{val}</a>');
        $table->text('action_name')->mapClass([''], 'hidden')->autoPost('', false)->getWrapper()->addStyle('max-width:100px');
        $table->switchBtn('action_type', __admin_lang('is_permission'))->autoPost('', false)->mapClass(['-1'], 'hidden')->getWrapper()->addStyle('max-width:80px');

        $table->getToolbar()->btnRefresh();
        $table->useActionbar(false);
        $table->useCheckbox(false);
    }
}

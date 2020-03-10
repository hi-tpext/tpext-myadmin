<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminUser;

class Operationlog extends Controller
{
    protected $dataModel;
    protected $roleModel;

    protected function initialize()
    {
        $this->dataModel = new AdminOperationLog;
        $this->userModel = new AdminUser;
    }

    public function index()
    {
        $builder = Builder::getInstance('操作记录', '列表');

        $table = $builder->table();

        $form = $table->getSearch();

        $form->select('user_id', '管理员', 3)->options($this->getUesrList());
        $form->text('path', '路径', 3);
        $form->radio('method', '提交方式', 3)->options(['' => '全部', 'GET' => 'get', 'POST' => 'post']);

        $table->show('id', 'ID')->getWapper();
        $table->show('username', '登录帐号');
        $table->show('name', '姓名');
        $table->show('path', '路径');
        $table->show('method', '提交方式');
        $table->show('data', '数据')->getWapper()->style('width:40%;');
        $table->show('create_time', '时间')->getWapper()->addStyle('width:180px');

        $table->getToolbar()
            ->btnDelete()
            ->btnExport()
            ->btnRefresh();

        $table->getActionbar()
            ->btnDelete();

        $pagezise = 14;

        $page = input('__page__/d', 1);

        $page = $page < 1 ? 1 : $page;

        $searchData = request()->only([
            'user_id',
            'path',
            'method',
        ], 'post');

        $where = [];

        if (!empty($searchData['user_id'])) {
            $where[] = ['user_id', 'eq', $searchData['user_id']];
        }

        if (!empty($searchData['path'])) {
            $where[] = ['path', 'like', '%' . $searchData['path'] . '%'];
        }

        if (!empty($searchData['method'])) {
            $where[] = ['method', 'eq', $searchData['method']];
        }

        $sortOrder = input('__sort__', 'id desc');

        $data = $this->dataModel->where($where)->order($sortOrder)->limit(($page - 1) * $pagezise, $pagezise)->select();

        $table->data($data);
        $table->sortOrder($sortOrder);

        $table->paginator($this->dataModel->where($where)->count(), $pagezise);

        if (request()->isAjax()) {
            return $table->partial()->render();
        }

        return $builder->render();
    }

    private function getUesrList()
    {
        $list = $this->userModel->all();
        $users = [
            '' => '请选择',
        ];

        foreach ($list as $row) {
            $users[$row['id']] = $row['name'];
        }

        return $users;
    }

    public function export()
    {
        // TODO
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . 'operation_log' . "_" . date('Y-m-d-H-i-s') . ".csv");
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');

        $header_data = ['编号', '登录帐号', '姓名', '路径', '方式', '数据', '时间'];

        foreach ($header_data as $key => $value) {
            $header_data[$key] = iconv('utf-8', 'gbk', $value);
        }
        fputcsv($fp, $header_data);

        $searchData = request()->only([
            'user_id',
            'path',
            'method',
            '__ids__',
        ], 'post');

        $where = [];

        if (!empty($searchData['__ids__'])) {
            $where[] = ['id', 'in', $searchData['__ids__']];
        } else {
            if (!empty($searchData['user_id'])) {
                $where[] = ['user_id', 'eq', $searchData['user_id']];
            }

            if (!empty($searchData['path'])) {
                $where[] = ['path', 'like', '%' . $searchData['path'] . '%'];
            }

            if (!empty($searchData['method'])) {
                $where[] = ['method', 'eq', $searchData['method']];
            }
        }

        $sortOrder = 'id desc';

        $sort = input('__sort__');
        if ($sort) {
            $arr = explode(':', $sort);
            if (count($arr) == 2) {
                $sortOrder = implode(' ', $arr);
            }
        }

        $list = $this->dataModel->where($where)->order($sortOrder)->select();

        $data = [];

        foreach ($list as $li) {
            $row = [];
            $row[] = $li['id'];
            $row[] = $li['username'];
            $row[] = $li['name'];
            $row[] = $li['path'];
            $row[] = $li['method'];
            $row[] = $li['data'];
            $row[] = $li['create_time'];
            $data[] = $row;
        }
        //来源网络
        $num = 0;
        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 10000;
        //逐行取出数据，不浪费内存
        $count = count($data);
        if ($count > 0) {
            for ($i = 0; $i < $count; $i++) {
                $num++;
                //刷新一下输出buffer，防止由于数据过多造成问题
                if ($limit == $num) {
                    ob_flush();
                    flush();
                    $num = 0;
                }
                $row = $data[$i];
                foreach ($row as $key => $value) {
                    $row[$key] = iconv('utf-8', 'gb2312', $value);
                }
                fputcsv($fp, $row);
            }
        }
        fclose($fp);
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

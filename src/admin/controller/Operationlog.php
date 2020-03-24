<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\traits\HasBuilder;
use tpext\myadmin\admin\model\AdminOperationLog;
use tpext\myadmin\admin\model\AdminUser;

class Operationlog extends Controller
{
    use HasBuilder;

    protected $dataModel;
    protected $roleModel;

    protected function initialize()
    {
        $this->dataModel = new AdminOperationLog;
        $this->userModel = new AdminUser;

        $this->pageTitle = '操作记录';
    }

    protected function filterWhere()
    {
        $searchData = request()->post();

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

        $search->select('user_id', '管理员', 3)->optionsData($this->userModel->all(), 'username');
        $search->text('path', '路径', 3);
        $search->radio('method', '提交方式', 3)->options(['' => '全部', 'GET' => 'get', 'POST' => 'post']);
    }
    /**
     * 构建表格
     *
     * @return void
     */
    protected function buildTable(&$data = [])
    {
        $table = $this->table;

        $table->show('id', 'ID')->getWapper();
        $table->show('username', '登录帐号');
        $table->show('name', '姓名');
        $table->show('path', '路径');
        $table->show('method', '提交方式');
        $table->show('ip', 'IP');
        $table->show('data', '数据')->getWapper()->style('width:40%;');
        $table->show('create_time', '时间')->getWapper()->addStyle('width:180px');

        $table->getToolbar()
            ->btnDelete()
            ->btnExport()
            ->btnRefresh();

        $table->getActionbar()
            ->btnDelete();
    }

    public function export()
    {
        // TODO
        ob_end_clean();
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename=' . 'operation_log' . "_" . date('Y-m-d-H-i-s') . ".csv");
        header('Cache-Control: max-age=0');
        $fp = fopen('php://output', 'a');

        $header_data = ['编号', '登录帐号', '姓名', '路径', '方式', 'IP', '数据', '时间'];

        foreach ($header_data as $key => $value) {
            $header_data[$key] = iconv('utf-8', 'gbk', $value);
        }
        fputcsv($fp, $header_data);

        $__ids__ = input('post.__ids__');

        $where = [];

        if (!empty($__ids__)) {
            $where[] = ['id', 'in', $__ids__];
        } else {
            $where = $this->filterWhere();
        }

        $sortOrder = input('__sort__', $this->sortOrder);

        $list = $this->dataModel->where($where)->order($sortOrder)->select();

        $data = [];

        foreach ($list as $li) {
            $row = [];
            $row[] = $li['id'];
            $row[] = $li['username'];
            $row[] = $li['name'];
            $row[] = $li['path'];
            $row[] = $li['method'];
            $row[] = $li['ip'];
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
}

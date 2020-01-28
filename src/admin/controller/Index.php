<?php
namespace tpext\myadmin\admin\controller;

use think\Controller;
use tpext\builder\common\Builder;

class Index extends Controller
{
    public function index()
    {
        return $this->fetch();
    }

    public function dashbord()
    {
        return $this->fetch();
    }

    public function login()
    {
        return $this->fetch();
    }

    public function test()
    {
        $builder = new Builder();

        $row = $builder->row();

        $row->column(6)->form();
        $row->column(6)->table();

        return $builder->render();
    }
}

<?php
namespace tpext\lyatadmin\admin\controller;

use think\Controller;

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
}

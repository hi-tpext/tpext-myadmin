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

        $form = $builder->form();

        $form->text('name', '姓名', 6, 'hell', 'width="1000"')->value('小明')->size([12, 12])->error('出错了');
        $form->text('phone', '电话', 6)->value('1234500006789')->help('帮助信息')->error('666');
        $form->textarea('note', '备注', 6)->value('大家好！')->errorClass('has-error');
        $form->html('')->value('<p style="color:red;">hello world !</p>');
        $form->divider('test', '', 6)->value('it ok');
        $form->raw('notice', '注意', 6)->value('<span style="color:green;">嘿</span>')->labelClass('nihao')->labelAttr('width="200"');
        $form->checkbox('hobi', '爱好', 6)->options([
            '1' => '游泳',
            '2' => '爬山',
        ])->inline(true)->checkAllBtn()->checked([1]);

        //$row->column(6)->table();

        return $builder->render();
    }
}

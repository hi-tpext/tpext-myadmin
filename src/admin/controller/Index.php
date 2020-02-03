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
        $builder = Builder::getInstance();

        $form = $builder->form(8);

        $form->datetimeRange('datetimeRange','时间日期范围');
        $form->dateRange('dateRange','日期范围');
        $form->timeRange('timeRange','时间范围');

        $form->text('name', '姓名', 12, 'hell', 'width="1000"')->maxlength(10)->default('小明')->befor('<span class="input-group-addon" id="basic-addon1">@</span>');
        $form->text('phone', '电话')->default('1234500006789')->readonly();
        $form->textarea('note', '备注')->default('大家好！')->maxlength(50);
        //$form->html('<p style="color:red;">hello world !</p>');
        //$form->divider('test');
        $form->raw('notice', '注意')->default('<span style="color:green;">嘿</span>')->labelClass('nihao')->labelAttr('width="200"');

        $form->checkbox('hobi', '爱好')->options([
            '1' => '游泳',
            '2' => '爬山',
        ])->default([1])->readonly();

        $form->radio('goodat', '擅长')->options([
            '1' => '写作',
            '2' => '唱歌',
        ])->default(2)->disabled();

        $form->select('eat', '吃的')->options([
            //'1' => '苹果',
            //'2' => '香蕉',
        ])->default(2)->dataUrl(url('testdata'));

        $form->select('todo', '任务')->options([
            [
                'label' => '1组',
                'options' => [
                    '1' => '吃饭',
                    '2' => '睡觉',
                ],
            ],
        ])->default(2)->readonly();

        $form->multipleSelect('todo2', '任务2')->options([
            [
                'label' => '1组',
                'options' => [
                    '1' => '吃饭',
                    '2' => '睡觉',
                ],
            ],
        ])->default([2]);

        $form->hidden('text', '24234324');

        $form->switchBtn('hellowww', 'swithc')->default(1);

        $form->tags('hjsfhd', 'tags')->default('hell,world');

        $form->datetime('datetime', '日期时间');

        $form->date('date','日期');

        $form->time('tiem','时间');

        

        //$row->column(6)->table();

        return $builder->render();
    }

    public function testdata()
    {
        return json([
            'more_url' => '',
            'data' => [
                [
                    'id' => 1,
                    'text' => '吃饭',
                ],
                [
                    'id' => 2,
                    'text' => '睡觉',
                ],
                [
                    'id' => 3,
                    'text' => '上厕所',
                ], [
                    'id' => 4,
                    'text' => '看电视',
                ], [
                    'id' => 5,
                    'text' => '玩游戏',
                ], [
                    'id' => 6,
                    'text' => '跑步',
                ], [
                    'id' => 7,
                    'text' => '爬山',
                ],
            ],
        ]);
    }
}

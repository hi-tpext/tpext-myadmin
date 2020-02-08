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

        $form = $builder->form(10);

        //$form->datetimeRange('datetimeRange','时间日期范围');
        //$form->dateRange('dateRange','日期范围');
        //$form->timeRange('timeRange','时间范围');

        $form->text('name', '姓名', 12, 'hell', 'width="1000"')->maxlength(10)->default('小明')->afterSymbol('%');
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

        /*   $form->switchBtn('hellowww', 'swithc')->default(1);

        $form->tags('hjsfhd', 'tags')->default('hell,world');

        $form->datetime('datetime', '日期时间');

        $form->date('date','日期')->timespan()->value(strtotime('-20day'));

        $form->time('tiem','时间');

        $form->color('color','颜色');

        $form->number('number','数字');

        $form->icon('icon','图标');
         */
        //$form->wangEditor('wang','wang编辑器');

        //$form->tinymce('tinymce','tinymce编辑器');

        //$form->ueditor('ueditor','ueditor编辑器');

        //$form->ckeditor('ckeditor','ckeditor编辑器');

        //$form->mdeditor('mdeditor','mdeditor编辑器');

        //$row->column(6)->table();

        //$form->rate('rate','rate');
        //$form->month('month','month');
        //$form->year('year','year');

        $form->multipleFile('multipleFile', 'multipleFile')->value('/upload/images/202002/file5e3c1b015b04d.png,/upload/images/202002/file5e3c29670e3c2.zip')->limit(3);
        //$form->file('file','file')->value('/upload/images/202002/file5e3c1b015b04d.png')->image();
        $form->image('iage', 'image')->value('/upload/images/202002/file5e3c1b015b04d.png');
        $form->rangeSlider('slider', 'slider')->default([20, 30]);

        return $builder->render();
    }

    public function test2()
    {
        $builder = Builder::getInstance('人员管理', '列表');

        $table = $builder->table(6);

        $table->text('name', '姓名');
        $table->textarea('age', '年龄');
        $table->radio('gender', '性别')->options(['1' => '男', '2' => '女'])->inline(true);
        $table->field('birthday', '生日');
        $table->field('hoby', '爱好');

        $table->data([
            ['name' => '小明', 'age' => 18, 'gender' => '1', 'birthday' => '1989-10', 'hoby' => '游泳'],
            ['name' => '小红', 'age' => 17, 'gender' => '2', 'birthday' => '1991-10', 'hoby' => '唱歌'],
        ]);

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

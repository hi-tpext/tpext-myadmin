# tpextmyadmin for ThinkPHP6

此版本3.0有所不同，没有强制依赖`ichynul/tpextbuilder`UI库，需要自己选择UI添加依赖。

目前已支持以下UI库，替换`ichynul/tpextbuilder`的依赖：

`ichynul/tpext-vexipui` (基于Vue3 + vexipui的UI库 <https://github.com/hi-tpext/tpext-vexipui>)

`ichynul/tpext-tinyvue` (基于Vue3 + tinyvue的UI库 <https://github.com/hi-tpext/tpext-tinyvue>)

安装方式：

`composer` 命令：

1、安装本扩展：

`composer require ichynul/tpextmyadmin:^5.0.1`

2、安装UI库：因为二者冲突，只能选择其中一个安装：

`composer require ichynul/tpext-vexipui:^5.0.1` 或 `composer require ichynul/tpext-tinyvue:^5.0.1`

对已有3.0(tp6)项目，可编辑网站根目录下的`composer.json`文件升级

添加/修改以下依赖：

```josn
"require": {
    "topthink/framework": "^6.1.4|^8.0",
    "topthink/think-orm": "^2.0",
    "ichynul/tpextmyadmin": "^5.0.1",
    "ichynul/tpext-tinyvue": "^5.0.1"
},
```

然后执行`composer update`命令，更新依赖

注意，Vue3版本的UI库与`tpextbuilder`(bootstrap)的有所差别，建议新项目采用。

升级/切换UI库后页面样式乱或无法显示，访问 `/admin/extension/prepare`刷新资源，直到正常。

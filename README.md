# tpextmyadmin for ThinkPHP 6 / 8

此版本与 3.0 有所不同

- 1、使用vue3技术栈构建页面

- 2、默认依赖`ichynul/tpextbuilder`UI 库，可以自己选择 UI添加依赖以替换。

目前已支持以下 UI 库，替换`ichynul/tpextbuilder`的依赖：

`ichynul/tpext-vexipui` (基于 Vue3 + vexipui 的 UI 库 <https://github.com/hi-tpext/tpext-vexipui>)

`ichynul/tpext-tinyvue` (基于 Vue3 + tinyvue 的 UI 库 <https://github.com/hi-tpext/tpext-tinyvue>)

安装方式：

`composer` 命令：

1、安装本扩展：

`composer require ichynul/tpextmyadmin:^5.0.1`

2、[可选] 替换 UI 库，只能选择其中一个安装，否则会冲突：

`composer require ichynul/tpext-vexipui` 或 `composer require ichynul/tpext-tinyvue`

对已有 3.0(tp6)项目，可编辑网站根目录下的`composer.json`文件升级

添加/修改以下依赖：

```josn
"require": {
    "topthink/framework": "^6.1.4",//或^8.0
    "topthink/think-orm": "^2.0",
    "ichynul/tpextmyadmin": "^5.0.1",
    "ichynul/tpext-tinyvue": "^5.1.1"
},
```

然后执行`composer update`命令，更新依赖

注意，Vue3 版本的 UI 库与`tpextbuilder`(bootstrap)的有所差别，建议新项目采用。

升级/切换 UI 库后页面样式乱或无法显示，访问 `/admin/extension/prepare`刷新资源，直到正常。

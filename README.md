# tpextmyadmin for webman v2

## 安装

此版本 4.0 有所不同，没有强制依赖`ichynul/tpextbuilder`UI 库，需要自己选择 UI 添加依赖。

目前已支持以下 UI 库，替换`ichynul/tpextbuilder`的依赖：

`ichynul/tpext-vexipui` (基于 Vue3 + vexipui 的 UI 库 <https://github.com/hi-tpext/tpext-vexipui>)

`ichynul/tpext-tinyvue` (基于 Vue3 + tinyvue 的 UI 库 <https://github.com/hi-tpext/tpext-tinyvue>)

安装方式：

`composer` 命令：

1、安装本扩展：

`composer require ichynul/tpextmyadmin:^4.5.1`

2、安装 UI 库：因为二者冲突，只能选择其中一个安装：

`composer require ichynul/tpext-vexipui:^5.0.1` 或 `composer require ichynul/tpext-tinyvue:^5.1.1` 或 `composer require ichynul/tpextbuilder:^3.9.1`

升级/切换 UI 库后页面样式乱或无法显示，访问 `/admin/extension/prepare`刷新资源，直到正常。

详细安装说明见：<https://github.com/hi-tpext/mywebman>

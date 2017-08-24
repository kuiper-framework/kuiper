==============================
安装
==============================

kuiper 运行环境需要 php >= 5.6 。低版本 php 将出现不兼容错误。

kuiper 使用 composer 管理代码依赖，请先确认安装了 composer。

  Note: 在未对外发布前，可以使用内部 packagist 镜像。运行命令 ::

     composer config -g repo.winwin composer "http://toran.winwin.group/repo/private/"
     composer config -g repo.packagist composer "http://toran.winwin.group/repo/packagist/"

kuiper 各模块之间没有强耦合，可单独使用，需要哪个模块就用 composer 安装哪个模块，例如 ::

     composer require kuiper/annotations

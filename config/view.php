<?php
// 模板引擎配置
return [
    // 模板引擎类型
    'type'          => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule'     => 1,
    // 模板目录名
    'view_dir_name' => 'view',
    // 模板起始路径（使用项目根目录下的 view 目录）
    'view_path'     => app()->getRootPath() . 'view' . DIRECTORY_SEPARATOR,
    // 模板文件后缀
    'view_suffix'   => 'html',
    // 模板文件名分隔符
    'view_depr'     => DIRECTORY_SEPARATOR,
    // 是否开启模板编译缓存
    'tpl_cache'     => !env('app_debug', false),
    // 模板变量默认过滤
    'default_filter' => 'htmlentities',
];

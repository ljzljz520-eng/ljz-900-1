<?php
return [
    // 应用地址
    'app_host'         => env('app.host', ''),
    // 应用的命名空间
    'app_namespace'    => 'app',
    // 是否启用路由
    'with_route'       => true,
    // 默认时区
    'default_timezone' => env('app.default_timezone', 'Asia/Shanghai'),
    // 是否显示错误信息（开发环境开启）
    'show_error_msg'   => true,
];

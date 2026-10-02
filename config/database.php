<?php
// 数据库配置（连接参数读取根目录 .env）
return [
    // 默认使用的数据库连接配置
    'default'     => 'mysql',

    // 数据库连接配置信息
    'connections' => [
        'mysql' => [
            // 数据库类型
            'type'            => env('db_type', 'mysql'),
            // 服务器地址
            'hostname'        => env('db_host', '127.0.0.1'),
            // 数据库名
            'database'        => env('db_name', 'dorm_photo'),
            // 用户名
            'username'        => env('db_user', 'root'),
            // 密码
            'password'        => env('db_pass', ''),
            // 端口
            'hostport'        => env('db_port', '3306'),
            // 数据库连接参数
            'params'          => [],
            // 数据库编码默认采用utf8mb4
            'charset'         => env('db_charset', 'utf8mb4'),
            // 数据库表前缀
            'prefix'          => env('db_prefix', ''),
            // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
            'deploy'          => 0,
            // 数据库读写是否分离 主从式有效
            'rw_separate'     => false,
            // 读写分离后 主服务器数量
            'master_num'      => 1,
            // 指定从服务器序号
            'slave_no'        => '',
            // 是否严格检查字段是否存在
            'fields_strict'   => true,
            // 是否需要断线重连
            'break_reconnect' => true,
            // 监听SQL
            'trigger_sql'     => env('app_debug', true),
            // 开启字段缓存
            'fields_cache'    => false,
        ],
    ],
];

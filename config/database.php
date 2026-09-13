<?php
return [
    'default'     => 'mysql',
    'connections' => [
        'mysql' => [
            'type'            => env('database.type', 'mysql'),
            'hostname'        => env('database.hostname', '127.0.0.1'),
            'database'        => env('database.database', 'dorm_clean'),
            'username'        => env('database.username', 'dorm'),
            'password'        => env('database.password', 'dorm123'),
            'hostport'        => env('database.hostport', '3306'),
            'charset'         => env('database.charset', 'utf8mb4'),
            'prefix'          => '',
            'debug'           => env('database.debug', false),
            'deploy'          => 0,
            'rw_separate'     => false,
            'master_num'      => 1,
            'slave_no'        => '',
            'fields_strict'   => true,
            'break_reconnect' => false,
            'trigger_sql'     => env('app_debug', true),
            'fields_cache'    => false,
        ],
    ],
];

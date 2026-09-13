<?php
return [
    'default' => 'public',
    'disks'   => [
        'local'  => [
            'type' => 'local',
            'root' => runtime_path() . 'storage',
        ],
        'public' => [
            'type' => 'local',
            'root' => public_path() . 'uploads',
            'url'  => '/uploads',
        ],
    ],
];

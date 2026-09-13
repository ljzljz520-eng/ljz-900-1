<?php
return [
    'default'  => 'file',
    'channels' => [
        'file' => [
            'type'        => 'File',
            'path'        => runtime_path() . 'log/',
            'apart_level' => [],
            'max_files'   => 30,
        ],
    ],
];

<?php
// 业务配置：扣分项预设、上传限制
return [
    // 常见宿舍卫生扣分项预设（创建整改单时可勾选，也可自定义）
    'deduction_presets' => [
        ['item' => '地面脏乱',     'points' => 1],
        ['item' => '垃圾未清理',   'points' => 1],
        ['item' => '桌面物品杂乱', 'points' => 0.5],
        ['item' => '床铺未整理',   'points' => 0.5],
        ['item' => '卫生间污渍',   'points' => 1],
        ['item' => '阳台杂物堆积', 'points' => 0.5],
        ['item' => '违规电器',     'points' => 2],
        ['item' => '室内空气异味', 'points' => 0.5],
    ],
    // 单张图片最大 10MB
    'upload_max_size'  => 10 * 1024 * 1024,
    // 允许的图片扩展名
    'upload_allow_ext' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    // 学生端单次最多上传张数
    'student_max_photos' => 9,
];

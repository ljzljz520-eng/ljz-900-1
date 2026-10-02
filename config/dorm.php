<?php
// 业务配置：扣分项预设、上传限制
return [
    // 预设扣分项 => 默认扣分值
    'deduct_items'    => [
        '地面脏乱'     => 2,
        '垃圾未清理'   => 2,
        '桌面物品凌乱' => 1,
        '床铺不整'     => 1,
        '卫生间有异味' => 3,
        '阳台堆放杂物' => 1,
        '违规电器'     => 5,
    ],
    // 单张图片最大字节数（8MB）
    'upload_max_size' => 8 * 1024 * 1024,
    // 允许的图片扩展名
    'upload_ext'      => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
    // 允许的 MIME
    'upload_mime'     => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
];

<?php
// 应用公共文件

if (!function_exists('status_text')) {
    /**
     * 整改状态文案
     */
    function status_text(string $status): string
    {
        return [
            'pending'   => '待整改',
            'submitted' => '已提交整改',
            'confirmed' => '已确认完成',
        ][$status] ?? $status;
    }
}

if (!function_exists('status_badge')) {
    /**
     * 整改状态对应样式
     */
    function status_badge(string $status): string
    {
        return [
            'pending'   => 'badge-warn',
            'submitted' => 'badge-info',
            'confirmed' => 'badge-ok',
        ][$status] ?? 'badge-default';
    }
}

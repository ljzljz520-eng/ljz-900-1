<?php
// 应用公共函数文件

/**
 * 渲染 PHP 原生模板
 */
function view(string $template, array $vars = []): string
{
    return \app\service\View::fetch($template, $vars);
}

/**
 * 格式化整改单状态
 */
function ticket_status_text(int $status): string
{
    return \app\model\Ticket::statusText($status);
}

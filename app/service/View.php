<?php
declare (strict_types = 1);

namespace app\service;

/**
 * 极简 PHP 原生模板引擎（零依赖）
 */
class View
{
    public static function fetch(string $template, array $vars = []): string
    {
        $file = app()->getAppPath() . 'view' . DIRECTORY_SEPARATOR
              . str_replace('/', DIRECTORY_SEPARATOR, $template) . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('模板不存在: ' . $template);
        }
        extract($vars, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }
}

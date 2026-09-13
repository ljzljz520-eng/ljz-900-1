<?php
// PHP 内置服务器路由脚本：php -S 127.0.0.1:8000 -t public public/router.php
$uri  = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
$file = __DIR__ . $uri;
if ($uri !== '/' && (is_file($file) || is_dir($file))) {
    return false; // 静态资源直接返回
}
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/index.php';
require __DIR__ . '/index.php';

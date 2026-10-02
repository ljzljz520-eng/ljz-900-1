<?php
/**
 * 初始化脚本：创建数据表 + 写入初始账号
 * 用法：php bin/setup.php
 */
$envFile = dirname(__DIR__) . '/.env';
$env = [];
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $env[strtoupper($k)] = $v;
    }
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$name = $env['DB_NAME'] ?? 'dorm_photo';
$user = $env['DB_USER'] ?? 'root';
$pass = $env['DB_PASS'] ?? '';

try {
    $pdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$name}`");

    // 建表
    $sql = file_get_contents(dirname(__DIR__) . '/database.sql');
    foreach (array_filter(array_map('trim', explode(';', $sql))) as $stmt) {
        if ($stmt !== '') {
            $pdo->exec($stmt);
        }
    }
    echo "[OK] 数据表创建完成\n";

    // 初始账号
    $users = [
        ['admin', 'admin123', 'admin', '系统管理员'],
        ['counselor', 'counselor123', 'counselor', '辅导员王老师'],
    ];
    $stmt = $pdo->prepare('INSERT INTO admin_user (username, password, role, name, created_at) VALUES (?,?,?,?,NOW())
        ON DUPLICATE KEY UPDATE password = VALUES(password), role = VALUES(role), name = VALUES(name)');
    foreach ($users as [$u, $p, $r, $n]) {
        $stmt->execute([$u, password_hash($p, PASSWORD_DEFAULT), $r, $n]);
        echo "[OK] 账号 {$u} / {$p}（{$r}）\n";
    }
    echo "初始化完成。\n";
} catch (Throwable $e) {
    fwrite(STDERR, "[失败] " . $e->getMessage() . "\n");
    exit(1);
}

<?php
/** @var string $title */
$roleText = ['admin' => '管理员', 'counselor' => '辅导员'];
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($title ?? '宿舍卫生整改拍照站') ?> - 宿舍卫生整改拍照站</title>
<link rel="stylesheet" href="/static/app.css">
</head>
<body>
<?php if (!empty($user)): ?>
<nav class="nav">
  <div class="nav-inner">
    <a class="brand" href="/">🧹 宿舍卫生整改</a>
    <div class="nav-links">
      <?php if ($user['role'] === 'admin'): ?>
      <a href="/admin">整改单</a>
      <a href="/admin/rooms">房间管理</a>
      <a href="/summary">整改汇总</a>
      <?php else: ?>
      <a href="/summary">整改汇总</a>
      <?php endif; ?>
      <span class="nav-user"><?= htmlspecialchars($user['name'] ?: $user['username']) ?>（<?= htmlspecialchars($roleText[$user['role']] ?? $user['role']) ?>）</span>
      <a href="/logout">退出</a>
    </div>
  </div>
</nav>
<?php endif; ?>
<main class="container">

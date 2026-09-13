<?php $title = '登录'; include __DIR__ . '/_header.php'; ?>
<div class="login-box">
  <div class="card login-card">
    <h1 class="login-title">🧹 宿舍卫生整改拍照站</h1>
    <p class="muted center">管理员 / 辅导员登录</p>
    <?php if (!empty($error)): ?>
      <div class="alert alert-bad"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="post" action="/login">
      <label class="field">
        <span>用户名</span>
        <input type="text" name="username" value="<?= htmlspecialchars($username ?? '') ?>" required autofocus>
      </label>
      <label class="field">
        <span>密码</span>
        <input type="password" name="password" required>
      </label>
      <button class="btn btn-primary btn-block" type="submit">登 录</button>
    </form>
    <p class="muted center small">学生请使用整改二维码进入，无需登录</p>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>

<?php $title = $title ?? '出错了'; include __DIR__ . '/_header.php'; ?>
<div class="card center" style="max-width:480px;margin:60px auto;">
  <h2>😕 <?= htmlspecialchars($title ?? '出错了') ?></h2>
  <p class="muted"><?= htmlspecialchars($msg ?? '') ?></p>
  <p><a class="btn" href="/">返回首页</a></p>
</div>
<?php include __DIR__ . '/_footer.php'; ?>

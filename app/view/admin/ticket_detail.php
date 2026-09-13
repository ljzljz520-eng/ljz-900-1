<?php
$title = '整改单详情';
include __DIR__ . '/../_header.php';
use app\model\Ticket;
$t = $ticket;
?>
<div class="page-head">
  <h2>整改单 <code><?= htmlspecialchars($t->ticket_key) ?></code></h2>
  <a class="btn" href="/admin">返回列表</a>
</div>

<div class="grid-2">
  <div class="card">
    <h3>基本信息</h3>
    <table class="kv">
      <tr><th>房间</th><td><?= htmlspecialchars($t->room->label) ?></td></tr>
      <tr><th>状态</th><td><span class="badge <?= Ticket::statusClass($t->status) ?>"><?= Ticket::statusText($t->status) ?></span></td></tr>
      <tr><th>扣分项</th><td>
        <ul class="deduct-list">
        <?php foreach ($t->deductions as $d): ?>
          <li><?= htmlspecialchars($d->item) ?><span class="points">-<?= (float)$d->points ?> 分</span></li>
        <?php endforeach; ?>
        </ul>
        <div class="muted small">合计扣 <?= (float)$t->deductions()->sum('points') ?> 分</div>
      </td></tr>
      <?php if ($t->remark): ?><tr><th>备注</th><td><?= htmlspecialchars($t->remark) ?></td></tr><?php endif; ?>
      <tr><th>创建时间</th><td class="muted"><?= htmlspecialchars((string) $t->created_at) ?></td></tr>
      <?php if ($t->submitted_at): ?><tr><th>学生提交</th><td class="muted"><?= htmlspecialchars((string) $t->submitted_at) ?></td></tr><?php endif; ?>
      <?php if ($t->reviewed_at): ?><tr><th>复查时间</th><td class="muted"><?= htmlspecialchars((string) $t->reviewed_at) ?><?= $t->review_note ? '（' . htmlspecialchars($t->review_note) . '）' : '' ?></td></tr><?php endif; ?>
    </table>

    <?php if ((int)$t->status === Ticket::STATUS_SUBMITTED): ?>
    <div class="review-box">
      <h4>复查操作</h4>
      <div class="btn-row">
        <button class="btn btn-ok" id="approveBtn">✔ 复查通过</button>
        <button class="btn btn-danger" id="rejectBtn">✘ 驳回重做</button>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <div class="card center">
    <h3>学生整改二维码</h3>
    <img class="qrcode" src="/admin/ticket/<?= $t->id ?>/qrcode" alt="整改二维码">
    <p class="muted small">学生扫码后上传整改照片，无需登录</p>
    <div class="copy-row">
      <input type="text" readonly value="<?= htmlspecialchars($studentUrl) ?>" id="stuUrl">
      <button class="btn btn-sm" id="copyBtn">复制</button>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-head">
    <h3>问题照片（<?= count($t->problemPhotos) ?> 张）</h3>
    <label class="btn btn-sm">＋ 补传<input type="file" accept="image/*" hidden id="addProblem"></label>
  </div>
  <div class="photo-grid sortable" data-kind="problem" id="problemGrid">
    <?php foreach ($t->problemPhotos as $p): ?>
    <div class="photo-item" data-id="<?= $p->id ?>">
      <a href="<?= htmlspecialchars($p->url) ?>" target="_blank"><img src="<?= htmlspecialchars($p->thumb_url) ?>" loading="lazy"></a>
      <div class="photo-ops">
        <button class="op move-up" title="上移">↑</button>
        <button class="op move-down" title="下移">↓</button>
        <button class="op del" title="删除">✕</button>
      </div>
      <span class="photo-no"><?= $p->sort ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card">
  <div class="card-head"><h3>整改照片（<?= count($t->fixPhotos) ?> 张，学生上传）</h3></div>
  <?php if (count($t->fixPhotos) === 0): ?>
    <p class="muted">学生还未上传整改照片。</p>
  <?php endif; ?>
  <div class="photo-grid sortable" data-kind="fix" id="fixGrid">
    <?php foreach ($t->fixPhotos as $p): ?>
    <div class="photo-item" data-id="<?= $p->id ?>">
      <a href="<?= htmlspecialchars($p->url) ?>" target="_blank"><img src="<?= htmlspecialchars($p->thumb_url) ?>" loading="lazy"></a>
      <div class="photo-ops">
        <button class="op move-up" title="上移">↑</button>
        <button class="op move-down" title="下移">↓</button>
        <button class="op del" title="删除">✕</button>
      </div>
      <span class="photo-no"><?= $p->sort ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
window.TICKET = { id: <?= $t->id ?> };
</script>
<?php include __DIR__ . '/../_footer.php'; ?>

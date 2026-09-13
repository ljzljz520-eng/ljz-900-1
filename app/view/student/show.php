<?php
$title = '宿舍整改上传';
include __DIR__ . '/../_header.php';
use app\model\Ticket;
$t = $ticket;
?>
<div class="student-wrap">
  <div class="card">
    <div class="ticket-head">
      <div>
        <span class="room-tag"><?= htmlspecialchars($t->room->label) ?></span>
        <code class="key-tag"><?= htmlspecialchars($t->ticket_key) ?></code>
      </div>
      <span class="badge <?= Ticket::statusClass($t->status) ?>"><?= Ticket::statusText($t->status) ?></span>
    </div>
    <h4>本次扣分项（合计扣 <?= (float)$t->deductions()->sum('points') ?> 分）</h4>
    <ul class="deduct-list">
      <?php foreach ($t->deductions as $d): ?>
      <li><?= htmlspecialchars($d->item) ?><span class="points">-<?= (float)$d->points ?> 分</span></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($t->remark): ?><p class="muted">备注：<?= htmlspecialchars($t->remark) ?></p><?php endif; ?>
    <?php if ((int)$t->status === Ticket::STATUS_REJECTED && $t->review_note): ?>
    <div class="alert alert-bad">复查被驳回：<?= htmlspecialchars($t->review_note) ?>，请重新整改后再次提交。</div>
    <?php endif; ?>
  </div>

  <div class="card">
    <h4>📷 问题照片（请对照整改）</h4>
    <div class="photo-grid">
      <?php foreach ($t->problemPhotos as $p): ?>
      <div class="photo-item"><a href="<?= $p->url ?>" target="_blank"><img src="<?= $p->thumb_url ?>" loading="lazy"></a></div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h4>✅ 我的整改照片（<?= count($t->fixPhotos) ?>/<?= $maxPhoto ?> 张）</h4>

    <?php if ($editable): ?>
    <label class="upload-btn">
      ＋ 拍照 / 选择图片上传
      <input type="file" accept="image/*" multiple hidden id="fixInput">
    </label>
    <p class="muted small">上传后可用 ↑↓ 调整顺序、✕ 删除，确认后点底部「提交整改」</p>
    <?php endif; ?>

    <div class="photo-grid sortable" id="fixGrid">
      <?php foreach ($t->fixPhotos as $p): ?>
      <div class="photo-item" data-id="<?= $p->id ?>">
        <a href="<?= $p->url ?>" target="_blank"><img src="<?= $p->thumb_url ?>" loading="lazy"></a>
        <?php if ($editable): ?>
        <div class="photo-ops">
          <button class="op move-up" title="上移">↑</button>
          <button class="op move-down" title="下移">↓</button>
          <button class="op del" title="删除">✕</button>
        </div>
        <?php endif; ?>
        <span class="photo-no"><?= $p->sort ?></span>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($editable): ?>
    <button class="btn btn-primary btn-block btn-lg" id="submitBtn">提交整改</button>
    <?php elseif ((int)$t->status === Ticket::STATUS_SUBMITTED): ?>
    <div class="alert alert-info">✔ 已提交整改（<?= $t->submitted_at ?>），请等待复查结果。</div>
    <?php elseif ((int)$t->status === Ticket::STATUS_APPROVED): ?>
    <div class="alert alert-ok">🎉 复查已通过，感谢配合！</div>
    <?php endif; ?>
  </div>
</div>

<script>
window.STUDENT = { token: <?= json_encode($t->token) ?>, editable: <?= $editable ? 'true' : 'false' ?> };
</script>
<?php include __DIR__ . '/../_footer.php'; ?>

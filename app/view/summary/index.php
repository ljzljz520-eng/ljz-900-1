<?php
$title = '整改汇总';
include __DIR__ . '/../_header.php';
use app\model\Ticket;
?>
<div class="page-head"><h2>整改汇总（按房间 / key 成对查看）</h2></div>

<form class="filter-bar" method="get" action="/summary">
  <select name="room_id">
    <option value="0">全部房间</option>
    <?php foreach ($rooms as $r): ?>
    <option value="<?= $r->id ?>" <?= $roomId === $r->id ? 'selected' : '' ?>><?= htmlspecialchars($r->label) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="status">
    <option value="">全部状态</option>
    <?php foreach ([Ticket::STATUS_PENDING, Ticket::STATUS_SUBMITTED, Ticket::STATUS_APPROVED, Ticket::STATUS_REJECTED] as $s): ?>
    <option value="<?= $s ?>" <?= (string)$s === (string)$status ? 'selected' : '' ?>><?= Ticket::statusText($s) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="text" name="kw" placeholder="搜索 key…" value="<?= htmlspecialchars($kw) ?>">
  <button class="btn" type="submit">筛选</button>
</form>

<?php if (count($tickets) === 0): ?>
<div class="card"><p class="muted center">没有符合条件的整改单</p></div>
<?php endif; ?>

<?php foreach ($tickets as $t): $pairs = $pairsMap[$t->id]; ?>
<div class="card ticket-card">
  <div class="ticket-head">
    <div>
      <span class="room-tag"><?= htmlspecialchars($t->room->label) ?></span>
      <code class="key-tag"><?= htmlspecialchars($t->ticket_key) ?></code>
      <span class="badge <?= Ticket::statusClass($t->status) ?>"><?= Ticket::statusText($t->status) ?></span>
    </div>
    <div class="muted small">
      扣分 <?= (float)$t->deductions()->sum('points') ?> 分 ·
      <?= htmlspecialchars(implode('、', array_map(fn($d) => $d->item, iterator_to_array($t->deductions)))) ?>
    </div>
  </div>

  <?php if (!$pairs): ?>
    <p class="muted">暂无照片</p>
  <?php else: ?>
  <div class="pair-table-wrap">
    <table class="pair-table">
      <thead><tr><th>#</th><th>问题照片（管理员）</th><th>整改照片（学生）</th></tr></thead>
      <tbody>
      <?php foreach ($pairs as $i => $pair): ?>
        <tr>
          <td class="pair-no"><?= $i + 1 ?></td>
          <td class="pair-cell">
            <?php if ($pair['problem']): ?>
            <a href="<?= htmlspecialchars($pair['problem']->url) ?>" target="_blank">
              <img src="<?= htmlspecialchars($pair['problem']->thumb_url) ?>" loading="lazy" alt="问题照片<?= $i+1 ?>">
            </a>
            <?php else: ?><span class="muted">—</span><?php endif; ?>
          </td>
          <td class="pair-cell">
            <?php if ($pair['fix']): ?>
            <a href="<?= htmlspecialchars($pair['fix']->url) ?>" target="_blank">
              <img src="<?= htmlspecialchars($pair['fix']->thumb_url) ?>" loading="lazy" alt="整改照片<?= $i+1 ?>">
            </a>
            <?php else: ?><span class="muted">待上传</span><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
  <div class="muted small ticket-foot">
    创建 <?= htmlspecialchars((string) $t->created_at) ?>
    <?php if ($t->submitted_at): ?> · 提交 <?= htmlspecialchars((string) $t->submitted_at) ?><?php endif; ?>
    <?php if ($t->review_note): ?> · 复查意见：<?= htmlspecialchars($t->review_note) ?><?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<div class="pagination"><?= $tickets->render() ?></div>
<?php include __DIR__ . '/../_footer.php'; ?>

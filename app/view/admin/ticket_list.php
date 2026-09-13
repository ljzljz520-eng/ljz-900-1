<?php
$title = '整改单列表';
include __DIR__ . '/../_header.php';
use app\model\Ticket;
?>
<div class="page-head">
  <h2>整改单列表</h2>
  <a class="btn btn-primary" href="/admin/ticket/create">＋ 新建整改单</a>
</div>

<form class="filter-bar" method="get" action="/admin">
  <input type="text" name="kw" placeholder="搜索整改单 key…" value="<?= htmlspecialchars($kw) ?>">
  <select name="status">
    <option value="">全部状态</option>
    <?php foreach ([Ticket::STATUS_PENDING, Ticket::STATUS_SUBMITTED, Ticket::STATUS_APPROVED, Ticket::STATUS_REJECTED] as $s): ?>
    <option value="<?= $s ?>" <?= (string)$s === (string)$status ? 'selected' : '' ?>><?= Ticket::statusText($s) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">筛选</button>
</form>

<div class="card">
<table class="table">
  <thead>
    <tr>
      <th>整改单 Key</th><th>房间</th><th>扣分项</th><th>问题照</th><th>整改照</th>
      <th>状态</th><th>创建时间</th><th>操作</th>
    </tr>
  </thead>
  <tbody>
  <?php if (count($list) === 0): ?>
    <tr><td colspan="8" class="center muted">暂无整改单，点击右上角「新建整改单」开始</td></tr>
  <?php endif; ?>
  <?php foreach ($list as $t): ?>
    <tr>
      <td><a href="/admin/ticket/<?= $t->id ?>"><code><?= htmlspecialchars($t->ticket_key) ?></code></a></td>
      <td><?= htmlspecialchars($t->room->label) ?></td>
      <td><?= count($t->deductions) ?> 项 / 扣 <?= (float)$t->deductions()->sum('points') ?> 分</td>
      <td><?= $t->problem_photos_count ?> 张</td>
      <td><?= $t->fix_photos_count ?> 张</td>
      <td><span class="badge <?= Ticket::statusClass($t->status) ?>"><?= Ticket::statusText($t->status) ?></span></td>
      <td class="muted small"><?= $t->created_at ?></td>
      <td><a class="btn btn-sm" href="/admin/ticket/<?= $t->id ?>">详情/二维码</a></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<div class="pagination"><?= $list->render() ?></div>
</div>
<?php include __DIR__ . '/../_footer.php'; ?>

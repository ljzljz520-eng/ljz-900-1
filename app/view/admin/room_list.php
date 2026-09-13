<?php $title = '房间管理'; include __DIR__ . '/../_header.php'; ?>
<div class="page-head"><h2>房间管理</h2></div>

<div class="card">
  <form id="roomForm" class="inline-form">
    <input type="text" name="building" placeholder="楼栋，如 1号楼" required>
    <input type="text" name="room_no" placeholder="房间号，如 101" required>
    <button class="btn btn-primary" type="submit">＋ 添加房间</button>
  </form>
</div>

<div class="card">
<table class="table">
  <thead><tr><th>楼栋</th><th>房间号</th><th>整改单数</th><th>创建时间</th><th>操作</th></tr></thead>
  <tbody id="roomTbody">
  <?php foreach ($rooms as $r): ?>
    <tr data-id="<?= $r->id ?>">
      <td><?= htmlspecialchars($r->building) ?></td>
      <td><?= htmlspecialchars($r->room_no) ?></td>
      <td><?= $r->tickets_count ?></td>
      <td class="muted small"><?= $r->created_at ?></td>
      <td><button class="btn btn-sm btn-danger room-del" data-id="<?= $r->id ?>">删除</button></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
<?php include __DIR__ . '/../_footer.php'; ?>

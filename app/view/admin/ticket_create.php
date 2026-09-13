<?php $title = '新建整改单'; include __DIR__ . '/../_header.php'; ?>
<div class="page-head"><h2>新建整改单</h2><a class="btn" href="/admin">返回列表</a></div>

<?php if (count($rooms) === 0): ?>
<div class="card"><p class="muted">还没有宿舍房间，请先到 <a href="/admin/rooms">房间管理</a> 添加。</p></div>
<?php else: ?>
<form id="ticketForm" class="card" enctype="multipart/form-data">
  <label class="field">
    <span>宿舍房间 *</span>
    <select name="room_id" required>
      <option value="">请选择房间</option>
      <?php foreach ($rooms as $r): ?>
      <option value="<?= $r->id ?>"><?= htmlspecialchars($r->label) ?></option>
      <?php endforeach; ?>
    </select>
  </label>

  <div class="field">
    <span>扣分项 *（勾选预设或自定义，分值可改）</span>
    <div class="preset-list">
      <?php foreach ($presets as $p): ?>
      <button type="button" class="chip" data-item="<?= htmlspecialchars($p['item']) ?>" data-points="<?= $p['points'] ?>">
        ＋ <?= htmlspecialchars($p['item']) ?>（<?= $p['points'] ?>分）
      </button>
      <?php endforeach; ?>
    </div>
    <div id="deductionRows"></div>
    <button type="button" class="btn btn-sm" id="addCustom">＋ 自定义扣分项</button>
  </div>

  <label class="field">
    <span>问题照片 *（可多选，上传后可在详情页排序）</span>
    <input type="file" name="photos[]" accept="image/*" multiple required id="photoInput">
  </label>
  <div id="preview" class="photo-grid"></div>

  <label class="field">
    <span>备注（可选）</span>
    <textarea name="remark" rows="2" placeholder="补充说明，如：限期一天内整改"></textarea>
  </label>

  <button class="btn btn-primary btn-block" type="submit">提交并生成整改二维码</button>
</form>
<?php endif; ?>
<?php include __DIR__ . '/../_footer.php'; ?>

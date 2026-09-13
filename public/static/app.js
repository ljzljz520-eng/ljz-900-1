/* 宿舍卫生整改拍照站 - 前端交互 */
(function () {
  'use strict';

  /* ---------- 工具 ---------- */
  function $(sel, el) { return (el || document).querySelector(sel); }
  function $all(sel, el) { return Array.from((el || document).querySelectorAll(sel)); }

  function toast(msg, isErr) {
    var t = document.createElement('div');
    t.className = 'toast' + (isErr ? ' err' : '');
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () { t.remove(); }, 2200);
  }

  function post(url, data) {
    var opts = { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } };
    if (data instanceof FormData) { opts.body = data; }
    else {
      opts.headers['Content-Type'] = 'application/x-www-form-urlencoded';
      opts.body = new URLSearchParams(data).toString();
    }
    return fetch(url, opts).then(function (r) { return r.json(); });
  }

  /* ---------- 房间管理 ---------- */
  var roomForm = $('#roomForm');
  if (roomForm) {
    roomForm.addEventListener('submit', function (e) {
      e.preventDefault();
      post('/admin/rooms', new FormData(roomForm)).then(function (res) {
        if (res.code === 0) { toast(res.msg); setTimeout(function () { location.reload(); }, 600); }
        else toast(res.msg, true);
      });
    });
    $all('.room-del').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!confirm('确定删除该房间？')) return;
        post('/admin/rooms/' + btn.dataset.id + '/delete', {}).then(function (res) {
          if (res.code === 0) { toast(res.msg); btn.closest('tr').remove(); }
          else toast(res.msg, true);
        });
      });
    });
  }

  /* ---------- 新建整改单 ---------- */
  var ticketForm = $('#ticketForm');
  if (ticketForm) {
    var rowsBox = $('#deductionRows');

    function addRow(item, points) {
      var div = document.createElement('div');
      div.className = 'deduct-row';
      div.innerHTML =
        '<input type="text" name="items[]" placeholder="扣分项，如：地面脏乱" required>' +
        '<input type="number" name="points[]" placeholder="分值" min="0.5" max="20" step="0.5" required>' +
        '<button type="button" class="rm">✕</button>';
      div.children[0].value = item || '';
      div.children[1].value = points || '';
      div.querySelector('.rm').addEventListener('click', function () { div.remove(); });
      rowsBox.appendChild(div);
    }

    // 预设项点击加入
    $all('.preset-list .chip').forEach(function (chip) {
      chip.addEventListener('click', function () {
        addRow(chip.dataset.item, chip.dataset.points);
      });
    });
    $('#addCustom').addEventListener('click', function () { addRow('', ''); });
    addRow('地面脏乱', 1); // 默认一行

    // 图片预览
    var photoInput = $('#photoInput');
    var preview = $('#preview');
    photoInput.addEventListener('change', function () {
      preview.innerHTML = '';
      Array.from(photoInput.files).forEach(function (f) {
        var url = URL.createObjectURL(f);
        var img = document.createElement('img');
        img.src = url;
        preview.appendChild(img);
      });
    });

    ticketForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var fd = new FormData(ticketForm);
      post('/admin/ticket', fd).then(function (res) {
        if (res.code === 0) {
          toast(res.msg);
          setTimeout(function () { location.href = res.data.url; }, 700);
        } else toast(res.msg, true);
      });
    });
  }

  /* ---------- 整改单详情 ---------- */
  if (window.TICKET) {
    var tid = window.TICKET.id;

    // 复制学生链接
    var copyBtn = $('#copyBtn');
    if (copyBtn) {
      copyBtn.addEventListener('click', function () {
        var input = $('#stuUrl');
        input.select();
        document.execCommand('copy');
        toast('链接已复制');
      });
    }

    // 补传问题照片
    var addProblem = $('#addProblem');
    if (addProblem) {
      addProblem.addEventListener('change', function () {
        if (!addProblem.files.length) return;
        var fd = new FormData();
        fd.append('photo', addProblem.files[0]);
        post('/admin/ticket/' + tid + '/photo', fd).then(function (res) {
          if (res.code === 0) { toast(res.msg); setTimeout(function () { location.reload(); }, 500); }
          else toast(res.msg, true);
        });
      });
    }

    // 复查
    var approveBtn = $('#approveBtn'), rejectBtn = $('#rejectBtn');
    function review(action) {
      var note = '';
      if (action === 'reject') {
        note = prompt('请输入驳回原因（将展示给学生）：', '整改不到位，请重新整改');
        if (note === null) return;
      } else if (!confirm('确认复查通过？')) return;
      post('/admin/ticket/' + tid + '/review', { action: action, note: note }).then(function (res) {
        if (res.code === 0) { toast(res.msg); setTimeout(function () { location.reload(); }, 600); }
        else toast(res.msg, true);
      });
    }
    if (approveBtn) approveBtn.addEventListener('click', function () { review('approve'); });
    if (rejectBtn) rejectBtn.addEventListener('click', function () { review('reject'); });

    bindPhotoOps('/admin/ticket/' + tid + '/sort', '/admin/photo/', true);
  }

  /* ---------- 学生端 ---------- */
  if (window.STUDENT) {
    var token = window.STUDENT.token;
    var editable = window.STUDENT.editable;

    if (editable) {
      var fixInput = $('#fixInput');
      fixInput.addEventListener('change', function () {
        if (!fixInput.files.length) return;
        var fd = new FormData();
        Array.from(fixInput.files).forEach(function (f) { fd.append('photos[]', f); });
        toast('上传中…');
        post('/s/' + token + '/upload', fd).then(function (res) {
          if (res.code === 0) { toast(res.msg); setTimeout(function () { location.reload(); }, 500); }
          else toast(res.msg, true);
        });
      });

      var submitBtn = $('#submitBtn');
      submitBtn.addEventListener('click', function () {
        if (!confirm('确认提交整改？提交后不可再修改')) return;
        post('/s/' + token + '/submit', {}).then(function (res) {
          if (res.code === 0) { toast(res.msg); setTimeout(function () { location.reload(); }, 700); }
          else toast(res.msg, true);
        });
      });

      bindPhotoOps('/s/' + token + '/sort', '/s/' + token + '/photo/', false);
    }
  }

  /* ---------- 照片排序/删除（管理员与学生端共用） ---------- */
  function bindPhotoOps(sortUrl, delBase, isAdmin) {
    $all('.photo-grid.sortable').forEach(function (grid) {
      var kind = grid.dataset.kind || 'fix';

      function saveOrder() {
        var ids = $all('.photo-item', grid).map(function (el) { return el.dataset.id; });
        var data = { 'ids[]': ids };
        if (isAdmin) data.kind = kind;
        post(sortUrl, data).then(function (res) {
          if (res.code !== 0) toast(res.msg, true);
          else refreshNo();
        });
      }
      function refreshNo() {
        $all('.photo-item', grid).forEach(function (el, i) {
          var no = el.querySelector('.photo-no');
          if (no) no.textContent = i + 1;
        });
      }

      grid.addEventListener('click', function (e) {
        var btn = e.target.closest('button.op');
        if (!btn) return;
        var item = btn.closest('.photo-item');
        if (btn.classList.contains('del')) {
          if (!confirm('确定删除这张照片？')) return;
          post(delBase + item.dataset.id + '/delete', {}).then(function (res) {
            if (res.code === 0) { item.remove(); saveOrder(); toast('已删除'); }
            else toast(res.msg, true);
          });
        } else if (btn.classList.contains('move-up')) {
          var prev = item.previousElementSibling;
          if (prev) { grid.insertBefore(item, prev); saveOrder(); }
        } else if (btn.classList.contains('move-down')) {
          var next = item.nextElementSibling;
          if (next) { grid.insertBefore(next, item); saveOrder(); }
        }
      });
    });
  }
})();

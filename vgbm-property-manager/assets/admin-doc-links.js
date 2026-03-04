/* global jQuery, ajaxurl */
(function ($) {
  'use strict';

  function esc(s) {
    return String(s || '').replace(/[&<>"']/g, function (c) {
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]);
    });
  }

  function renderResult(item, postId, nonce) {
    var fileTag = item.has_file ? '' : ' <small style="color:#a00;">(no file)</small>';
    return '' +
      '<div class="vgbm-doc-result" style="padding:6px 0;border-bottom:1px solid #eee;display:flex;gap:8px;align-items:center;">' +
        '<div style="flex:1;min-width:0;">' +
          '<div><strong>' + esc(item.title) + '</strong>' + fileTag + '</div>' +
          '<div style="color:#666;font-size:12px;">' + esc(item.type_name) + ' · ' + esc(item.label_name) + ' · #' + item.id + '</div>' +
        '</div>' +
        '<button type="button" class="button button-small vgbm-doc-attach" data-doc-id="' + item.id + '">Attach</button>' +
      '</div>';
  }

  function renderLinkedRow(row) {
    var download = row.download_url ? '<a href="' + esc(row.download_url) + '" target="_blank" rel="noopener">Download</a>' : '—';
    return '' +
      '<tr data-doc-id="' + row.id + '">' +
        '<td><a href="' + esc(row.edit_url) + '">' + esc(row.title) + '</a></td>' +
        '<td>' + esc(row.type_name) + '</td>' +
        '<td>' + esc(row.label_name) + '</td>' +
        '<td>' + download + '</td>' +
        '<td><a href="#" class="vgbm-doc-detach">Detach</a></td>' +
      '</tr>';
  }

  $(function () {
    var $box = $('.vgbm-doc-links-box');
    if (!$box.length) return;

    var postId = parseInt($box.data('postId'), 10) || 0;
    var nonce = $box.data('nonce') || '';

    var $q = $box.find('.vgbm-doc-search-q');
    var $results = $box.find('.vgbm-doc-search-results');
    var $linkedTable = $box.find('table.vgbm-linked-docs tbody');

    var timer = null;

    function doSearch() {
      var q = ($q.val() || '').trim();
      if (q.length < 2) {
        $results.html('');
        return;
      }

      $results.html('<div style="padding:6px 0;color:#666;">Searching…</div>');

      $.get(ajaxurl, { action: 'vgbm_pm_doc_search', nonce: nonce, q: q })
        .done(function (resp) {
          if (!resp || !resp.success) {
            $results.html('<div style="padding:6px 0;color:#a00;">Search failed.</div>');
            return;
          }

          var items = resp.data && resp.data.items ? resp.data.items : [];
          if (!items.length) {
            $results.html('<div style="padding:6px 0;color:#666;">No results.</div>');
            return;
          }

          var html = '';
          items.forEach(function (it) {
            html += renderResult(it, postId, nonce);
          });
          $results.html(html);
        })
        .fail(function () {
          $results.html('<div style="padding:6px 0;color:#a00;">Search failed.</div>');
        });
    }

    $q.on('input', function () {
      clearTimeout(timer);
      timer = setTimeout(doSearch, 250);
    });

    $results.on('click', '.vgbm-doc-attach', function (e) {
      e.preventDefault();
      var docId = parseInt($(this).data('docId'), 10) || 0;
      if (!docId || !postId) return;

      var $btn = $(this);
      $btn.prop('disabled', true).text('Attaching…');

      $.post(ajaxurl, { action: 'vgbm_pm_doc_attach', nonce: nonce, post_id: postId, doc_id: docId })
        .done(function (resp) {
          if (!resp || !resp.success) {
            $btn.prop('disabled', false).text('Attach');
            alert('Attach failed.');
            return;
          }
          var row = resp.data;
          // Avoid duplicates
          if ($linkedTable.find('tr[data-doc-id="' + row.id + '"]').length === 0) {
            $linkedTable.prepend(renderLinkedRow(row));
          }
          $btn.text('Attached');
        })
        .fail(function () {
          $btn.prop('disabled', false).text('Attach');
          alert('Attach failed.');
        });
    });

    $box.on('click', '.vgbm-doc-detach', function (e) {
      e.preventDefault();
      var $tr = $(this).closest('tr');
      var docId = parseInt($tr.data('docId'), 10) || 0;
      if (!docId || !postId) return;
      if (!confirm('Detach this document from this record?')) return;

      $.post(ajaxurl, { action: 'vgbm_pm_doc_detach', nonce: nonce, post_id: postId, doc_id: docId })
        .done(function (resp) {
          if (!resp || !resp.success) {
            alert('Detach failed.');
            return;
          }
          $tr.remove();
          if ($linkedTable.find('tr').length === 0) {
            $linkedTable.append('<tr class="no-items"><td colspan="5">No documents linked yet.</td></tr>');
          }
        })
        .fail(function () {
          alert('Detach failed.');
        });
    });
  });
})(jQuery);

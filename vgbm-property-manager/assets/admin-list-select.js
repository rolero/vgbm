/* global jQuery */
(function ($) {
  'use strict';

  function isBlockedTarget(target) {
    // Do NOT toggle selection when clicking links or controls.
    // Links should keep their default behaviour (e.g. title opens edit screen).
    return $(target).closest('a, .row-actions a, button, input, select, textarea, label, .check-column').length > 0;
  }

  function isNonDataRow($tr) {
    return $tr.hasClass('no-items') || $tr.hasClass('inline-edit-row') || $tr.hasClass('hidden');
  }

  function toggleRow($tr) {
    var $cb = $tr.find('th.check-column input[type="checkbox"]').first();
    if (!$cb.length || $cb.is(':disabled')) return;

    $cb.prop('checked', !$cb.prop('checked')).trigger('change');
    $tr.toggleClass('is-selected', $cb.prop('checked'));
  }

  $(function () {
    var $tbody = $('#the-list');
    if (!$tbody.length) return;

    // Sync class with checked state.
    $tbody.find('tr').each(function () {
      var $tr = $(this);
      var $cb = $tr.find('th.check-column input[type="checkbox"]').first();
      if ($cb.length && $cb.prop('checked')) {
        $tr.addClass('is-selected');
      }
    });

    // Click anywhere on cells toggles selection, except links/controls.
    $tbody.on('click', 'td, th', function (e) {
      var $tr = $(this).closest('tr');
      if (!$tr.length || isNonDataRow($tr)) return;

      if (isBlockedTarget(e.target)) return;

      toggleRow($tr);
    });

    // Keep in sync when checkbox clicked directly.
    $tbody.on('change', 'th.check-column input[type="checkbox"]', function () {
      var $cb = $(this);
      var $tr = $cb.closest('tr');
      $tr.toggleClass('is-selected', $cb.prop('checked'));
    });
  });
})(jQuery);

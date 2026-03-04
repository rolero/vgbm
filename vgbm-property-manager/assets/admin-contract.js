/* global jQuery, wp */
(function ($) {
  'use strict';

  $(function () {
    var $pick = $('#vgbm_contract_document_pick');
    var $clear = $('#vgbm_contract_document_clear');
    var $field = $('#vgbm_contract_document_id');
    var $label = $('#vgbm_contract_document_label');
    if (!$pick.length || typeof wp === 'undefined' || !wp.media) return;

    var frame;

    $pick.on('click', function (e) {
      e.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: 'Select contract document',
        button: { text: 'Use this document' },
        multiple: false
      });

      frame.on('select', function () {
        var attachment = frame.state().get('selection').first().toJSON();
        $field.val(attachment.id);
        $label.text(attachment.filename || attachment.title || ('#' + attachment.id));
      });

      frame.open();
    });

    $clear.on('click', function (e) {
      e.preventDefault();
      $field.val('0');
      $label.text('No document selected');
    });
  });
})(jQuery);

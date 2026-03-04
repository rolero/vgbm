/* global jQuery, wp */
(function ($) {
  'use strict';

  function bindPicker(pickId, clearId, fieldId, labelId, emptyText) {
    var $pick = $(pickId);
    var $clear = $(clearId);
    var $field = $(fieldId);
    var $label = $(labelId);
    if (!$pick.length || typeof wp === 'undefined' || !wp.media) return;

    var frame;

    $pick.on('click', function (e) {
      e.preventDefault();

      if (frame) {
        frame.open();
        return;
      }

      frame = wp.media({
        title: 'Select photo',
        button: { text: 'Use this photo' },
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
      $label.text(emptyText);
    });
  }

  $(function () {
    bindPicker('#vgbm_utility_photo_pick', '#vgbm_utility_photo_clear', '#vgbm_utility_photo_id', '#vgbm_utility_photo_label', 'No photo selected');
  });
})(jQuery);

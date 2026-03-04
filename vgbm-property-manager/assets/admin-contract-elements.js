/* global jQuery */
(function ($) {
  'use strict';

  function renumberRows($tbody) {
    $tbody.find('tr').each(function (idx) {
      var $tr = $(this);
      $tr.find('input,select,textarea').each(function () {
        var $input = $(this);
        var name = $input.attr('name');
        if (!name) return;
        name = name.replace(/vgbm_rent_elements\[\d+\]/, 'vgbm_rent_elements[' + idx + ']');
        $input.attr('name', name);
      });
    });
  }

  $(function () {
    var $table = $('#vgbm-rent-elements');
    if (!$table.length) return;

    var $tbody = $table.find('tbody');
    var $add = $('#vgbm-add-rent-element');

    $tbody.on('click', '.vgbm-remove-row', function (e) {
      e.preventDefault();
      $(this).closest('tr').remove();
      renumberRows($tbody);
    });

    $add.on('click', function (e) {
      e.preventDefault();

      var idx = $tbody.find('tr').length;
      var row = '' +
        '<tr>' +
          '<td><input type="text" class="widefat" name="vgbm_rent_elements[' + idx + '][label]" value=""></td>' +
          '<td><input type="number" step="0.01" min="0" class="widefat" name="vgbm_rent_elements[' + idx + '][amount]" value="0.00"></td>' +
          '<td style="text-align:center;"><input type="checkbox" name="vgbm_rent_elements[' + idx + '][indexable]" value="1" checked></td>' +
          '<td style="text-align:center;"><button type="button" class="button vgbm-remove-row">&times;</button></td>' +
        '</tr>';

      $tbody.append(row);
    });
  });
})(jQuery);

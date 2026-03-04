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
        name = name.replace(/vgbm_participants\[\d+\]/, 'vgbm_participants[' + idx + ']');
        $input.attr('name', name);
      });
    });
  }

  $(function () {
    var $table = $('#vgbm-allocation-participants');
    if (!$table.length) return;

    var $tbody = $table.find('tbody');
    var $add = $('#vgbm-add-participant');

    $tbody.on('click', '.vgbm-remove-row', function (e) {
      e.preventDefault();
      $(this).closest('tr').remove();
      renumberRows($tbody);
    });

    $add.on('click', function (e) {
      e.preventDefault();
      var idx = $tbody.find('tr').length;

      var $firstSelect = $tbody.find('select').first();
      var optionsHtml = $firstSelect.length ? $firstSelect.html() : '<option value="0">— Select —</option>';

      var row = '' +
        '<tr>' +
          '<td><select class="widefat" name="vgbm_participants[' + idx + '][contract_id]">' + optionsHtml + '</select></td>' +
          '<td><input type="number" step="0.01" min="0" max="100" class="widefat" name="vgbm_participants[' + idx + '][share]" value="0"></td>' +
          '<td style="text-align:center;"><button type="button" class="button vgbm-remove-row">&times;</button></td>' +
        '</tr>';

      $tbody.append(row);
    });
  });
})(jQuery);

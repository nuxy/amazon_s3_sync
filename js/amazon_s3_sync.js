(function($) {
  Drupal.behaviors.amazon_s3_sync = {
    attach: function(context, settings) {
      var table = $('#edit-aws-regions');

      // Enable Endpoint options for selected Regions.
      function enableSelected() {
        var options = $('#edit-endpoint option');

        $('tbody .form-checkbox', table).each(function(index) {
          if (this.checked) {
            options[index].disabled = false;
          }
          else {
            options[index].disabled = true;
          }
        });
      }

      enableSelected();

      // Bind table events to avoid conflicts.
      table.on('click', enableSelected);
    }
  };
})(jQuery);

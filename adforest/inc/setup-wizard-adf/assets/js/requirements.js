// File: inc/setup-wizard-adf/assets/js/requirements.js
jQuery(function($) {
  // Tab switching for Requirements step
  $(document).on('click', '.sb-requirements-tabs button[role="tab"]', function() {
    var $btn = $(this);
    var tab = $btn.data('tab');
    if (!tab) return;

    // Toggle selected state
    $btn
      .attr('aria-selected', 'true')
      .addClass('active')
      .siblings('[role="tab"]')
        .attr('aria-selected', 'false')
        .removeClass('active');

    // Show/hide panels
    $('.tab-panel').each(function() {
      var $panel = $(this);
      if ($panel.data('tab') === tab) {
        $panel.removeAttr('hidden');
      } else {
        $panel.attr('hidden', 'hidden');
      }
    });
  });

  // Back & Continue navigation
  $(document).on('click', '.sb-btn-back, .sb-btn-next', function() {
    var next = $(this).data('next');
    if (next) {
      $('.sb-wizard-steps [data-step="' + next + '"]').trigger('click');
    }
  });
});

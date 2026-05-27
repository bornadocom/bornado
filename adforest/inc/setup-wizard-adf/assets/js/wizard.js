// File: inc/setup-wizard-adf/assets/js/wizard.js
jQuery(function($) {
  const ajaxUrl       = adforestWizard.ajax_url;
  const nonce         = adforestWizard.nonce;
  const licenseStatus = adforestWizard.license_status;

  /**
   * Helper to instantly switch panels and sidebar without AJAX
   */
  function showStep(step, step_type='') {
    $('.sb-wizard-panel.step').attr('aria-hidden','true').removeClass('active');
    $('.sb-wizard-steps [role="tab"]').attr('aria-selected','false').removeClass('active');

    const params = new URLSearchParams(window.location.search);
    if (params.get('reload') === 'license') {
      step='license';
      loadStep(step);
      const url = new URL(window.location.href);
      url.searchParams.delete('reload');
      window.history.replaceState({}, document.title, url.toString());   
    }

    $(`#${step}`).attr('aria-hidden','false').addClass('active');
    $(`.sb-wizard-steps [data-step="${step}"]`).attr('aria-selected','true').addClass('active');
    $(window).scrollTop(0);
  }

  /**
   * Load a step via AJAX, enforcing license gating from server-side value
   */
  function loadStep(step) {
    // Only allow steps beyond license if the license is valid
    if (step !== 'license' && step !== 'welcome') {
      if (licenseStatus !== 'valid') {
        alert(adforestWizard.license.enter_code || 'Please activate your license first.');
        return;
      }
    }

    // Highlight sidebar tab
    $('.sb-wizard-steps [role="tab"]').attr('aria-selected','false').removeClass('active');
    $(`.sb-wizard-steps [data-step="${step}"]`).attr('aria-selected','true').addClass('active');

    // Show loading indicator
    const $main = $('.sb-wizard-content').first();
    $main.html('<div class="sb-loading">&hellip;</div>');

    // Fetch HTML for the step
    $.post(ajaxUrl, { action: 'adforest_get_step', nonce, step }, function(res) {
      if (res.success && res.data.html) {
        $main.html(res.data.html);
      } else if (res.success) {
        // Fallback to direct show
        showStep(step);
      } else {
        const msg = res.data && res.data.message ? res.data.message : 'Error loading step.';
        $main.html(`<div class="sb-error">${msg}</div>`);
      }
    });
  }

  // Sidebar tab click
  $(document).on('click', '.sb-wizard-steps [role="tab"]', function() {
    const step = $(this).data('step');
    if (step) loadStep(step);
  });

  // Next/Continue button click
  $(document).on('click', '.sb-btn-next', function(e) {
    e.preventDefault();
    const next = $(this).data('next');
    if (next) loadStep(next);
  });

  // Back button click
  $(document).on('click', '.sb-btn-back', function(e) {
    e.preventDefault();
    const prev = $(this).data('prev') || $(this).data('next');
    if (prev) loadStep(prev);
  });

  // On initial load: show the first selected tab
  const initial = $('.sb-wizard-steps [aria-selected="true"]').data('step');
  if (initial) showStep(initial);
});

jQuery(function ($) {
  function handleLicenseBtn($btn) {
    var action = $btn.data('action'),
      next = $btn.data('next'),
      nonce = $btn.data('nonce'),
      $input = $('#sb-license-field'),
      $error = $('#sb-license-error'),
      spinner = $('.sb-spinner'),
      data = { action: action, nonce: nonce };

    if (action === 'adforest_deactivate_license') {
      if (!window.confirm(adforestWizard.license.confirm_deactivate || 'Are you sure you want to deactivate your license?')) {
        return;
      }
      data.license_key = $input.val() ? $input.val().trim() : '';
    }

    $error.text('').hide();
    spinner.addClass('active');
    $btn.prop('disabled', true);

    if (action === 'adforest_verify_license') {
      var code = $input.val() ? $input.val().trim() : '';
      if (!code) {
        $error.text(adforestWizard.license.enter_code).show();
        spinner.removeClass('active');
        $btn.prop('disabled', false);
        return;
      }
      data.license_key = code;
    }

    $.post(adforestWizard.ajax_url, data)
      .done(function (res) {
        spinner.removeClass('active');
        $btn.prop('disabled', false);

        if (res.success) {
          $error
            .removeClass('field-error')
            .addClass('field-success')
            .text(res.data.message || adforestWizard.license.success)
            .show();

          setTimeout(function () {
            if (action === 'adforest_verify_license') {
              $('.state-inactive, .footer-inactive').hide();
              $('.state-active, .footer-active').show();
              $('.sb-btn-continue').prop('disabled', false);
              if (typeof adforestWizard.showStep === 'function') {
                adforestWizard.showStep('license', 'license');
              }

              const url = new URL(window.location.href);
              url.searchParams.set('reload', 'license');
              window.location.href = url.toString();

            } else if (action === 'adforest_deactivate_license') {
              $('.state-active, .footer-active').hide();
              $('.state-inactive, .footer-inactive').show();
              $('.sb-btn-continue').prop('disabled', true);
              if (typeof adforestWizard.showStep === 'function') {
                adforestWizard.showStep('license');
              }
              window.location.reload();
            }


          }, 1000);

        } else {
          var msg = res.data && res.data.message
            ? res.data.message
            : adforestWizard.license.invalid_code;
          $error
            .removeClass('field-success')
            .addClass('field-error')
            .text(msg)
            .show();
        }
      })
      .fail(function (jqXHR) {
        spinner.removeClass('active');
        $btn.prop('disabled', false);
        var msg = adforestWizard.license.error;
        if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
          msg = jqXHR.responseJSON.data.message;
        }
        $error
          .removeClass('field-success')
          .addClass('field-error')
          .text(msg)
          .show();
      });
  }

  $(document).on('click', '.sb-btn-verify, .sb-btn-deactivate', function (e) {
    e.preventDefault();
    handleLicenseBtn($(this));
  });
});

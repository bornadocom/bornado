// File: inc/setup-wizard-adf/assets/js/plugins.js
jQuery(function($) {
  const ajaxUrl = adforestWizard.ajax_url;
  const nonce   = adforestWizard.nonce;

  // Hide spinners for already installed/active or skipped plugins
  $('.plugin-item').each(function() {
    const file      = $(this).data('plugin-file'),
          base      = file.replace(/[^a-z0-9_-]/gi, '-'),
          installed = !!$(this).find('.col-status .dashicons-yes-alt').length,
          active    = !!$(this).find('.col-active .dashicons-yes-alt').length,
          optional  = $(this).data('required') === 0;
    if ((installed && active) || optional) {
      $('#spinner-' + base).hide();
    }
  });

  // Refresh statuses
  $(document).on('click', '.sb-wizard-steps [data-step="plugins"]', function() {
    const files = $('.plugin-item').map(function() {
      return $(this).data('plugin-file');
    }).get();
    if (!files.length) return;

    $.post(ajaxUrl, { action: 'adforest_check_plugins', nonce, plugins: files }, function(res) {
      if (!res.success) return;
      $.each(res.data, function(file, status) {
        const base = file.replace(/[^a-z0-9_-]/gi, '-');
        $('#status-' + base).html(
          `<span class="dashicons ${status.installed ? 'dashicons-yes-alt' : 'dashicons-no-alt'}"></span>`
        );
        $('#active-' + base).html(
          `<span class="dashicons ${status.active ? 'dashicons-yes-alt' : 'dashicons-no-alt'}"></span>`
        );
        $('#version-' + base).text(status.version || '');
        const slug = file.split('/')[0],
              $cb  = $(`input[data-plugin-file="${slug}"]`);
        $cb.prop('checked', status.installed).prop('disabled', status.installed);
        if ((status.installed && status.active) || !status.installed) {
          $('#spinner-' + base).hide();
        }
      });
    });
  });


$(document).on('click', '.sb-btn-install-activate', function() {
  const btn    = $(this),
        next   = btn.data('next'),
        items  = $('.plugin-item')
                    .filter((_, el) => $(el).find('input[type="checkbox"]').is(':checked'))
                    .toArray();

  if (!items.length) {
    return $('.sb-wizard-steps [data-step="' + next + '"]').trigger('click');
  }

  let failedSlugs = [];
  btn.prop('disabled', true).text(adforestWizard.installing_text);
  $('.sb-btn-retry-failed').remove(); // clear old retry

  function installNext(index) {
    if (index >= items.length) {
      // all done
      btn.prop('disabled', false).text(adforestWizard.next_text);

      if (failedSlugs.length) {
        btn.after(`
          <button type="button" class="sb-btn sb-btn-secondary sb-btn-retry-failed" style="margin-left:.5em;">
            ${adforestWizard.retry_failed_text}
          </button>
        `);
      }

      // advance if all required succeeded
      const allRequiredActive = $('.plugin-item[data-required="1"]').toArray().every(el =>
        $(el).find('.col-active .dashicons-yes-alt').length
      );
      if (allRequiredActive && !failedSlugs.length) {
        setTimeout(() => {
          $('.sb-wizard-steps [data-step="' + next + '"]').trigger('click');
        }, 3000);
      }
      return;
    }

    const $item   = $(items[index]),
          file    = $item.data('plugin-file'),
          slug    = file.split('/')[0],
          base    = file.replace(/[^a-z0-9_-]/gi, '-'),
          payload = { action: 'adforest_install_plugins', nonce, install: { [slug]: true } };

    // reset UI state
    $item.removeClass('plugin-failed');
    $item.find('.install-error').remove();

    // show spinner
    $('#spinner-' + base).show().addClass('is-active');

    $.post(ajaxUrl, payload)
      .done(function(res) {
        // hide spinner
        $('#spinner-' + base).removeClass('is-active').hide();

        if (!res.success || !res.data || !res.data[slug]) {
          // unexpected response
          failedSlugs.push(slug);
          $item.addClass('plugin-failed')
               .append(`<div class="install-error">${adforestWizard.error_text}</div>`);
        } else {
          const entry  = res.data[slug],
                status = (typeof entry === 'string' ? entry : entry.status),
                version = entry.version || '';

          if (status === 'failed') {
            failedSlugs.push(slug);
            const errs = Array.isArray(entry.errors)
              ? entry.errors.join('; ')
              : adforestWizard.error_text;

            $item.addClass('plugin-failed')
                 .append(`<div class="install-error">${errs}</div>`);
          } else {
            // success: active or installed
            const icon = status === 'active'
              ? 'dashicons-yes-alt'
              : 'dashicons-no-alt';

            $(`#status-${base}`).html(`<span class="dashicons ${icon}"></span>`);
            $(`#active-${base}`).html(`<span class="dashicons ${icon}"></span>`);
            if (version) {
              $(`#version-${base}`).text(version);
            }
            $item.find('input[type="checkbox"]')
                 .prop('checked', true)
                 .prop('disabled', true);
          }
        }
      })
      .fail(function() {
        // network/server error
        $('#spinner-' + base).removeClass('is-active').hide();
        failedSlugs.push(slug);
        $item.addClass('plugin-failed')
             .append(`<div class="install-error">${adforestWizard.error_text}</div>`);
      })
      .always(function() {
        installNext(index + 1);
      });
  }

  installNext(0);
});




  // Retry failed installs
  $(document).on('click', '.sb-btn-retry-failed', function() {
    $('.plugin-failed input[type="checkbox"]').prop('checked', true);
    $('.install-error').remove();
    $('.plugin-failed').removeClass('plugin-failed');
    $(this).remove();
    $('.sb-btn-install-activate').trigger('click');
  });

  // Bulk checks
  $(document).on('click', '.sb-btn-check-all', function() {
    $('.plugin-item input[type="checkbox"]').not(':disabled').prop('checked', true);
  });
  $(document).on('click', '.sb-btn-check-required', function() {
    $('.plugin-item').each(function() {
      const $cb = $(this).find('input[type="checkbox"]');
      if ($(this).data('required') === 1 && !$cb.prop('disabled')) {
        $cb.prop('checked', true);
      }
    });
  });

  // Navigation
  $(document).on('click', '.sb-btn-back, .sb-btn-next', function() {
    const next = $(this).data('next');
    if (next) $('.sb-wizard-steps [data-step="' + next + '"]').trigger('click');
  });
});

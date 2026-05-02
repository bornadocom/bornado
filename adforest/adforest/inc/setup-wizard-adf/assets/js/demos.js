// File: inc/setup-wizard-adf/assets/js/demos.js
jQuery(function($) {
  console.log('demos.js loaded');

  // Toggle parts fieldsets based on mode
  $(document).on('change', '.import-mode-fieldset input[type=radio]', function() {
    const $ui = $(this).closest('.import-ui');
    if (this.value === 'new') {
      $ui.find('.parts-fieldset.fresh-parts').show().find('input').prop('disabled', false);
      $ui.find('.parts-fieldset.existing-parts').hide().find('input').prop('disabled', true);
    } else {
      $ui.find('.parts-fieldset.fresh-parts').hide().find('input').prop('disabled', true);
      $ui.find('.parts-fieldset.existing-parts').show().find('input').prop('disabled', false);
    }
  });

  // Finalize import UI and reload demo step
  function finalizeImport($card) {
    const slug = $card.data('demo');
    $card
      .addClass('imported')
      .removeClass('is-locked is-importing');

    const $start = $card.find('.sb-btn-confirm-stream-import');
    // remove spinner next to it
    $start.prop('disabled', true);
    $start.next('.spinner').remove();
    $card.find('.sb-btn-stop-import').prop('disabled', true);

    $card.find('.actions button')
         .prop('disabled', true)
         .text(sb_vars.labels.complete);

    $('.sb-btn-continue').prop('disabled', false).focus();

    // reload demo step
    reloadStep('demo');
  }

  // Recursive runner
  function runImportSequence($card, parts, mode) {
    const slug = $card.data('demo'),
          total = parts.length;
    const $start = $card.find('.sb-btn-confirm-stream-import'),
          $stop  = $card.find('.sb-btn-stop-import');

    function runPart(i) {
      if (i >= total) return;
      const part = parts[i];
      $.post(sb_vars.ajax_url, {
        action:      'adforest_import_demo_step',
        nonce:       sb_vars.nonce,
        demo:        slug,
        part:        part,
        import_mode: mode,
        mark_done:   (part === 'options' || part === 'new-options' || part === 'pages') ? 1 : 0
      }).done(function(res) {
        const label = sb_vars.labels.parts[part] || part;
        const msg   = label + ' ' + sb_vars.labels.complete.toLowerCase();
        $('#log-' + slug).append(msg + '\n').scrollTop(1e9);
        $('#status-' + slug).text(msg);
        const prog = Math.round(((i+1)/total)*100);
        $('#progress-' + slug).css('width', prog + '%').attr('aria-valuenow', prog);

        if (i === total - 1 && (part === 'options' || part === 'new-options' || part === 'pages')) {
          finalizeImport($card);
        } else {
          runPart(i+1);
        }
      }).fail(function(xhr) {
        const err = xhr.responseJSON?.data?.message || sb_vars.labels.ajaxError;
        $('#log-' + slug).append(err + '\n');
        $card.find('.import-error').text(err).show();
        // restore buttons
        $start.prop('disabled', false);
        $start.next('.spinner').remove();
        $stop.prop('disabled', true);
      });
    }

    // initialize UI
    $('#progress-' + slug).css('width', '0%').attr('aria-valuenow', 0);
    $('#status-'   + slug).empty();
    $('#log-'      + slug).empty();
    $card.find('.import-error').hide().empty();

    $card.addClass('is-importing');
    $start.prop('disabled', true);
    // insert spinner after the button
    $start.after('<span class="spinner is-active"></span>');
    $stop.prop('disabled', false);

    runPart(0);
  }

  // Select / Cancel / Confirm
  $(document).on('click', '.sb-btn-select-demo', function(e) {
    e.preventDefault();
    const $card = $(this).closest('.demo-card');
    // focus: select this card
    $card.addClass('demo-selected');
    // hide other cards
    $card.siblings('.demo-card').hide();
    // show confirmation
    $card.find('.actions').hide();
    $card.find('.import-confirmation').show();
    // show try different
    $card.find('.try-different-wrap').show();
  });
  $(document).on('click', '.sb-btn-cancel-select', function(e) {
    e.preventDefault();
    const $card = $(this).closest('.demo-grid').find('article.demo-card').show();
    $card.find('.import-confirmation').hide();
    $card.find('.actions').show();
  });
  $(document).on('click', '.sb-btn-confirm-import', function(e) {
    e.preventDefault();
    const $card = $(this).closest('.demo-card');
    $card.find('.import-confirmation').hide();
    $card.addClass('is-importing').find('.import-ui').show();
    $card.find('.import-mode-fieldset input:checked').trigger('change');
  });

  // Try Different Demo
  $(document).on('click', '.sb-btn-try-different', function(e) {
    e.preventDefault();
    const $card = $(this).closest('.demo-card');
    // remove focus
    $card.removeClass('demo-selected');
    // show all cards
    $('.demo-card').show();
    // restore initial UI
    $card.find('.try-different-wrap').hide();
    $card.find('.import-confirmation').hide();
    $card.find('.actions').show();
  });

  // Start import
jQuery(document).on('click', '.sb-btn-confirm-stream-import', function(e) {
    e.preventDefault();

    // Confirmation dialog
    var msg = sb_vars.labels.importBTNConfirm;
    if ( ! confirm( msg ) ) {
        return;
    }

    const $card = $(this).closest('.demo-card'),
          slug  = $card.data('demo'),
          mode  = $card.find('input[name="mode_' + slug + '"]:checked').val(),
          parts = $card.find('input[name="parts_' + slug + '[]"]:enabled:checked')
                       .map(function(){ return this.value; })
                       .get();

    if (! parts.length) {
      $card.find('.import-error')
           .text(sb_vars.labels.importAllWarning)
           .show();
      return;
    }

    runImportSequence($card, parts, mode);
});

  // Stop import
  $(document).on('click', '.sb-btn-stop-import', function(e) {
    e.preventDefault();
    const $stop = $(this).prop('disabled', true),
          $card = $stop.closest('.demo-card'),
          slug  = $card.data('demo');
    $('#log-' + slug).append(sb_vars.labels.stalled + '\n').scrollTop(1e9);
    // restore start button
    const $start = $card.find('.sb-btn-confirm-stream-import');
    $start.prop('disabled', false);
    $start.next('.spinner').remove();
  });

  // Reset demo & reload step
  $(document).on('click', '#reset-demo', function(e) {
    e.preventDefault();
    if (!confirm(sb_vars.labels.resetConfirm)) return;
    $.post(sb_vars.ajax_url, { action: 'adforest_reset_demo', nonce: sb_vars.nonce })
      .done(function(res) {
        if (res.success) {
          $('.demo-card')
            .removeClass('imported is-locked is-importing')
            .find('.actions').show();
          $('.sb-btn-select-demo').prop('disabled', false);
          $('.import-confirmation, .import-ui').hide();
          $('.progress-bar').css('width','0%').attr('aria-valuenow',0);
          $('.import-status, .import-log').empty();
          $('.import-error').hide().empty();
          $('.sb-btn-continue').prop('disabled', true);
          alert(sb_vars.labels.resetDone);
          reloadStep('demo');
        } else {
          alert(res.data.message || sb_vars.labels.ajaxError);
        }
      })
      .fail(function() {
        alert(sb_vars.labels.ajaxError);
      });
  });

  // Utility: reload a wizard step via AJAX
  function reloadStep(step) {
    $.post(sb_vars.ajax_url, {
      action: 'adforest_get_step',
      nonce:  sb_vars.nonce,
      step:   step
    }).done(function(res) {
      if (res.success && res.data.html) {
        $('#demo').replaceWith(res.data.html);
      }
    });
  }

  // Navigation: Continue / Back
  $(document).on('click', '.sb-btn-continue', function(e) {
    e.preventDefault();
    const next = $(this).data('next') || 'done';
    reloadStep(next);
  });
  $(document).on('click', '.sb-btn-back', function(e) {
    e.preventDefault();
    const prev = $(this).data('prev') || $(this).data('next');
    reloadStep(prev);
  });
});
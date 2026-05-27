<?php
// File: inc/setup-wizard-adf/views/step-license.php
if ( ! defined( 'ABSPATH' ) ) exit;

$status = get_option( 'adforest_license_status', '' );
$nonce  = esc_attr( wp_create_nonce( 'adforest_wizard_nonce' ) );
?>

<div class="sb-wizard-panel license-step" data-step="license" data-nonce="<?php echo esc_attr($nonce); ?>">
  <div class="license-card">

    <header class="license-header">
      <h3 id="license-heading" style="color: #fff !important;">
        <?php esc_html_e( 'Activate Your License', 'adforest' ); ?>
      </h3>
      <p class="license-subtitle">
        <?php esc_html_e( 'Unlock automatic updates, premium support & demo imports', 'adforest' ); ?>
      </p>
    </header>

    <div class="license-body">
      <!-- INACTIVE -->
      <div class="state-inactive"<?php if ( 'valid' === $status ) echo ' style="display:none"'; ?>>
        <p class="license-intro">
          <?php esc_html_e( 'Enter your Envato purchase code to continue:', 'adforest' ); ?>
        </p>
        <div class="field-group">
          <label for="sb-license-field">
            <?php esc_html_e( 'Purchase Code', 'adforest' ); ?>
          </label>
          <input
            type="text"
            id="sb-license-field"
            class="field-input"
            placeholder="<?php esc_attr_e( 'XXXXX-XXXXX-XXXXX-XXXXX', 'adforest' ); ?>"
          />
          <div id="sb-license-error" class="field-error" aria-live="polite"></div>
        </div>
      </div>

      <!-- ACTIVE -->
      <div class="state-active"<?php if ( 'valid' !== $status ) echo ' style="display:none"'; ?>>
        <p class="license-intro">
          <?php esc_html_e( 'Your license is currently activated. You may deactivate it to transfer it to another site.', 'adforest' ); ?>
        </p>
      </div>
    </div>

    <footer class="license-footer">
      <div class="actions">
        <?php if ( 'valid' !== $status ) : ?>
          <button
            type="button"
            class="sb-btn sb-btn-primary sb-btn-verify"
            data-action="adforest_verify_license"
            data-nonce="<?php echo esc_attr($nonce); ?>"
          >
            <?php esc_html_e( 'Verify & Continue', 'adforest' ); ?>
          </button>
        <?php else : ?>
          <button
            type="button"
            class="sb-btn sb-btn-danger sb-btn-deactivate"
            data-action="adforest_deactivate_license"
            data-nonce="<?php echo esc_attr($nonce); ?>"
          >
            <?php esc_html_e( 'Deactivate License', 'adforest' ); ?>
          </button>
          <button
            type="button"
            class="sb-btn sb-btn-primary sb-btn-next"
            data-step="license"
            data-next="requirements"
          >
            <?php esc_html_e( 'Continue', 'adforest' ); ?>
          </button>
        <?php endif; ?>
      </div>

      <button
        type="button"
        class="sb-btn sb-btn-back"
        data-step="license"
        data-next="welcome"
      >
        <?php esc_html_e( 'Back', 'adforest' ); ?>
      </button>

      <div class="spinner-wrapper">
        <span class="sb-spinner"></span>
      </div>
    </footer>

  </div>
</div>

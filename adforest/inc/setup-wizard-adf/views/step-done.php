<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<div class="done-step">
  <div class="done-card">
    <!-- Header -->
    <header class="done-header">
      <span class="dashicons dashicons-yes-alt done-icon" aria-hidden="true"></span>
      <h3 id="done-heading"><?php esc_html_e( 'Setup Complete!', 'adforest' ); ?></h3>
      <p class="done-subtitle"><?php esc_html_e( 'Your AdForest theme is ready to go. Here’s a quick recap:', 'adforest' ); ?></p>
    </header>

    <!-- Summary Grid -->
    <ul class="af-setup-summary" aria-label="<?php esc_attr_e( 'Setup Summary', 'adforest' ); ?>">
      <li>
        <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
        <?php esc_html_e( 'License verified or skipped', 'adforest' ); ?>
      </li>
      <li>
        <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
        <?php esc_html_e( 'Required plugins installed & activated', 'adforest' ); ?>
      </li>
      <li>
        <span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
        <?php esc_html_e( 'Demo content imported successfully', 'adforest' ); ?>
      </li>
    </ul>

    <!-- Resources Section -->
    <section class="af-resources" aria-labelledby="done-resources-heading">
      <h4 id="done-resources-heading"><?php esc_html_e( 'Next Steps & Resources', 'adforest' ); ?></h4>
      <div class="af-links">
        <a href="<?php echo esc_url( 'https://documentation.scriptsbundle.com/doc/adforest-gold-classified-e-commerce-wordpress-theme/' ); ?>" target="_blank" rel="noopener" class="af-link">
          <span class="dashicons dashicons-book-alt" aria-hidden="true"></span>
          <?php esc_html_e( 'Documentation', 'adforest' ); ?>
        </a>
        <a href="<?php echo esc_url( 'https://scriptsbundle.ticksy.com/' ); ?>" target="_blank" rel="noopener" class="af-link">
          <span class="dashicons dashicons-sos" aria-hidden="true"></span>
          <?php esc_html_e( 'Support Center', 'adforest' ); ?>
        </a>
        <a href="<?php echo esc_url( 'https://scriptsbundle.com/freelancer/' ); ?>" target="_blank" rel="noopener" class="af-link">
          <span class="dashicons dashicons-admin-customizer" aria-hidden="true"></span>
          <?php esc_html_e( 'Custom Development', 'adforest' ); ?>
        </a>
      </div>
    </section>

    <!-- Final Navigation -->
    <nav class="af-final-nav" aria-label="<?php esc_attr_e( 'Wizard Final Actions', 'adforest' ); ?>">
      <a href="<?php echo esc_url( admin_url() ); ?>" class="af-button af-button-primary">
        <?php esc_html_e( 'Go to Dashboard', 'adforest' ); ?>
      </a>
      <a href="<?php echo esc_url( admin_url( 'themes.php?page=adforest-setup-wizard' ) ); ?>" class="af-button af-button-secondary">
        <?php esc_html_e( 'Re-run Wizard', 'adforest' ); ?>
      </a>
    </nav>
  </div>
</div>

<?php if (! defined('ABSPATH')) exit; ?>
<?php
if (is_plugin_active('sb_framework/index.php')) {
  deactivate_plugins('sb_framework/index.php', true);
}

if (is_plugin_active('js_composer/js_composer.php')) {
  deactivate_plugins('js_composer/js_composer.php', true);
}

if (is_plugin_active('one-click-demo-import/one-click-demo-import.php')) {
  deactivate_plugins('one-click-demo-import/one-click-demo-import.php', true);
}
?>
<div class="sb-welcome-panel">
  <?php
  if (get_option('adforest_license_status') !== 'valid') {
    echo '<div class="notice notice-warning adforest-license-warning" style="width:100%; padding:15px; border-left:5px solid #e67e22; background:#fff8e1;">';
    echo '<p><strong>' . esc_html__('🔐 License Activation Required', 'adforest') . '</strong></p>';
    echo '<p>' . esc_html__('Please activate your license in the next step to continue using AdForest.', 'adforest') . '</p>';
    echo '<p>' . sprintf(
      esc_html__('Click %1$shere%2$s to begin the activation process.', 'adforest'),
      '<a class="licence-activation-start" href="javascript:void(0);" style="color:#e67e22; font-weight:bold;">',
      '</a>'
    ) . '</p>';
    echo '</div>';
  ?>
    <script>
      jQuery(document).ready(function($) {
        $('.licence-activation-start').on('click', function(e) {
          e.preventDefault();
          $('.sb-btn.sb-btn-primary.sb-btn-next').trigger('click');
        });
      });
    </script>
  <?php
  }
  ?>
  <div class="adforest-update-warning">
    <div class="warning-header">
      <div class="warning-icon">🚨</div>
      <h3><?php echo esc_html__('AdForest v6.0 – Major Update', 'adforest'); ?></h3>
    </div>

    <div class="warning-content">
      <p class="warning-intro"><?php echo esc_html__("We're excited to introduce AdForest v6.0 with major performance and builder enhancements. However, this update includes critical changes that require your attention.", 'adforest'); ?></p>

      <div class="warning-section">
        <h4><?php echo esc_html__('Who should read this:', 'adforest'); ?></h4>
        <p><?php echo esc_html__('If your site is already live and using WPBakery, this update notice is for you.', 'adforest'); ?></p>
      </div>

      <div class="warning-section">
        <h4><?php echo esc_html__('Important:', 'adforest'); ?></h4>
        <p><?php echo esc_html__('WPBakery Page Builder is no longer supported in version 6.0. The theme now exclusively supports Elementor.', 'adforest'); ?></p>
      </div>

      <div class="warning-critical">
        <p><?php echo esc_html__('⚠️ WARNING: Never use Fresh Import on a live/existing website — it will erase all your data.', 'adforest'); ?></p>
      </div>

      <div class="warning-checklist">
        <h4><?php echo esc_html__('Before You Update:', 'adforest'); ?></h4>
        <ul>
          <li><?php echo esc_html__('✅ Always take a full backup of your website before updating.', 'adforest'); ?></li>
          <li><?php echo esc_html__('⚠️ If you are using WPBakery, do not update until you have migrated your pages to Elementor.', 'adforest'); ?></li>
          <li><?php echo esc_html__('🛠️ Test the update on a staging site first.', 'adforest'); ?></li>
          <li><?php echo esc_html__('💾 Use a plugin like UpdraftPlus or All-in-One WP Migration to back up your site.', 'adforest'); ?></li>
          <li><?php echo esc_html__('🔁 After updating, recheck all customizations, widgets, and page templates.', 'adforest'); ?></li>
        </ul>
      </div>

      <div class="warning-footer">
        <p><?php echo esc_html__("This update is a big leap forward, but it's crucial to proceed with caution.", 'adforest'); ?></p>
        <p>
          <?php echo sprintf(
            esc_html__('Need help? Visit our %1$sSupport Portal%2$s.', 'adforest'),
            '<a href="https://scriptsbundle.ticksy.com/" target="_blank" class="support-link">',
            '</a>'
          ); ?>
        </p>
      </div>
    </div>
  </div>

  <style>
    .adforest-update-warning {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      border: 1px solid #f59e0b;
      border-radius: 16px;
      padding: 0;
      /* margin: 2rem 0; */
      box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.1), 0 4px 6px -2px rgba(245, 158, 11, 0.05);
      overflow: hidden;
      position: relative;
    }

    .adforest-update-warning::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 4px;
      background: linear-gradient(90deg, #f59e0b, #d97706, #f59e0b);
      animation: shimmer 2s ease-in-out infinite;
    }

    @keyframes shimmer {

      0%,
      100% {
        opacity: 0.8;
      }

      50% {
        opacity: 1;
      }
    }

    .warning-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1.5rem 2rem 1rem;
      background: rgba(255, 255, 255, 0.5);
    }

    .warning-icon {
      font-size: 2rem;
      animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pulse {

      0%,
      100% {
        transform: scale(1);
      }

      50% {
        transform: scale(1.1);
      }
    }

    .warning-header h3 {
      margin: 0;
      color: #92400e;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .warning-content {
      padding: 0 2rem 2rem;
    }

    .warning-intro {
      font-size: 1.1rem;
      color: #92400e;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .warning-section {
      margin-bottom: 1.5rem;
    }

    .warning-section h4 {
      color: #92400e;
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0 0 0.5rem;
    }

    .warning-section p {
      color: #78350f;
      margin: 0;
      line-height: 1.5;
    }

    .warning-critical {
      background: rgba(239, 68, 68, 0.1);
      border: 1px solid #ef4444;
      border-radius: 12px;
      padding: 1rem;
      margin: 1.5rem 0;
    }

    .warning-critical p {
      color: #dc2626;
      font-weight: 600;
      margin: 0;
      font-size: 1.05rem;
    }

    .warning-checklist h4 {
      color: #92400e;
      font-size: 1.1rem;
      font-weight: 600;
      margin: 0 0 1rem;
    }

    .warning-checklist ul {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .warning-checklist li {
      background: rgba(255, 255, 255, 0.7);
      border-radius: 8px;
      padding: 0.75rem 1rem;
      margin-bottom: 0.5rem;
      color: #78350f;
      font-weight: 500;
      transition: all 0.3s ease;
    }

    .warning-checklist li:hover {
      background: rgba(255, 255, 255, 0.9);
      transform: translateX(4px);
    }

    .warning-footer {
      margin-top: 1.5rem;
      padding-top: 1.5rem;
      border-top: 1px solid rgba(245, 158, 11, 0.3);
    }

    .warning-footer p {
      color: #78350f;
      margin: 0.5rem 0;
      line-height: 1.5;
    }

    .support-link {
      color: #d97706;
      font-weight: 600;
      text-decoration: none;
      border-bottom: 2px solid transparent;
      transition: all 0.3s ease;
    }

    .support-link:hover {
      color: #92400e;
      border-bottom-color: #92400e;
    }
  </style>

  <aside class="sb-welcome-graphic">
    <img src="<?php echo esc_url(get_template_directory_uri() . '/screenshot.png'); ?>"
      alt="<?php esc_attr_e('AdForest Preview', 'adforest'); ?>" />
  </aside>

  <div class="sb-welcome-content">
    <h1 id="welcome-heading" class="sb-welcome-title">
      <?php _e('Welcome to AdForest Setup', 'adforest'); ?>
    </h1>

    <p class="sb-welcome-version">
      <?php 
      $version = '';
      
      if (empty($version)) {
        $theme_data = wp_get_theme(get_template());
        if ($theme_data && $theme_data->exists()) {
          $version = $theme_data->get('Version');
        }
      }
      
      printf(__('Theme Version: %s', 'adforest'), esc_html($version)); 
      ?>
    </p>

    <ul class="sb-welcome-list">
      <li><?php _e('Verify your license',       'adforest'); ?></li>
      <li><?php _e('Check system requirements',  'adforest'); ?></li>
      <li><?php _e('Install required plugins',   'adforest'); ?></li>
      <li><?php _e('Import demo content',        'adforest'); ?></li>
      <li><?php _e('Finish setup',               'adforest'); ?></li>
    </ul>

    <div class="sb-welcome-actions">
      <button
        type="button"
        class="sb-btn sb-btn-primary sb-btn-next"
        data-step="welcome"
        data-next="license"
        aria-describedby="welcome-heading">
        <?php _e('Let’s Get Started', 'adforest'); ?>
      </button>
      <div class="sb-spinner" role="status" aria-hidden="true"></div>
    </div>
  </div>
</div>
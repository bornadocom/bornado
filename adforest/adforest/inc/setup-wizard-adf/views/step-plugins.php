<?php if (!defined('ABSPATH')) exit; ?>
<div id="plugins" class="step plugins-step">

    <?php
            if ( is_plugin_active( 'sb_framework/index.php' ) ) {
                deactivate_plugins( 'sb_framework/index.php', true );
            }

            if ( is_plugin_active( 'js_composer/js_composer.php' ) ) {
                deactivate_plugins( 'js_composer/js_composer.php', true );
            }

            if ( is_plugin_active( 'one-click-demo-import/one-click-demo-import.php' ) ) {
                deactivate_plugins( 'one-click-demo-import/one-click-demo-import.php', true );
            }
    ?>

    <div class="demo-plugin-header">
        <div class="demo-plugin-header-title">
            <h3 id="plugins-heading"><?php esc_html_e('Plugins', 'adforest'); ?></h3>
            <p class="plugins-intro">
                <?php esc_html_e('Below are the plugins recommended or required for AdForest. Required plugins are always enabled.', 'adforest'); ?>
            </p>
        </div>

        <div class="plugins-controls">
            <button type="button" class="sb-btn sb-btn-secondary sb-btn-check-all">
                <?php esc_html_e('Check All', 'adforest'); ?>
            </button>
        </div>
    </div>

    <div class="plugin-list-header">
        <div class="col-name"><?php esc_html_e('Plugin', 'adforest'); ?></div>
        <div class="col-required"><?php esc_html_e('Required', 'adforest'); ?></div>
        <div class="col-install"><?php esc_html_e('Install', 'adforest'); ?></div>
        <div class="col-status"><?php esc_html_e('Installed?', 'adforest'); ?></div>
        <div class="col-active"><?php esc_html_e('Active?', 'adforest'); ?></div>
        <div class="col-version"><?php esc_html_e('Version', 'adforest'); ?></div>
        <div class="col-loading"><?php esc_html_e('Loading', 'adforest'); ?></div>
    </div>

    <div class="plugin-list">
        <?php
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $all_plugins = get_plugins();

        foreach (\Adforest_Setup_Wizard::get_plugins() as $plugin) :
            $slug = sanitize_text_field($plugin['slug']);

            // Resolve the real plugin file path
            $file_path = '';
            foreach ($all_plugins as $pfile => $meta) {
                if (strpos($pfile, $slug . '/') === 0 || $pfile === $slug . '.php') {
                    $file_path = $pfile;
                    break;
                }
            }
            if (!$file_path) {
                $file_path = $slug . '/' . $slug . '.php';
            }

            $base = sanitize_title(str_replace('/', '-', $file_path));
            $required = !empty($plugin['required']);
            $is_installed = isset($all_plugins[$file_path]);
            $is_active = is_plugin_active($file_path);
            $version = $is_installed ? $all_plugins[$file_path]['Version'] : '';
            ?>
            <div class="plugin-item"
                 data-plugin-file="<?php echo esc_attr($file_path); ?>"
                 data-required="<?php echo esc_attr($required) ? '1' : '0'; ?>">
                <div class="col-name"><?php echo esc_html($plugin['name']); ?></div>
                <div class="col-required">
          <span class="dashicons dashicons-<?php echo esc_attr($required) ? 'yes-alt' : 'no-alt'; ?>"
                aria-label="<?php echo esc_attr($required)
                    ? esc_attr__('Required', 'adforest')
                    : esc_attr__('Optional', 'adforest'); ?>"></span>
                </div>
                <div class="col-install">
                    <input
                            type="checkbox"
                            data-plugin-file="<?php echo esc_attr($file_path); ?>"
                            name="install[<?php echo esc_attr($slug); ?>]"
                            value="1"
                        <?php checked($required || $is_installed); ?>
                        <?php disabled($required || $is_installed); ?> />
                </div>
                <div class="col-status" id="status-<?php echo esc_attr($base); ?>">
          <span class="dashicons dashicons-<?php echo esc_attr($is_installed) ? 'yes-alt' : 'no-alt'; ?>"
                aria-label="<?php echo esc_attr($is_installed)
                    ? esc_attr__('Installed', 'adforest')
                    : esc_attr__('Not Installed', 'adforest'); ?>"></span>
                </div>
                <div class="col-active" id="active-<?php echo esc_attr($base); ?>">
          <span class="dashicons dashicons-<?php echo esc_attr($is_active) ? 'yes-alt' : 'no-alt'; ?>"
                aria-label="<?php echo esc_attr($is_active)
                    ? esc_attr__('Active', 'adforest')
                    : esc_attr__('Inactive', 'adforest'); ?>"></span>
                </div>
                <div class="col-version" id="version-<?php echo esc_attr($base); ?>">
                    <?php echo esc_html($version); ?>
                </div>
                <div class="col-loading">
          <span class="spinner plugin-spinner"
                id="spinner-<?php echo esc_attr($base); ?>"
                role="status"
                aria-hidden="true"></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="plugins-actions">
        <button type="button"
                class="sb-btn sb-btn-primary sb-btn-install-activate"
                data-next="demo">
            <?php esc_html_e('Install & Activate', 'adforest'); ?>
        </button>
        <button type="button"
                class="sb-btn sb-btn-secondary sb-btn-back"
                data-next="demo">
            <?php esc_html_e('Continue', 'adforest'); ?>
        </button>
        <button type="button"
                class="sb-btn sb-btn-primary sb-btn-next sb-btn-continue"
                data-next="demo"
                style="display: none;">
            <?php esc_html_e('Continue to Demo', 'adforest'); ?>
        </button>
    </div>
</div>

<?php
class UserPackageManager
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_submenu']);
        add_shortcode('user_package', [$this, 'display_user_package_shortcode']);
        add_filter('manage_users_columns', [$this, 'add_user_package_column']);
        add_action('manage_users_custom_column', [$this, 'show_user_package_column_content'], 10, 3);
        add_action('wp_ajax_clear_package_revocation_logs', [$this, 'ajax_clear_package_revocation_logs']);
    }

    public function register_admin_submenu()
    {
        add_submenu_page(
            'themes.php',
            esc_html__('Bulk Assign Packages to Users', 'adforest'),
            esc_html__('Bulk Assign Packages to Users', 'adforest'),
            'manage_options',
            'user-management',
            [$this, 'render_user_management_page']
        );
        
        add_submenu_page(
            'themes.php',
            esc_html__('Bulk Unassign Packages from Users', 'adforest'),
            esc_html__('Bulk Unassign Packages', 'adforest'),
            'manage_options',
            'user-package-unassign',
            [$this, 'render_bulk_unassign_page']
        );
        
        add_submenu_page(
            'themes.php',
            esc_html__('Package Revocation Logs', 'adforest'),
            esc_html__('Package Revocation Logs', 'adforest'),
            'manage_options',
            'package-revocation-logs',
            [$this, 'render_package_revocation_logs_page']
        );
    }

    public function render_user_management_page()
    {
        if (isset($_POST['submit_user_packages']) && wp_verify_nonce($_POST['user_management_nonce'], 'user_management_action')) {
            $selected_users = isset($_POST['selected_users']) ? array_map('intval', $_POST['selected_users']) : [];
            $selected_package = isset($_POST['package_assignment']) ? sanitize_text_field($_POST['package_assignment']) : '';

            if (!empty($selected_users) && !empty($selected_package)) {
                $this->assign_package_to_users($selected_users, $selected_package);

                printf(
                    '<div class="notice notice-success"><p>%s</p></div>',
                    esc_html(sprintf(
                        __('Package "%s" assigned to %d user(s) successfully!', 'adforest'),
                        $selected_package,
                        count($selected_users)
                    ))
                );
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Please select users and a package.', 'adforest') . '</p></div>';
            }
        }

        $users = get_users(['orderby' => 'display_name', 'order' => 'ASC']);
        $packages = apply_filters('adforest_elementor_get_packages', []);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e("Assign Package to Bulk Users", "adforest"); ?></h1>
            <p><?php esc_html_e('Select users and assign packages to them.', 'adforest'); ?></p>

            <form method="post" action="">
                <?php wp_nonce_field('user_management_action', 'user_management_nonce'); ?>

                <div style="margin-bottom: 20px;">
                    <h2><?php esc_html_e('Select Users', 'adforest'); ?></h2>
                    <div style="margin-bottom: 10px;">
                        <button type="button" id="select-all-users" class="button"><?php esc_html_e('Select All', 'adforest'); ?></button>
                        <button type="button" id="deselect-all-users" class="button"><?php esc_html_e('Deselect All', 'adforest'); ?></button>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                        <?php foreach ($users as $user): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="selected_users[]" value="<?php echo esc_attr($user->ID); ?>" class="user-checkbox">
                                <?php echo esc_html($user->display_name); ?>
                                <span style="color: #666;">(<?php echo esc_html($user->user_email); ?>)</span>
                                <?php
//                                $current_package = get_user_meta($user->ID, 'assigned_package', true);
//                                if ($current_package) {
                                    //echo '<span style="color: #0073aa; font-size: 12px;"> - ' . esc_html__('Current:', 'adforest') . ' ' . esc_html($packages[$current_package] ?? $current_package) . '</span>';
//                                }
                                ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h2><?php esc_html_e('Assign Package', 'adforest'); ?></h2>
                    <select name="package_assignment" id="package_assignment" class="regular-text" required>
                        <option value=""><?php esc_html_e('Select a package...', 'adforest'); ?></option>
                        <?php foreach ($packages as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <input type="submit" name="submit_user_packages" class="button button-primary"
                           value="<?php esc_attr_e('Assign Package to Selected Users', 'adforest'); ?>">
                </div>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#select-all-users').click(function () {
                    $('.user-checkbox').prop('checked', true);
                });

                $('#deselect-all-users').click(function () {
                    $('.user-checkbox').prop('checked', false);
                });

                $('form').submit(function (e) {
                    const selectedUsers = $('.user-checkbox:checked').length;
                    const selectedPackage = $('#package_assignment').val();

                    if (selectedUsers === 0) {
                        alert('<?php echo esc_js(__('Please select at least one user.', 'adforest')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    if (!selectedPackage) {
                        alert('<?php echo esc_js(__('Please select a package.', 'adforest')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    return confirm('<?php echo esc_js(__('Are you sure you want to assign', 'adforest')); ?> "' +
                        $('#package_assignment option:selected').text() + '" ' +
                        '<?php echo esc_js(__('to', 'adforest')); ?> ' + selectedUsers + ' <?php echo esc_js(__('user(s)?', 'adforest')); ?>');
                });
            });
        </script>

        <style>
            .user-checkbox { margin-right: 8px; }
            #package_assignment { min-width: 200px; }
            .notice { margin: 20px 0; }
            .wrap h2 { margin-top: 20px; margin-bottom: 10px; }
        </style>
        <?php
    }

    public function render_bulk_unassign_page()
    {
        if (isset($_POST['submit_unassign_packages']) && wp_verify_nonce($_POST['unassign_management_nonce'], 'unassign_management_action')) {
            $selected_users = isset($_POST['selected_users']) ? array_map('intval', $_POST['selected_users']) : [];
            $selected_package = isset($_POST['package_unassignment']) ? sanitize_text_field($_POST['package_unassignment']) : '';

            if (!empty($selected_users) && !empty($selected_package)) {
                $result = $this->unassign_package_from_users($selected_users, $selected_package);
                
                if ($result['success_count'] > 0) {
                    printf(
                        '<div class="notice notice-success"><p>%s</p></div>',
                        esc_html(sprintf(
                            __('Package "%s" unassigned from %d user(s) successfully! %d users skipped (package not found).', 'adforest'),
                            $selected_package,
                            $result['success_count'],
                            $result['skipped_count']
                        ))
                    );
                } else {
                    printf(
                        '<div class="notice notice-warning"><p>%s</p></div>',
                        esc_html(sprintf(
                            __('No users had the package "%s" assigned. %d users processed.', 'adforest'),
                            $selected_package,
                            $result['skipped_count']
                        ))
                    );
                }
            } else {
                echo '<div class="notice notice-error"><p>' . esc_html__('Please select users and a package.', 'adforest') . '</p></div>';
            }
        }

        $users = get_users(['orderby' => 'display_name', 'order' => 'ASC']);
        $packages = apply_filters('adforest_elementor_get_packages', []);

        ?>
        <div class="wrap">
            <h1><?php esc_html_e("Unassign Package from Bulk Users", "adforest"); ?></h1>
            <p><?php esc_html_e('Select users and unassign a specific package from them. Users who don\'t have this package will be skipped.', 'adforest'); ?></p>

            <?php if (current_user_can('manage_options') && isset($_GET['debug']) && $_GET['debug'] === '1'): ?>
                <div class="notice notice-info">
                    <h3><?php esc_html_e('Debug Information:', 'adforest'); ?></h3>
                    <p><?php esc_html_e('Package ID you\'re trying to unassign:', 'adforest'); ?> <strong><?php echo esc_html($selected_package ?? 'None selected'); ?></strong></p>
                    <p><?php esc_html_e('Available packages:', 'adforest'); ?></p>
                    <ul>
                        <?php foreach ($packages as $pkg_id => $pkg_name): ?>
                            <li><strong><?php echo esc_html($pkg_id); ?></strong>: <?php echo esc_html($pkg_name); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('unassign_management_action', 'unassign_management_nonce'); ?>

                <div style="margin-bottom: 20px;">
                    <h2><?php esc_html_e('Select Users', 'adforest'); ?></h2>
                    <div style="margin-bottom: 10px;">
                        <button type="button" id="select-all-users" class="button"><?php esc_html_e('Select All', 'adforest'); ?></button>
                        <button type="button" id="deselect-all-users" class="button"><?php esc_html_e('Deselect All', 'adforest'); ?></button>
                    </div>

                    <div style="max-height: 300px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">
                        <?php foreach ($users as $user): ?>
                            <label style="display: block; margin-bottom: 5px;">
                                <input type="checkbox" name="selected_users[]" value="<?php echo esc_attr($user->ID); ?>" class="user-checkbox">
                                <?php echo esc_html($user->display_name); ?>
                                <span style="color: #666;">(<?php echo esc_html($user->user_email); ?>)</span>
                                <?php
                                $current_package_data = get_user_meta($user->ID, 'adforest_ads_package_details', true);
                                if ($current_package_data && is_array($current_package_data) && !empty($current_package_data)) {
                                    $package_names = [];
                                    foreach (array_keys($current_package_data) as $package_id) {
                                        $package_names[] = $packages[$package_id] ?? $package_id;
                                    }
                                    echo '<span style="color: #0073aa; font-size: 12px;"> - ' . esc_html__('Current:', 'adforest') . ' ' . esc_html(implode(', ', $package_names)) . '</span>';
                                    
                                    // Debug info
                                    if (current_user_can('manage_options') && isset($_GET['debug']) && $_GET['debug'] === '1') {
                                        echo '<br><span style="color: #999; font-size: 10px;">Debug: Package IDs: ' . esc_html(implode(', ', array_keys($current_package_data))) . '</span>';
                                    }
                                }
                                ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div style="margin-bottom: 20px;">
                    <h2><?php esc_html_e('Unassign Package', 'adforest'); ?></h2>
                    <select name="package_unassignment" id="package_unassignment" class="regular-text" required>
                        <option value=""><?php esc_html_e('Select a package to unassign...', 'adforest'); ?></option>
                        <?php foreach ($packages as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <input type="submit" name="submit_unassign_packages" class="button button-primary"
                           value="<?php esc_attr_e('Unassign Package from Selected Users', 'adforest'); ?>">
                </div>
            </form>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('#select-all-users').click(function () {
                    $('.user-checkbox').prop('checked', true);
                });

                $('#deselect-all-users').click(function () {
                    $('.user-checkbox').prop('checked', false);
                });

                $('form').submit(function (e) {
                    const selectedUsers = $('.user-checkbox:checked').length;
                    const selectedPackage = $('#package_unassignment').val();

                    if (selectedUsers === 0) {
                        alert('<?php echo esc_js(__('Please select at least one user.', 'adforest')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    if (!selectedPackage) {
                        alert('<?php echo esc_js(__('Please select a package.', 'adforest')); ?>');
                        e.preventDefault();
                        return false;
                    }

                    return confirm('<?php echo esc_js(__('Are you sure you want to unassign', 'adforest')); ?> "' +
                        $('#package_unassignment option:selected').text() + '" ' +
                        '<?php echo esc_js(__('from', 'adforest')); ?> ' + selectedUsers + ' <?php echo esc_js(__('user(s)? Users who don\'t have this package will be skipped.', 'adforest')); ?>');
                });
            });
        </script>

        <style>
            .user-checkbox { margin-right: 8px; }
            #package_unassignment { min-width: 200px; }
            .notice { margin: 20px 0; }
            .wrap h2 { margin-top: 20px; margin-bottom: 10px; }
        </style>
        <?php
    }

    public function display_user_package_shortcode()
    {
        if (!is_user_logged_in()) {
            return esc_html__('Please log in to view your package.', 'adforest');
        }

        $user_id = get_current_user_id();
        $package_data = get_user_meta($user_id, 'adforest_ads_package_details', true);
        
        if ($package_data && is_array($package_data) && !empty($package_data)) {
            $packages = apply_filters('adforest_elementor_get_packages', []);
            $package_names = [];
            foreach (array_keys($package_data) as $package_id) {
                $package_names[] = $packages[$package_id] ?? $package_id;
            }
            return esc_html__('Your assigned package(s):', 'adforest') . ' ' . esc_html(implode(', ', $package_names));
        }

        return esc_html__('No package assigned.', 'adforest');
    }

    public function add_user_package_column($columns)
    {
        $columns['assigned_package'] = esc_html__('Package', 'adforest');
        return $columns;
    }

    public function show_user_package_column_content($value, $column_name, $user_id)
    {
        if ($column_name === 'assigned_package') {
            $package_data = get_user_meta($user_id, 'adforest_ads_package_details', true);
            $packages = apply_filters('adforest_elementor_get_packages', []);
            
            if ($package_data && is_array($package_data) && !empty($package_data)) {
                $package_names = [];
                foreach (array_keys($package_data) as $package_id) {
                    $package_names[] = $packages[$package_id] ?? $package_id;
                }
                return esc_html(implode(', ', $package_names));
            } else {
                return '<em>' . esc_html__('None', 'adforest') . '</em>';
            }
        }
        return $value;
    }

    private function assign_package_to_users($user_ids, $product_id)
    {
        foreach ($user_ids as $user_id) {
            if (function_exists('adforest_give_user_package_from_admin')) {
                adforest_give_user_package_from_admin($product_id, $user_id);
            }
        }
    }

    private function unassign_package_from_users($user_ids, $package_id)
    {
        $success_count = 0;
        $skipped_count = 0;
        $packages = apply_filters('adforest_elementor_get_packages', []);
        $package_name = $packages[$package_id] ?? $package_id;
        $current_admin = wp_get_current_user();

        foreach ($user_ids as $user_id) {
            $user = get_userdata($user_id);
            if (!$user) {
                $skipped_count++;
                continue;
            }

            $package_data = get_user_meta($user_id, 'adforest_ads_package_details', true);
            
            // Check if user has the specified package
            if ($package_data && is_array($package_data) && isset($package_data[$package_id])) {
                // Remove the specific package from the array
                unset($package_data[$package_id]);
                
                // Update the user meta with the remaining packages
                if (empty($package_data)) {
                    delete_user_meta($user_id, 'adforest_ads_package_details');
                } else {
                    update_user_meta($user_id, 'adforest_ads_package_details', $package_data);
                }
                
                // Log the revocation
                $this->log_package_revocation($user_id, $user->user_email, $package_name, $current_admin);
                
                $success_count++;
            } else {
                $skipped_count++;
            }
        }

        return [
            'success_count' => $success_count,
            'skipped_count' => $skipped_count
        ];
    }

    private function log_package_revocation($user_id, $user_email, $package_name, $admin_user)
    {
        $logs = get_option('adforest_package_revocations', array());
        
        $logs[] = array(
            'timestamp' => current_time('mysql'),
            'admin_name' => $admin_user->display_name,
            'user_id' => $user_id,
            'user_email' => $user_email,
            'package_name' => $package_name,
            'reason' => __('Bulk unassignment', 'adforest')
        );
        
        // Keep only the last 1000 logs to prevent database bloat
        if (count($logs) > 1000) {
            $logs = array_slice($logs, -1000);
        }
        
        update_option('adforest_package_revocations', $logs);
    }
    
    public function render_package_revocation_logs_page()
    {
        $logs = get_option('adforest_package_revocations', array());
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Package Revocation Logs', 'adforest'); ?></h1>
            <p><?php esc_html_e('View all package revocations performed by administrators.', 'adforest'); ?></p>
            
            <?php if (empty($logs)) : ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No package revocations found.', 'adforest'); ?></p>
                </div>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date/Time', 'adforest'); ?></th>
                            <th><?php esc_html_e('Admin', 'adforest'); ?></th>
                            <th><?php esc_html_e('User', 'adforest'); ?></th>
                            <th><?php esc_html_e('User Email', 'adforest'); ?></th>
                            <th><?php esc_html_e('Package', 'adforest'); ?></th>
                            <th><?php esc_html_e('Reason', 'adforest'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($logs) as $log) : ?>
                            <tr>
                                <td><?php echo esc_html($log['timestamp']); ?></td>
                                <td><?php echo esc_html($log['admin_name']); ?></td>
                                <td>
                                    <?php 
                                    $user = get_userdata($log['user_id']);
                                    echo $user ? esc_html($user->display_name) : esc_html__('User not found', 'adforest');
                                    ?>
                                </td>
                                <td><?php echo esc_html($log['user_email']); ?></td>
                                <td><?php echo esc_html($log['package_name']); ?></td>
                                <td><?php echo esc_html($log['reason']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div style="margin-top: 20px;">
                    <button type="button" class="button button-secondary" id="clear-revocation-logs">
                        <?php esc_html_e('Clear All Logs', 'adforest'); ?>
                    </button>
                </div>
                
                <script type="text/javascript">
                    jQuery(document).ready(function($) {
                        $('#clear-revocation-logs').on('click', function() {
                            if (confirm('<?php echo esc_js(__('Are you sure you want to clear all revocation logs? This action cannot be undone.', 'adforest')); ?>')) {
                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'clear_package_revocation_logs',
                                        nonce: '<?php echo wp_create_nonce('clear_revocation_logs_nonce'); ?>'
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            location.reload();
                                        } else {
                                            alert('<?php echo esc_js(__('Error clearing logs', 'adforest')); ?>');
                                        }
                                    },
                                    error: function() {
                                        alert('<?php echo esc_js(__('Error clearing logs', 'adforest')); ?>');
                                    }
                                });
                            }
                        });
                    });
                </script>
            <?php endif; ?>
        </div>
        <?php
    }

    public function ajax_clear_package_revocation_logs()
    {
        check_ajax_referer('clear_revocation_logs_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions to perform this action.', 'adforest'));
        }

        if (delete_option('adforest_package_revocations')) {
            wp_send_json_success(__('All package revocation logs cleared.', 'adforest'));
        } else {
            wp_send_json_error(__('Error clearing package revocation logs.', 'adforest'));
        }
    }
}
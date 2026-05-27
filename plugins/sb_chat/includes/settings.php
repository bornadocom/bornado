<?php
if (!defined('ABSPATH'))
    exit;

class SB_Chat_Setting_Page {

    private $plugin_name;
    private $plugin_slug;
    private $textdomain;
    private $settings; // Declare the property
    private $options; // Declare the property


    public function __construct($plugin_name, $plugin_slug, $file="") {
      //  $this->file = $file;
        $this->plugin_slug = $plugin_slug;
        $this->plugin_name = $plugin_name;
        $this->textdomain = 'sb_chat';
        add_action('admin_init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_menu_item'));
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
    }

    /**
     * Initialise settings
     * @return void
     */
    public function init() {
        $this->settings = $this->settings_fields();
        $this->options = $this->get_options();
        $this->register_settings();
        $this->clear_plugin_data();

        // Self-heal the unread-notification cron whenever the configured
        // interval has changed since the last schedule was registered.
        // WP-Cron stores the schedule slug at the time of scheduling; just
        // updating `cron_schedules` filter values doesn't retro-apply.
        $configured_minutes = (int) SB_Chat::get_plugin_options( 'run_cron_after_minutes' );
        if ( $configured_minutes <= 0 || $configured_minutes > 1440 ) {
            $configured_minutes = 15;
        }
        $last_scheduled_minutes = (int) get_option( 'sbchat_unread_cron_minutes', 0 );
        $next_run               = wp_next_scheduled( 'unread_conversations_notify_cron' );

        if ( ! $next_run || $configured_minutes !== $last_scheduled_minutes ) {
            if ( $next_run ) {
                wp_clear_scheduled_hook( 'unread_conversations_notify_cron' );
            }
            wp_schedule_event( time(), 'sbchat_cron_minutes', 'unread_conversations_notify_cron' );
            update_option( 'sbchat_unread_cron_minutes', $configured_minutes, false );
        }
    }

    public function clear_plugin_data() {

        if ( isset( $_GET['action'] ) && $_GET['action'] == 'clear_plugin_data') {
            global $wpdb;
            $table_conversations = SBCHAT_TABLE_CONVERSATIONS;
            $table_messages = SBCHAT_TABLE_MESSAGES;

            $sbchat_conversation_table = "SELECT count(*) FROM information_schema.TABLES WHERE (TABLE_SCHEMA = 'glixen') AND (TABLE_NAME = $table_conversations )";
            $sbchat_messages_table = "SELECT count(*) FROM information_schema.TABLES WHERE (TABLE_SCHEMA = 'glixen') AND (TABLE_NAME = $table_messages )";

            $conversation_table_exists = (bool) $wpdb->get_var( $sbchat_conversation_table );
            $messages_table_exists = (bool) $wpdb->get_var( $sbchat_messages_table );

          

                $sbchat_messages = "SELECT `attachment_ids` FROM $table_messages WHERE `attachment_ids` != ''";
                $message_attachments = (array) $wpdb->get_results( $sbchat_messages );
                
                if ( is_array( $message_attachments ) && count( $message_attachments ) > 0 ) {
                    foreach ( $message_attachments as $message_attachment ) {
                        
                        $attachment_ids = explode( ',', $message_attachment->attachment_ids );
                        if ( is_array( $attachment_ids ) && count( $attachment_ids ) > 0 ) {
                            foreach ( $attachment_ids as $attachment_id ) {
                                
                                $attachment_path = SB_Chat::get_server_base_path() . 'uploads\\' . get_the_title( $attachment_id );
                                if ( file_exists( $attachment_path ) )
                                    unlink( $attachment_path );
                                
                                $wpdb->delete( $wpdb->prefix . 'posts', array( 'ID' => $attachment_id ), array( '%d' ) );
                            }
                        }
                    }
                }

                $remove_conversations = "DELETE FROM $table_conversations";
                $wpdb->get_row( $remove_conversations );

                $remove_messages = "DELETE FROM $table_messages";
                $wpdb->get_row( $remove_messages );
            
        }
    }

    /**
     * Add settings link to plugin list table
     * @param  array $links Existing links
     * @return array 		Modified links
     */
    public function add_settings_link($links) {
        $settings_link = '<a href="options-general.php?page=' . $this->plugin_slug . '">' . __('Settings', $this->textdomain) . '</a>';
        array_push($links, $settings_link);
        return $links;
    }

    /**
     * Build settings fields
     * @return array Fields to be displayed on settings page
     */
    private function settings_fields() {
        global $sb_plugin_options;

        $sbChat_bad_wordfilters = isset($whizzChat_options['sbChat-bad_words_filter_switch']) && $sb_plugin_options['sbChat-bad_words_filter_switch'] != '' ? $sb_plugin_options['sbChat-bad_words_filter_switch'] : '1';

        $admin_sec_class = 'sbChat_bad_wordsFilter_options';
        if ($sbChat_bad_wordfilters == 0) { // for admin only
            $admin_sec_class = 'sbChat_bad_wordsFilter_options hide';
        }


        $defaults = array(
            'numberposts'      => -1,
            'fields'         => 'ids',
            'post_status' => 'publish',
            'post_type'        => 'page',

        );

        $pages    =   get_posts($defaults);
        $admin_pages   =  array();
        if(isset($pages)  && !empty($pages)){

            foreach ($pages as $page) {
                if($page != ""){
                    $admin_pages[$page] = get_the_title($page);
                }            # code...
            }
        }

        $allowed_mime_types = array(
            'image/jpg' => 'JPG',
            'image/jpeg' => 'JPEG',
            'image/png' => 'PNG',
            'image/bmp' => 'BMP',
            'image/gif' => 'GIF',
            'image/webp' => 'WEBP',
            'application/pdf' => 'PDF',
            'text/plain' => 'TXT',
            'application/msword' => 'DOC',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'DOCX',
            'application/vnd.ms-excel' => 'XLS',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'XLSX',
            'application/vnd.ms-powerpoint' => 'PPT',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'PPTX',
        );
    
        $settings['basic_settings'] = array(
            'title' => __('Basic Settings', $this->textdomain),
            'description' => __('Here you can customize your chat settings.', $this->textdomain),
            'fields' => array(
                array(
                    'id' => 'sbChat-active',
                    'label' => __('SbChat', $this->textdomain),
                    'description' => __('Use these options to completely stop/start the chat.', $this->textdomain),
                    'type' => 'radio',
                    'options' => array(
                        '0' => __('Disable', $this->textdomain),
                        '1' => __('Enable', $this->textdomain),
                    ),
                    'default' => '0'
                ),
                array(
                    'id' => 'sb_notifications',
                    'label' => __('Notifications', $this->textdomain),
                    'description' => __('Turn On messages notifications.', $this->textdomain),
                    'type' => 'radio',
                    'options' => array(
                        '0' => __('Disable', $this->textdomain),
                        '1' => __('Enable', $this->textdomain),
                    ),
                    'default' => '0'
                ),
                array(
                    'id' => 'sb_notifications_time',
                    'label' => __('Notification time to refresh', $this->textdomain),
                    'description' => __('Time should be in Seconds. 1 Minute = 60 Seconds. Minimum 5sec', $this->textdomain),
                    'type' => 'number',
                    'default' => '5',
                    'placeholder' => __('5', $this->textdomain)
                ),
                array(
                    'id' => 'sbChat-bad_words_filter_switch',
                    'label' => __('Bad Words Filter', $this->textdomain),
                    'description' => __('Use these options to enable or disable bad words filters.', $this->textdomain),
                    'type' => 'radio',
                    'options' => array(
                        '0' => __('Disable', $this->textdomain),
                        '1' => __('Enable', $this->textdomain),
                    ),
                    // 'default' => '1',
                    'class' => 'sbChat_bad_wordsFilter',

                ),
                array(
                    'id' => 'sb_chat_bad_words_filter',
                    'label' => __('Bad Words', $this->textdomain),
                    'type' => 'textarea',
                    'subtitle' => esc_html__('comma separated', 'exertio_theme'),
                    'placeholder' => esc_html__('word1,word2', 'exertio_theme'),
                    'description' => esc_html__('These words will be removed from all Titles and Descriptions. Please be carefull while adding words. if you enter space here then it will remove space between works with provided word as well.', 'exertio_theme'),
                    'class' => $admin_sec_class,
                    'default' => '',


                    ),
                    array(
                    'id' => 'sb_chat_bad_words_replace',
                    'label' => __('Bad Words Replace Word', $this->textdomain),
                    'type' => 'text',
                    'title' => esc_html__('Bad Words Replace Word', 'exertio_theme'),
                    'description' => esc_html__('This words will be replace with above bad words list from AD Title and Description', 'exertio_theme'),
                    'class' => $admin_sec_class,
                    'default' => '',

                    ),
                array(
                    'id' => 'sb-dashboard-page',
                    'label' => __('Dashboard Page', $this->textdomain),
                    'description' => __('Select the dashboard page for chat messenger.', $this->textdomain),
                    'type' => 'select',
                    'options' => $admin_pages,
                    'default' => '',
                ),
                array(
                    'id' => 'sbchat_allowed_mime_types',
                    'label' => __( 'Allowed MIME Types', $this->textdomain ),
                    'description' => __( 'Restrict file uploads to only specific file extensions.', $this->textdomain ),
                    'type' => 'checkbox_multi',
                    'options' => $allowed_mime_types,
                    'default' => '',
                ),
                array(
                    'id' => 'sbchat_max_files_upload',
                    'label' => __( 'Max. Files Upload', $this->textdomain ),
                    'type' => 'number',
                    'default' => 7,
                ),
                array(
                    'id' => 'sb_max_file_size',
                    'label' => __( 'Max. File Upload Size (in KBs)', $this->textdomain ),
                    'type' => 'number',
                    'default' => 1,
                ),
            ),
        );

        $settings['notifications'] = array(
            'title' => __('Notifications', $this->textdomain),
            'description' => __('Turn on notifications for email.', $this->textdomain),
            'fields' => array(
                array(
                    'id' => 'run_cron_after_minutes',
                    'label' => __('Send Unread Notifications', $this->textdomain),
                    'description' => __( "Notifications for unread messages will be sent to users after every 'x' minutes. Allowed range: 1–1440. Very low values increase WP-Cron load.", $this->textdomain),
                    'type' => 'number',
                    'default' => 15,
                    'placeholder' => __( 'e.g. 15 minutes', $this->textdomain)
                ),
                array(
                    'id' => 'unread_messages_templates',
                    'label' => __('Unread Messages Email Template', $this->textdomain),
                    'type' => 'tinymce',
                    'default' => '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
                    <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
                    <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                    <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
                    <tbody>
                    <tr>
                    <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                    <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                    <td class="alert" align="center" valign="top" bgcolor="#fff">A Designing and development company</td>
                    </tr>
                    <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello  </span>%receiver_name%</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">%notification_message%</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Link :  <a href="%dashboard_link%"> Visit Inbox</a></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                    <div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
                    <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                    <td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
                    </tr>
                    </tbody>
                    </table>
                    </div>
                     </div>
                    </td>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
                    </tr>
                    </tbody>
                    </table>
                    <p>&nbsp;</p>',
                ),
            ),
        );

        $settings = apply_filters('plugin_settings_fields', $settings);
        return $settings;
    }

    /**
     * Options getter
     * @return array Options, either saved or default ones.
     */
    public function get_options() {
        $options = get_option($this->plugin_slug);

        if (!$options && is_array($this->settings)) {
            $options = Array();
            foreach ($this->settings as $section => $data) {
                foreach ($data['fields'] as $field) {
                    $options[$field['id']] = isset($field['default']) ? $field['default'] : '';
                }
            }
            add_option($this->plugin_slug, $options);
        }
        return $options;
    }

    /**
     * Register plugin settings
     * @return void
     */
    public function register_settings() {
        if (is_array($this->settings)) {
            
            register_setting($this->plugin_slug, $this->plugin_slug, array($this, 'validate_fields'));
            foreach ($this->settings as $section => $data) {
                add_settings_section($section, $data['title'], array($this, 'settings_section'), $this->plugin_slug);
                foreach ($data['fields'] as $field) {
                    $fields_args = array('field' => $field);
                    if (isset($field['class']) && $field['class'] != '') {
                        $fields_args = array('field' => $field, 'class' => $field['class']);
                    }
                    add_settings_field($field['id'], $field['label'], array($this, 'display_field'), $this->plugin_slug, $section, $fields_args);
                }
            }
        }
    }
    public function settings_section($section) {
        $html = '<p> ' . $this->settings[$section['id']]['description'] . '</p>' . "\n";
        echo sbChat_return($html);
    }
    public function display_field($args) {
        $field = $args['field'];
        $html = '';
        $option_name = $this->plugin_slug . "[" . $field['id'] . "]";
        // $data = (isset($this->options[$field['id']])) ? $this->options[$field['id']] : $this->options[$field['default']];
        /*updated code */
        if (isset($this->options[$field['id']])) {
            $data = $this->options[$field['id']];
        } elseif (isset($field['default']) && isset($this->options[$field['default']])) {
            $data = $this->options[$field['default']];
        } else {
            $data = '';
        }
         /*updated code Ends */
         $placehoder =   isset($field['placeholder']) ?  $field['placeholder'] : "";
            $desc =   isset($field['description']) ?  $field['description'] : "";
        switch ($field['type']) {
            case 'text':
            case 'password':
            case 'number':
                $html .= '<input id="' . esc_attr($field['id']) . '" type="' . $field['type'] . '" name="' . esc_attr($option_name) . '" placeholder="' . esc_attr($placehoder) . '" value="' . $data . '"/>' . "\n";
                break;
            case 'color':
                $html .= '<input class="sbchat-color-field" id="' . esc_attr($field['id']) . '" type="' . $field['type'] . '" name="' . esc_attr($option_name) . '" placeholder="' . esc_attr($field['placeholder']) . '" value="' . $data . '"/>' . "\n";
                break;
            case 'button':
                $html .= '<button class="' . esc_attr($field['ext_class']) . '" id="' . esc_attr($field['id']) . '" type="' . $field['type'] . '" name="' . esc_attr($option_name) . '" >' . esc_attr($field['button_label']) . '</button>' . "\n";
                break;
            case 'text_secret':
                $html .= '<input id="' . esc_attr($field['id']) . '" type="text" name="' . esc_attr($option_name) . '" placeholder="' . esc_attr($field['placeholder']) . '" value=""/>' . "\n";
                break;
            case 'textarea':
                $html .= '<textarea id="' . esc_attr($field['id']) . '" rows="7" cols="70" name="' . esc_attr($option_name) . '" placeholder="' . esc_attr($field['placeholder']) . '">' . $data . '</textarea><br/>' . "\n";
                break;
            case 'checkbox':
                $checked = '';
                if ($data && 'on' == $data) {
                    $checked = 'checked="checked"';
                }
                $html .= '<input id="' . esc_attr($field['id']) . '" type="' . $field['type'] . '" name="' . esc_attr($option_name) . '" ' . $checked . '/>' . "\n";
                break;
            case 'checkbox_multi':
                foreach ($field['options'] as $k => $v) {
                    $checked = false;
                    if (is_array($data) && in_array($k, $data)) {
                        $checked = true;
                    }
                    $html .= '<input type="checkbox" ' . checked($checked, true, false) . ' name="' . esc_attr($option_name) . '[]" value="' . esc_attr($k) . '" id="' . esc_attr($field['id'] . '_' . $k) . '" />' . esc_html( $v ) . ' ';
                }
                break;
            case 'radio':
                foreach ($field['options'] as $k => $v) {
                    $checked = false;
                    if ($k == $data) {
                        $checked = true;
                    }
                    $html .= '<label for="' . esc_attr($field['id'] . '_' . $k) . '"><input type="radio" ' . checked($checked, true, false) . ' name="' . esc_attr($option_name) . '" value="' . esc_attr($k) . '" id="' . esc_attr($field['id'] . '_' . $k) . '" /> ' . $v . '</label> ';
                }
                break;
            case 'select':
                $html .= '<select name="' . esc_attr($option_name) . '" id="' . esc_attr($field['id']) . '">';
                foreach ($field['options'] as $k => $v) {
                    $selected = false;
                    if ($k == $data) {
                        $selected = true;
                    }
                    $html .= '<option ' . selected($selected, true, false) . ' value="' . esc_attr($k) . '">' . $v . '</option>';
                }
                $html .= '</select> ';
                break;
            case 'select_multi':
                $html .= '<select name="' . esc_attr($option_name) . '[]" id="' . esc_attr($field['id']) . '" multiple="multiple">';
                foreach ($field['options'] as $k => $v) {
                    $selected = false;
                    if (in_array($k, $data)) {
                        $selected = true;
                    }
                    $html .= '<option ' . selected($selected, true, false) . ' value="' . esc_attr($k) . '" />' . $v . '</label> ';
                }
                $html .= '</select> ';
                break;
            
            case 'tinymce':

                $default_content = $data;
                if($default_content == ""){

                    $default_content = '<table class="body" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f6f6f6; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
                    <td class="container" style="font-family: sans-serif; font-size: 14px; vertical-align: top; display: block; max-width: 580px; padding: 10px; width: 580px; margin: 0 auto !important;">
                    <div class="content" style="box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;">
                    <table class="main" style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background: #fff; border-radius: 3px; width: 100%;">
                    <tbody>
                    <tr>
                    <td class="wrapper" style="font-family: sans-serif; font-size: 14px; vertical-align: top; box-sizing: border-box; padding: 20px;">
                    <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                    <td class="alert" align="center" valign="top" bgcolor="#fff">A Designing and development company</td>
                    </tr>
                    <tr>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;">
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><span style="font-family: sans-serif; font-weight: normal;">Hello  </span>%receiver_name%</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">%notification_message%</p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">Link :  <a href="%dashboard_link%"> Visit Inbox</a></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;"><strong>Thanks!</strong></p>
                    <p style="font-family: sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;">ScriptsBundle</p>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                    </td>
                    </tr>
                    </tbody>
                    </table>
                    <div class="footer" style="clear: both; padding-top: 10px; text-align: center; width: 100%;">
                    <table style="border-collapse: separate; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;" border="0" cellspacing="0" cellpadding="0">
                    <tbody>
                    <tr>
                    <td class="content-block powered-by" style="font-family: sans-serif; font-size: 12px; vertical-align: top; color: #999999; text-align: center;"><a style="color: #999999; text-decoration: underline; font-size: 12px; text-align: center;" href="https://themeforest.net/user/scriptsbundle">Scripts Bundle</a>.</td>
                    </tr>
                    </tbody>
                    </table>
                    </div>
                     </div>
                    </td>
                    <td style="font-family: sans-serif; font-size: 14px; vertical-align: top;"> </td>
                    </tr>
                    </tbody>
                    </table>
                    <p>&nbsp;</p>';
                }

                $editor_id = 'notification_email_template';

                $arg =array(
                    'textarea_name' => $option_name,
                    'media_buttons' => true,
                    'textarea_rows' => 10,
                    'quicktags' => true,
                    'wpautop' => false,
                    'teeny' => true
                ); $html .=   wp_editor( $default_content, $editor_id, $arg );

            break;                    
        }

        switch ($field['type']) {

              

            case 'checkbox_multi':
            case 'radio':
            case 'select_multi':
                $html .= '<p class="description" id="' . $field['id'] . '_description">' . $desc. '</span>';
                break;
            default:
                $html .= '<label for="' . esc_attr($field['id']) . '"><p class="description">' .$desc . '</p></label>' . "\n";
                break;
        }
        echo sbChat_return($html);
    }
    public function add_menu_item() {
        global $sb_plugin_options;
        $sb_plugin_options = get_option('sb_plugin_options');
        
        // Main menu
        add_menu_page(
            'SbChat Settings', 
            'SbChat', 
            'manage_options', 
            'sbChat-menu', 
            array($this, 'sb_chat_settings_page'), 
            'dashicons-email', 
            31
        );
        
        // Conversations submenu
        $conversations_hook = add_submenu_page(
            'sbChat-menu', 
            'SBChat — Conversations', 
            'Conversations', 
            'manage_options', 
            'sbchat_conversations', 
            array($this, 'conversations_page')
        );
        
        // Add debug info when the page loads (remove this in production)
        add_action("load-{$conversations_hook}", array($this, 'conversations_page_debug'));
    }
    
    public function conversations_page_debug() {
        // Add any debugging or data preparation here
        add_action('admin_notices', function() {
            if (!function_exists('sbchat_get_conversations_by_user_id')) {
                echo '<div class="notice notice-error"><p>Error: Core chat functions are not loaded!</p></div>';
            }
        });
    }

    public function conversations_page() {
        // Ensure required functions are loaded
        if (!function_exists('sbchat_get_conversations_by_user_id')) {
            echo '<div class="wrap"><h1>SbChat Conversations</h1>';
            echo '<div class="notice notice-error"><p>Error: Chat functions are not properly loaded. Please check your plugin installation.</p></div>';
            echo '</div>';
            return;
        }
        
        require_once(SBCHAT_ADMIN_DIR_PATH . '/templates/conversations.php');
    }

    public function sb_chat_settings_page() {
        ?>
        <div class="wrap sb-admin-container" id="<?php echo sbChat_return($this->plugin_slug);?>">
            <h2><?php _e('SbChat Settings', $this->textdomain);?></h2>
            <p><?php _e('Sb Chat is a WordPress plugin based on Ajax Chat system where buyers and seller can communicate with each other.', $this->textdomain);?></p>
            <h2 class="nav-tab-wrapper settings-tabs hide-if-no-js">
                <?php
                foreach ($this->settings as $section => $data) {
                    echo '<a href="#' . $section . '" class="nav-tab">' . $data['title'] . '</a>';
                }
                ?>
            </h2>
<!--            --><?php $this->do_script_for_tabbed_nav();?>
            <!-- Tab navigation ends -->
            <form action="options.php" method="POST">
                <?php settings_fields($this->plugin_slug);?>
                <div class="settings-container">
                    <?php do_settings_sections($this->plugin_slug);?>
                </div>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes" />
                    <a    href="javascript:void(0)" class="button button-primary clear_sb_data">Clear Plugin Data</a>
                    <input type ="hidden"  id ="clear_url" value ="<?php echo admin_url( 'admin.php' ) . '?page=sbChat-menu&action=clear_plugin_data' ?>">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Print jQuery script for tabbed navigation
     * @return void
     */
    private function do_script_for_tabbed_nav() {
        ?>
        <script>
            jQuery(document).ready(function ($) {
                /*
                 * functions to save active tab in cookie
                 */
                function whizzchat_setCookie(key, value, expiry) {
                    var expires = new Date();
                    expires.setTime(expires.getTime() + (expiry * 24 * 60 * 60 * 1000));
                    document.cookie = key + '=' + value + ';expires=' + expires.toUTCString();
                }
                function whizzchat_getCookie(key) {
                    var keyValue = document.cookie.match('(^|;) ?' + key + '=([^;]*)(;|$)');
                    return keyValue ? keyValue[2] : null;
                }
                function whizzchat_eraseCookie(key) {
                    var keyValue = whizzchat_getCookie(key);
                    whizzchat_setCookie(key, keyValue, '-1');
                }

                /*
                 * functions to save active tab in cookie
                 */

                var headings = jQuery('.settings-container > h2, .settings-container > h3');
                var paragraphs = jQuery('.settings-container > p');
                var tables = jQuery('.settings-container > table');
                var triggers = jQuery('.settings-tabs a');
                triggers.each(function (i) {
                    triggers.eq(i).on('click', function (e) {
                        e.preventDefault();
                        triggers.removeClass('nav-tab-active');
                        headings.hide();
                        paragraphs.hide();
                        tables.hide();
                        whizzchat_eraseCookie('whizz-tab-active');
                        triggers.eq(i).addClass('nav-tab-active');
                        headings.eq(i).show();
                        paragraphs.eq(i).show();
                        tables.eq(i).show();
                        whizzchat_setCookie('whizz-tab-active', i, '1'); //(key,value,expiry in days)

                    });
                });
                var whizz_active = whizzchat_getCookie('whizz-tab-active');
                whizz_active = typeof whizz_active !== 'undefined' && whizz_active != '' ? whizz_active : 0;
                triggers.eq(whizz_active).click();
                jQuery('.whizz-chat-chat-between input[type="radio"]').on('click', function (e) {
                    var chat_between = jQuery(this).val();
                    if (typeof chat_between !== 'undefined' && chat_between != '') {
                        if (chat_between == '1' || chat_between == '2') {
                            jQuery('.whizz-chat-admin-dropdown').show();
                        } else {
                            jQuery('.whizz-chat-admin-dropdown').hide();
                        }
                    }
                });
                $(document).ready(function() {
                    var bad_words_class = jQuery('.sbChat_bad_wordsFilter input[type="radio"]:checked').val();
                        if (bad_words_class == '0') {
                            jQuery('.sbChat_bad_wordsFilter_options').hide();
                        }

                    $('.sbChat_bad_wordsFilter input[type="radio"]').on('click', function (e) {

                        var bad_words_class = jQuery(this).val();

                        if (typeof bad_words_class !== 'undefined' && bad_words_class != '') {
                            if (bad_words_class == '1') {
                                jQuery('.sbChat_bad_wordsFilter_options').show();
                            } else {
                                jQuery('.sbChat_bad_wordsFilter_options').hide();
                            }
                        }
                    });
                });

                 jQuery('.shortcode-allow input[type="radio"]').on('click', function (e) {
                    var shortcode_allow = jQuery(this).val();
                    if (typeof shortcode_allow !== 'undefined' && shortcode_allow != '') {
                        if (shortcode_allow == '1') {
                            jQuery('.shortcode_type').show();
                        } else {
                            jQuery('.shortcode_type').hide();
                        }
                    }
                });



                jQuery('.whizzchat-usertype input[type="radio"]').on('click', function (e) {
                    var user_type = jQuery(this).val();
                    if (typeof user_type !== 'undefined' && user_type != '') {
                        if (user_type == '2') {
                            jQuery('.whizzchat-login-type').show();
                            jQuery('.whizzchat-logintype-field').show();

                        } else {
                            jQuery('.whizzchat-login-type').hide();
                            jQuery('.whizzchat-logintype-field').hide();
                        }
                    }
                });
            });
        </script>
        <?php
    }

}
$settings = new SB_Chat_Setting_Page("SB_Chat", "sb_plugin_options");
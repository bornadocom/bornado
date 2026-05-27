<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class Adforest_Export_Widgets {

    public function __construct() {
        add_action( 'customize_register', [ $this, 'add_customizer_export_section' ] );
        add_action( 'customize_controls_print_footer_scripts', [ $this, 'inject_export_button_script' ] );
        add_action( 'admin_init', [ $this, 'handle_export_download' ] );
    }

    /**
     * Add "Export Widgets" section to Customizer
     */
    public function add_customizer_export_section( $wp_customize ) {
        $wp_customize->add_section( 'adforest_tools_section', array(
            'title'       => __( 'Theme Tools', 'adforest' ),
            'priority'    => 999,
            'description' => __( 'Export theme-related settings and data.', 'adforest' ),
        ) );

        $wp_customize->add_setting( 'adforest_export_widgets_dummy', array(
            'sanitize_callback' => 'sanitize_text_field',
        ) );

        $wp_customize->add_control( new WP_Customize_Control(
            $wp_customize,
            'adforest_export_widgets_button',
            array(
                'label'       => __( 'Export Widgets (.wie)', 'adforest' ),
                'description' => __( 'Click the button to download current widgets configuration as a .wie file.', 'adforest' ),
                'section'     => 'adforest_tools_section',
                'settings'    => 'adforest_export_widgets_dummy',
                'type'        => 'hidden',
            )
        ) );
    }

    /**
     * Inject export button script into Customizer
     */
    public function inject_export_button_script() {
        if ( current_user_can( 'manage_options' ) ) {
            $url = esc_url( admin_url( '?adforest_export_widgets=1' ) );
            ?>
            <script>
            jQuery(document).ready(function ($) {
                const label = $('li#customize-control-adforest_export_widgets_button');
                if (label.length) {
                    label.append('<a href="<?php echo esc_url($url); ?>" class="button button-primary" target="_blank" style="margin-top:10px;">Export Widgets (.wie)</a>');
                }
            });
            </script>
            <?php
        }
    }


    /**
     * Handle the actual export and force download
     */
    public function handle_export_download() {
        if ( isset( $_GET['adforest_export_widgets'] ) && current_user_can( 'manage_options' ) ) {
            $widgets = get_option( 'sidebars_widgets' );
            $export  = [];

            foreach ( $widgets as $sidebar => $widget_ids ) {
                if ( ! is_array( $widget_ids ) || $sidebar === 'wp_inactive_widgets' ) {
                    continue;
                }

                foreach ( $widget_ids as $widget_id ) {
                    if ( preg_match( '/(.+)-(\d+)$/', $widget_id, $matches ) ) {
                        $widget_type = $matches[1];
                        $widget_num  = $matches[2];
                        $widget_data = get_option( 'widget_' . $widget_type );

                        if ( isset( $widget_data[ $widget_num ] ) ) {
                            $export[] = [
                                'sidebar'     => $sidebar,
                                'widget_id'   => $widget_id,
                                'widget_type' => $widget_type,
                                'widget_num'  => $widget_num,
                                'settings'    => $widget_data[ $widget_num ],
                            ];
                        }
                    }
                }
            }

            header( 'Content-Type: application/json' );
            header( 'Content-Disposition: attachment; filename=widgets.wie' );
            echo json_encode( $export, JSON_PRETTY_PRINT );
            exit;
        }
    }
}
new Adforest_Export_Widgets();
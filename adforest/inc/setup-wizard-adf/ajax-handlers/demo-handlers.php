<?php
namespace Adforest\SetupWizard;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register AJAX handlers if not already
if ( ! has_action( 'wp_ajax_adforest_import_demo_step' ) ) {
    add_action( 'wp_ajax_adforest_import_demo_step', __NAMESPACE__ . '\\adforest_import_demo_step' );
}
if ( ! has_action( 'wp_ajax_adforest_reset_demo' ) ) {
    add_action( 'wp_ajax_adforest_reset_demo',      __NAMESPACE__ . '\\adforest_reset_demo' );
}

/**
 * Handles individual demo import steps via POST.
 */
function adforest_import_demo_step() {
    @set_time_limit(0);

    $nonce     = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
    $demo      = sanitize_file_name(wp_unslash($_POST['demo'] ?? ''));
    $part      = sanitize_key(wp_unslash($_POST['part'] ?? ''));
    $mode      = sanitize_key(wp_unslash($_POST['import_mode'] ?? 'new'));
    $mark_done = ! empty($_POST['mark_done']);

    if (!wp_verify_nonce($nonce,'adforest_wizard_nonce') || !current_user_can('manage_options')) {
        wp_send_json_error(['message'=>__('Permission denied','adforest')],403);
    }

    // Preflight: ensure required PHP XML extensions are available before XML-based parts
    $missing = array();
    if (!class_exists('DOMDocument')) { $missing[] = 'dom'; }
    if (!extension_loaded('libxml')) { $missing[] = 'libxml'; }
    if (!extension_loaded('simplexml')) { $missing[] = 'simplexml'; }
    if (!empty($missing) && in_array($part, array('content','pages','media'), true)) {
        $exts = implode(', ', $missing);
        wp_send_json_error(array(
            'message' => sprintf(
                /* translators: %s: list of php extensions */
                __('Server missing PHP extensions required for XML import: %s. Please enable them for this site\'s PHP version.', 'adforest'),
                $exts
            )
        ), 500);
    }

    $demo_base = trailingslashit(__DIR__ . '/../demo/');

    $incoming_demo = sanitize_title( $demo );

    $folders = scandir($demo_base);

    $matched_folder = '';
    foreach ( $folders as $folder ) {
        if ( $folder === '.' || $folder === '..' ) {
            continue;
        }

        $folder_slug = sanitize_title($folder);

        if ( $folder_slug === $incoming_demo ) {
            $matched_folder = $folder;
            break;
        }
    }

    if ( empty($matched_folder) || !is_dir($demo_base . $matched_folder) ) {
        wp_send_json_error(['message' => __('Invalid demo folder.', 'adforest')]);
    }
    
    $demo_dir = trailingslashit($demo_base . $matched_folder);

    $allowed = ('existing' === $mode) ? ['pages','media','new-widgets','new-options'] : ['content','widgets','options'];

    if (!in_array($part,$allowed,true)) {
        wp_send_json_error(['message'=>__('Invalid part for selected mode.','adforest')],400);
    }

    if (!defined('WP_LOAD_IMPORTERS')) define('WP_LOAD_IMPORTERS',true);
    if (!class_exists('WP_Import')) {
        $paths = [
//            WP_PLUGIN_DIR . '/wordpress-importer/wordpress-importer.php',
//            get_stylesheet_directory() . '/inc/setup-wizard-adf/wordpress-importer/wordpress-importer.php',
            get_template_directory() . '/inc/setup-wizard-adf/wordpress-importer/wordpress-importer.php',
        ];
        foreach ($paths as $p) if (file_exists($p)) { require_once $p; break; }
    }
    require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
    require_once ABSPATH . 'wp-admin/includes/import.php';

    static $importer;
    if (null === $importer) {
        if (class_exists('WP_Import')) {
            $importer = new \WP_Import();
            $importer->fetch_attachments = true;
        } else {
            wp_send_json_error(['message'=>__('Importer not available.','adforest')],500);
        }
    }
    


    ob_start();
    try {
        switch ($part) {
            case 'content':
                $file = $demo_dir.'content.xml';
                if (!file_exists($file)) throw new \Exception(__('Content XML missing.','adforest'));
                $importer->import($file);
                break;

            case 'widgets':
                $file = $demo_dir.'widgets.wie';
                if (!file_exists($file)) throw new \Exception(__('Widgets file missing.','adforest'));
                $raw = file_get_contents($file);
                $data = is_serialized($raw)?maybe_unserialize($raw):json_decode($raw,true);
                if (!is_array($data)) throw new \Exception(__('Invalid widgets data.','adforest'));
                    $sidebars = get_option( 'sidebars_widgets', [] );
                    foreach ( $data as $w ) {
                        $opt_key   = 'widget_' . $w['widget_type'];
                        $instances = get_option( $opt_key, [] );
                        $instances[ $w['widget_num'] ] = $w['settings'];
                        update_option( $opt_key, $instances );

                        $sidebar = $w['sidebar'];
                        $wid     = $w['widget_type'] . '-' . $w['widget_num'];
                        if ( ! in_array( $wid, $sidebars[ $sidebar ] ?? [], true ) ) {
                            $sidebars[ $sidebar ][] = $wid;
                        }
                    }
                    update_option( 'sidebars_widgets', $sidebars );                
                break;                
            case 'new-widgets':
                $file = $demo_dir.'new-widgets.wie';
                if (!file_exists($file)) throw new \Exception(__('Widgets file missing.','adforest'));
                $raw = file_get_contents($file);
                $data = is_serialized($raw)?maybe_unserialize($raw):json_decode($raw,true);
                if (!is_array($data)) throw new \Exception(__('Invalid widgets data.','adforest'));
                        $sidebars = get_option( 'sidebars_widgets', [] );
                        foreach ( $data as $w ) {
                            $opt_key   = 'widget_' . $w['widget_type'];
                            $instances = get_option( $opt_key, [] );
                            $instances[ $w['widget_num'] ] = $w['settings'];
                            //update_option( $opt_key, $instances );

                            $sidebar = $w['sidebar'];
                            $wid     = $w['widget_type'] . '-' . $w['widget_num'];
                            if ( ! in_array( $wid, $sidebars[ $sidebar ] ?? [], true ) ) {
                                $sidebars[ $sidebar ][] = $wid;
                            }
                        }
                        //update_option( 'sidebars_widgets', $sidebars );                
                break;

            case 'options':
                $file = $demo_dir.'theme-options.json';
                if (!file_exists($file)) throw new \Exception(__('Theme options missing..','adforest'));
                $opts = json_decode(file_get_contents($file),true);
                if (!is_array($opts)) throw new \Exception(__('Invalid options data.','adforest'));
                 update_option( 'adforest_theme', $opts );
                break;                
            case 'new-options':
                break;
                $file = $demo_dir.'new-theme-options.json';
                if (!file_exists($file)) throw new \Exception(__('New Theme options missing.','adforest'));
                $opts = json_decode(file_get_contents($file),true);
                if (!is_array($opts)) throw new \Exception(__('Invalid options data.','adforest'));
                 //update_option( 'new007_adforest_theme', $opts );
                break;

            case 'pages':
                $file = $demo_dir.'pages.xml';

                if (!file_exists($file)) throw new \Exception(__('Pages XML missing.','adforest'));
                $importer->import($file);
                break;

            case 'media':
                break;
                $file = $demo_dir.'media.xml';
                if (!file_exists($file)) throw new \Exception(__('Media XML missing.','adforest'));
                add_filter('wp_import_posts_pre_parse', function($posts){
                    return array_filter($posts, fn($p)=>$p['post_type']=='attachment');
                });
                $importer->import($file);
                remove_all_filters('wp_import_posts_pre_parse');
                break;

            default:
                throw new \Exception(__('Unknown part.','adforest'));
        }

        if (in_array($part,['options','new-options', 'pages'],true)) {

            $demo_dir  = trailingslashit( $demo_dir );
            $json_file = $demo_dir . 'settings.json';

            if ( file_exists( $json_file ) ) {
                $data = json_decode( file_get_contents( $json_file ), true );

                if ( json_last_error() !== JSON_ERROR_NONE ) {
                    return;
                }

                $resolve = function( string $id_key, string $slug_key, callable $resolver ) use ( $data ) : ?int {
                    $id = isset( $data[ $id_key ] ) ? absint( $data[ $id_key ] ) : 0;

                    if ( ! $id && ! empty( $data[ $slug_key ] ) ) {
                        $slug = sanitize_title( $data[ $slug_key ] );
                        $obj  = $resolver( $slug );

                        if ( is_object( $obj ) ) {
                            if ( isset( $obj->ID ) ) {
                                $id = absint( $obj->ID );
                            } elseif ( isset( $obj->term_id ) ) {
                                $id = absint( $obj->term_id );
                            }
                        }
                    }
                    return $id ?: null;
                };

                $resolve_menu = function( string $slug ) {
                    $slug  = sanitize_title( $slug );
                    $menus = wp_get_nav_menus();
                    if(isset($menus) && count($menus) > 0)
                    foreach ( $menus as $menu ) {
                        if ( $menu->slug === $slug ) {
                            return $menu;
                        }
                    }
                    return false;
                };

                if($part !== 'pages'){
                    if ( $front_id = $resolve( 'front_page_id', 'front_page_slug', 'get_page_by_path' ) ) {
                        if ( get_post_status( $front_id ) === 'publish' ) {
                            update_option( 'show_on_front', 'page' );
                            update_option( 'page_on_front', $front_id );
                        }

                        update_option('adforest_elementor_safe_fix_done','0');
                    }

                    if ( $blog_id = $resolve( 'blog_page_id', 'blog_page_slug', 'get_page_by_path' ) ) {
                        if ( get_post_status( $blog_id ) === 'publish' ) {
                            update_option( 'page_for_posts', $blog_id );
                        }
                    }

                    if ( $menu_id = $resolve( 'main_menu_id', 'main_menu_slug', $resolve_menu )  ) {
                        $locations = get_theme_mod( 'nav_menu_locations', [] );
                        $locations['main_menu'] = $menu_id;
                        set_theme_mod( 'nav_menu_locations', $locations );
                    }
                }

            }
            update_option('adforest_elementor_safe_fix_done','0');
            update_option('adforest_imported_demo',$demo);
            update_option('adforest_imported_demo_label',ucfirst($demo));
        }
    } catch (\Exception $e) {
        ob_end_clean();
        wp_send_json_error(['message'=>$e->getMessage()],500);
    }
    ob_end_clean();

    wp_send_json_success(['part'=>$part]);
}

/** Reset demo flag */
function adforest_reset_demo() {
    check_ajax_referer('adforest_wizard_nonce','nonce');
    if(!current_user_can('manage_options')) wp_send_json_error();
    delete_option('adforest_imported_demo');
    delete_option('adforest_imported_demo_label');
    wp_send_json_success();
}

if(!function_exists('is_serialized')) {
    function is_serialized($data) {
        return is_string($data) && preg_match("/^(a|O|s|i|b|d):/",trim($data));
    }
}

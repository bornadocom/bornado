<?php
if (!defined('ABSPATH'))
    exit;

class Event_Elementor {

    private static $instance = null;

    public static function get_instance() {
        if (!self::$instance)
            self::$instance = new self;
        return self::$instance;
    }

    public function init() {
        $this->sb_pro_elementor_files_inclusion();
        add_action('elementor/widgets/widgets_registered', array($this, 'widgets_registered'));
        add_action('elementor/frontend/after_register_scripts', array($this, 'widget_scripts'));
        add_action('elementor/elements/categories_registered', array($this, 'sb_pro_elementor_register_widgets_sections'));
    }

    public function sb_pro_elementor_files_inclusion() {
        if (!class_exists('Adforest_Elementor')) {

            /* included all the filter from adforest elementor need not to add it twice if elementor already added * */
            require_once SB_DIR_PATH . '/inc/elementor/sb-pro-elementor-functions.php';
        }
    }

    public function widget_scripts() {
        
    }
    public function widgets_registered() {
        if (defined('ELEMENTOR_PATH') && class_exists('Elementor\Widget_Base')) {
            if (class_exists('Elementor\Plugin') && class_exists('Elementor\Widget_Base')) {
                if (is_callable('Elementor\Plugin', 'instance')) {
                    $elementor = Elementor\Plugin::instance();
                    if (isset($elementor->widgets_manager)) {
                        if (method_exists($elementor->widgets_manager, 'register_widget_type')) {
//                            require_once SB_DIR_PATH . 'inc/elementor/widgets/event_hero.php';
//                            require_once SB_DIR_PATH . 'inc/elementor/widgets/event_grids.php';
//                            require_once SB_DIR_PATH . 'inc/elementor/widgets/about_us.php';
//                            require_once SB_DIR_PATH . 'inc/elementor/widgets/event_country.php';
//                            require_once SB_DIR_PATH . 'inc/elementor/widgets/process_cycle_event.php';
//                            require_once SB_DIR_PATH . 'inc/elementor/widgets/event_hero_2.php';
//                            require_once SB_DIR_PATH . 'inc/elementor/widgets/cats_event.php';
//                             require_once SB_DIR_PATH . 'inc/elementor/widgets/directory_hero.php';


                            
                            
                            
                            
//                            $reg_file_name = "Elementor\Widget_event_hero";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
//
//                            $reg_file_name = "Elementor\Widget_event_grids";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
//
//                            $reg_file_name = "Elementor\Widget_about_us_event";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
//
//                            $reg_file_name = "Elementor\Widget_event_country";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
//
//                            $reg_file_name = "Elementor\Widget_process_cycle_event";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
//
//                            $reg_file_name = "Elementor\Widget_event_hero_2";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
//
//                             $reg_file_name = "Elementor\Widget_cats_event";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
//
//                            $reg_file_name = "Elementor\Widget_directory_hero";
//                            Elementor\Plugin::instance()->widgets_manager->register_widget_type(new $reg_file_name);
                        }
                    }
                }
            }
        }
    }

    public function sb_pro_elementor_register_widgets_sections($category_manager) {
        $category_manager->add_category(
                'sb_pro_elementor', [
            'title' => __('Adforest Widgets', 'sb_pro-elementor'),
            'icon' => 'fa fa-home',
                ]
        );
    }

}

Event_Elementor::get_instance()->init();

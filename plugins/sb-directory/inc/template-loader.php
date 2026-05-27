<?php
add_action('init', array('sb_pro_templates', 'init'));
class sb_pro_templates {
    public static function init() {
        add_filter('template_include', array(__CLASS__, 'template_loader'));
    }
    public static function template_loader($template) {
        $templates[] = 'sb_pro.php';
        if (is_singular('events')) {
            $default_file = "single-events.php";
            $object = get_queried_object();
            $name_decoded = urldecode($object->post_name);
            $templates[] = "single-events.php";
            $template = locate_template($templates);
            if (!$template) {
                $template = SB_DIR_PATH . '/template-parts/' . $default_file;
            }
            //load_template($template, false);
        }
 
        if(is_tax('l_event_cat')  || is_tax('event_loc') ){            
            wc_get_template2('event-search/search.php');
        }
               
else {
     return $template;
}        
    }
}
/* provide name and slug , it will find template from child theme / parent theme and plugin  */
function sb_load_template_parts($slug, $name = '') {
    $default_file = "{$slug}-{$name}.php";
    $template_arr = array(
        "{$slug}-{$name}.php",
        SB_DIR_PATH . 'template-parts/' . $default_file,
    );
    $template = locate_template($template_arr);
    if (!$template) {
        $check = SB_DIR_PATH . 'template-parts/' . $default_file;
        $template = file_exists($check) ? $check : '';
    }
    if ($template) {
        load_template($template, false);
    }
}
function wc_get_template2($template_name, $template_path = '', $default_path = '') {
    $template = wc_locate_template2($template_name, $template_path, $default_path);
    if (file_exists($template)) {
        load_template($template, false);
    }
}
function wc_locate_template2($template_name, $template_path = '', $default_path = '') {
    if (!$template_path) {
        $template_path = SB_DIR_PATH;
    }
    if (!$default_path) {
        $default_path = SB_DIR_PATH . 'template-parts/';
    }
    $template = locate_template(
            array(
                trailingslashit($template_path) . $template_name,
                $template_name,
            )
    );
    if (!$template) {
        $cs_template = str_replace('_', '-', $template_name);
        $template = $default_path . $cs_template;
        $template = file_exists($template) ? $template : '';
    }
    return $template;
}

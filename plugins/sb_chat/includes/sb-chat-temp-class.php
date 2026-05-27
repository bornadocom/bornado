<?php
/*
 * SB_Chat dashboard init class
 */

if (!class_exists('Sb_Chat_Dashboard')) {


    Class Sb_Chat_Dashboard {
        public function __construct() {
            add_filter('page_template', array($this, 'Sb_Chat_dashboard_page_template'));
            add_filter('theme_page_templates', array($this, 'Sb_Chat_page_template_selection'), 10, 4);
        }
        public function Sb_Chat_dashboard_page_template($page_template) {
            if (get_page_template_slug() == 'sb_chat_dashboard') {
                $page_template = dirname(__FILE__) . '/page-template/index.php';
            }
            return $page_template;
        }
        public function Sb_Chat_page_template_selection($post_templates) {
            $post_templates['sb_chat_dashboard'] = __('SB Chat Dashboard');
            return $post_templates;
        }
        function Sb_Chat_hide_admin_bar() {
            if (is_page_template('template-sbchat.php')) {
                return false;
            }
            else{
                return true;
            }
        }
    }
}
new Sb_Chat_Dashboard();
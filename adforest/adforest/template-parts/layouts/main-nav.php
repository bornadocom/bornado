<?php
global $adforest_theme;
$menu_ul_flag = true;
$sb_disable_menu = isset($adforest_theme['sb_disable_menu']) ? $adforest_theme['sb_disable_menu'] : false;
if (!$sb_disable_menu) {
    adforest_theme_menu('main_menu');
}

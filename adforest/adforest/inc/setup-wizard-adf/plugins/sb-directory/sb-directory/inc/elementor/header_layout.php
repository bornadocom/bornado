<?php
if (!function_exists('adforest_color_text')) {

    function adforest_color_text($str) {
        preg_match_all('~{color}([^{]*){/color}~i', $str, $matches);
        $i = 1;
        $found = array();
        foreach ($matches as $key => $val) {
            if ($i == 2) {
                $found = $val;
            }
            $i++;
        }
        foreach ($found as $k) {
            $search = "{color}" . $k . "{/color}";
            $replace = '<span class="heading-color">' . $k . '</span>';
            $str = str_replace($search, $replace, $str);
        }
        return $str;
    }
}
// For Section header
if (!function_exists('adforest_getHeader')) {
    function adforest_getHeader($sb_section_title, $sb_section_description, $style = 'classic', $view_all_btn = '' ,$sb_section_tagline  = '') {
        if ($style == 'classic') {
            $desc = '';
            if ($sb_section_description != '') {
                $desc = '<p class="heading-text">' . $sb_section_description . '</p>';
            }
            $main_title = adforest_color_text($sb_section_title);
            return '<div class="heading-panel"><div class="col-xs-12 col-md-12 col-sm-12 text-center"><h2>' . $main_title . '</h2> ' . $desc . '</div></div>';
        } else if ($style == 'regular') {
            $sb_section_title = adforest_color_text($sb_section_title);
            return '<div class="heading-panel"><div class="col-xs-12 col-md-12 col-sm-12"><h3 class="main-title text-left">' . ($sb_section_title) . '</h3></div></div>';
        } else if ($style == 'fancy') {
            $sb_section_title = adforest_color_text($sb_section_title);

            $btn_html = '';
            if (isset($view_all_btn) && !empty($view_all_btn)) {
                $btn_html = adforest_ThemeBtn($view_all_btn, "btn btn-theme", false);
            }
            return '<div class="col-xs-12 col-md-12 col-sm-12"><div class="prop-newset-heading"><h2> ' . ($sb_section_title) . '</h2>' . $btn_html . '</div></div>';
        }

        else if ($style == 'modern') {
            $sb_section_title = adforest_color_text($sb_section_title);
            $btn_html = '';
            if (isset($view_all_btn) && !empty($view_all_btn)) {
                $btn_html = adforest_ThemeBtn($view_all_btn, "btn btn-theme", false);
            }
            return '<div class="hading text-left"><h3> ' . ($sb_section_title) . '</h3><p> ' . $sb_section_description . '</p></div>';
        }

        else if ($style == 'new') {
            $sb_section_title = adforest_color_text($sb_section_title);

            return '<div class="sb-short-head center"><span>'.$sb_section_tagline.'</span><h2> ' . ($sb_section_title) . '</h2><p> ' . $sb_section_description . '</p></div>';
        }
    }
}

/* Header Layouts */
extract(shortcode_atts(array(
    'section_bg' => '', 'bg_img' => '', 'header_style' => '', 'section_title' => '', 'section_title_regular' => '','section_title_fancy' => '','view_link_fancy' => '', 'section_description' => '', 'ad_left' => '', 'ad_right' => '', 'cat_link_page' => '', 'sub_limit' => '', 'max_limit' => '', 'p_cols' => '', 'main_heading' => '', 'main_description' => '', 'main_image' => '', 'main_link' => '', 'ad_720_90' => '', 'woo_products' => '', 'woo_products' => '', 'all_products' => '','section_title_modern'=>'','section_tagline'=>''), $atts));
$header = '';
if (isset($header_style)) {
    $main_title = '';  
    if ($header_style == 'classic') {
        $main_title = $section_title;
    } else if ($header_style == 'fancy') {
        $main_title = $section_title_fancy;
    } else if ($header_style == 'modern') {
        $main_title = $section_title_modern;
    }
    else if ($header_style == 'new') {
        $main_title = $section_title;
    }
    else {
        $main_title = $section_title_regular;
    }  


    $tagline   =  isset($section_tagline)   ?   $section_tagline  :  "";
    $header = adforest_getHeader($main_title, $section_description, $header_style , $view_link_fancy ,$tagline  );
}

$style = '';
$bg_color = '';
if ($section_bg == 'img') {
    $bgImageURL = adforest_returnImgSrc($bg_img);
    if (isset($bg_bootom)) {
        $style = ( $bgImageURL != "" ) ? ' style="background:#fff url(' . $bgImageURL . ') repeat-x scroll center bottom; "' : "";
    } else {
        $style = ( $bgImageURL != "" ) ? ' style="background: rgba(0, 0, 0, 0) url(' . $bgImageURL . ') center center no-repeat; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;"' : "";
    }
} else {
    $bg_color = $section_bg;
}
<?php
/* Ads Layout */
extract(shortcode_atts(array('cats' => '', 's_title' => '', 'ad_type' => '', 'layout_type' => '', 'ad_order' => '', 'no_of_ads' => '',), $atts));

extract($atts);
$cats = array();
$rows = array();
if (isset($adforest_elementor) && $adforest_elementor) {
     $rows   =   isset($atts['cats'])  ?  $atts['cats']  : "";
} else {
    if (isset($atts['cats']) && $atts['cats'] != '') {
        $rows = vc_param_group_parse_atts($atts['cats']);
        $rows = apply_filters('adforest_validate_term_type', $rows);
    }
}

$is_all = false; 
    if (isset($rows) && $rows != '' && is_array($rows) && count($rows) > 0) {
        foreach ($rows as $row) {                  
                  if (isset($adforest_elementor) && $adforest_elementor) {
                         $cat_id  =  $row;
                    }
                      else{
                         $cat_id  =  $row['cat'];
                     }
                if ($cat_id  != 'all') {
                    $cats[] = $cat_id;
                }
        else {
            
            $is_all   =  true;
            
        }
        }
    }


$category = '';
if (isset($cats) && is_array($cats) && !empty($cats) && !$is_all) {
    $category = array('taxonomy' => 'l_event_cat', 'field' => 'term_id', 'terms' => $cats);
}

$ordering = 'DESC';
$order_by = 'date';
if ($ad_order == 'asc') {
    $ordering = 'ASC';
} else if ($ad_order == 'desc') {
    $ordering = 'DESC';
} else if ($ad_order == 'rand') {
    $order_by = 'rand';
}
$args = array(
    'post_type' => 'events',
    'post_status' => 'publish',
    'posts_per_page' => $no_of_ads,
    'orderby' => $order_by,
    'order' => $ordering,
   // 'meta_query' => array($is_feature, $is_active,),
);

if ($category != '') {
    $args['tax_query'] = array($category);
}
$args = apply_filters('adforest_wpml_show_all_posts', $args);
$html = '';
if ($layout_type == ''  || $layout_type > 3) {
    $layout_type = '1';
}

global $adforest_theme;
$out  =  "";
    $results = new WP_Query($args);
    $layouts = array('list_1', 'list_2', 'list_3');
    if ($results->have_posts()) {
        $type = $layout_type;
        $col = isset($col) && $col!="" ?   $col : 4; 
       
              while ($results->have_posts()) {
                $results->the_post();
                $pid = get_the_ID();
                $fun   =   "get_event_grid_type_$layout_type";
                $out .= $fun(get_the_ID() ,$col);
            }
       wp_reset_postdata();
        $heading = '';
        if ($s_title != "") {
            $heading = '<div class="heading-panel"><div class="col-xs-12 col-md-12 col-sm-12"><h3 class="main-title text-left"> ' . $s_title . ' </h3></div></div>';
        }
        $css = '';
        if (isset($no_title)) {
            $heading = ''; 
            $css = 'style="box-shadow: 0px 0px 0px 0px;"';
        }
        $cur_cls = '';
        if (isset($cls)) {
            $cur_cls = $cls;
        } else {
            $html = '<div class="' . esc_attr($cur_cls) . '" ' . $css . ' >' . $heading . '<div class="col-md-12 col-xs-12 col-sm-12"><div class="row posts-masonry">' . $out . ' </div></div></div>';       
    }
}
else {
    echo "no result found";
}
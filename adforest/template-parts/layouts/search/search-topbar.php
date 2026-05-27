<?php
global $adforest_theme;
$total_active_ads = get_total_active_ad_posts();;
$adforest_search_page = apply_filters( 'adforest_language_page_id', $adforest_theme['sb_search_page'] );
if ( function_exists( 'adforest_load_search_countries' ) ) {
	adforest_load_search_countries();
}

$loading_ads_mode = isset( $adforest_theme['loading_ads_mode'] ) ? $adforest_theme['loading_ads_mode'] : 'pagination';

$style_for_infinity_scroll = '';
if ( $loading_ads_mode == 'infinity_scroll' ) {
	$style_for_infinity_scroll = 'style = "height: 1000px; overflow: auto;"';
}
/* Only need on this page so included here don't want to increase page size for optimizaion by adding extra scripts in all the web */
$mapType = adforest_mapType();
if ( $mapType == 'leafletjs_map' ) {
} else if ( $mapType == 'google_map' ) {
//    wp_enqueue_script('google-map-callback');
}
wp_enqueue_script( 'adforest-search' );
wp_enqueue_style( 'datepicker', trailingslashit( get_template_directory_uri() ) . 'assets/css/datepicker.min.css' );
/* For Near By Ads */
$allow_near_by      = ( isset( $_GET['location'] ) && $_GET['location'] ) ? true : false;
$allow_rd           = ( isset( $_GET['rd'] ) && $_GET['rd'] ) ? true : false;
$lat_lng_meta_query = array();
if ( $allow_near_by && $allow_rd ) {
	$latlng = array();
	if ( $mapType == 'leafletjs_map' ) {
		$map_lat  = ( isset( $_GET['lat'] ) && $_GET['lat'] ) ? $_GET['lat'] : '';
		$map_long = ( isset( $_GET['long'] ) && $_GET['long'] ) ? $_GET['long'] : '';
		if ( $map_lat != "" && $map_long != "" ) {
			$latlng = array( "latitude" => $map_lat, "longitude" => $map_long );
		}
	} else if ( $mapType == 'google_map' ) {
		$latlng = adforest_getLatLong( $_GET['location'] );
	}

	if ( count( $latlng ) > 0 ) {
		$latitude   = ( isset( $latlng['latitude'] ) ) ? $latlng['latitude'] : '';
		$longitude  = ( isset( $latlng['longitude'] ) ) ? $latlng['longitude'] : '';
		$distance   = ( isset( $_GET['rd'] ) ) ? $_GET['rd'] : '20';
		$data_array = array( "latitude" => $latitude, "longitude" => $longitude, "distance" => $distance );
		if ( $latitude != "" && $longitude != "" ) {
			$type_lat   = "'DECIMAL'";
			$type_lon   = "'DECIMAL'";
			$lats_longs = adforest_determine_minMax_latLong( $data_array, false );
			if ( isset( $lats_longs ) && count( $lats_longs ) > 0 ) {
				//$lat_lng_meta_query['relation'] = 'AND';
				$lat_lng_meta_query[] = array(
					'key'     => '_adforest_ad_map_lat',
					'value'   => array(
						$lats_longs['lat']['min'],
						$lats_longs['lat']['max']
					),
					'compare' => 'BETWEEN',
					'type'    => 'DECIMAL',
				);
				$lat_lng_meta_query[] = array(
					'key'     => '_adforest_ad_map_long',
					'value'   => array(
						$lats_longs['long']['min'],
						$lats_longs['long']['max']
					),
					'compare' => 'BETWEEN',
					'type'    => 'DECIMAL',
				);
				add_filter( 'get_meta_sql', 'adforest_cast_decimal_precision' );
				if ( ! function_exists( 'adforest_cast_decimal_precision' ) ) {
					function adforest_cast_decimal_precision( $array ) {
						$array['where'] = str_replace( 'DECIMAL', 'DECIMAL(10,3)', $array['where'] );

						return $array;
					}
				}
			}
		}
	}
}

$meta = array( 'key' => 'post_id', 'value' => '0', 'compare' => '!=', );
// only active ads
$is_active = array( 'key' => '_adforest_ad_status_', 'value' => 'active', 'compare' => '=', );
$condition = '';
if ( isset( $_GET['condition'] ) && $_GET['condition'] != "" ) {
	$condition = array( 'key' => '_adforest_ad_condition', 'value' => $_GET['condition'], 'compare' => '=', );
}
$ad_type = '';
if ( isset( $_GET['ad_type'] ) && $_GET['ad_type'] != "" ) {
	$ad_type = array( 'key' => '_adforest_ad_type', 'value' => $_GET['ad_type'], 'compare' => '=', );
} else if ( isset( $_GET['adtype'] ) && $_GET['adtype'] != "" ) {
	$ad_type = array( 'key' => '_adforest_ad_type', 'value' => $_GET['adtype'], 'compare' => '=', );
}
$warranty = '';
if ( isset( $_GET['warranty'] ) && $_GET['warranty'] != "" ) {
	$warranty = array( 'key' => '_adforest_ad_warranty', 'value' => $_GET['warranty'], 'compare' => '=', );
}
$feature_or_simple = '';
if ( isset( $_GET['ad'] ) && $_GET['ad'] != "" ) {
	$feature_or_simple = array( 'key' => '_adforest_is_feature', 'value' => $_GET['ad'], 'compare' => '=', );
}
if ( isset( $_GET['sort'] ) && $_GET['sort'] == "featured" ) {
	$feature_or_simple = array( 'key' => '_adforest_is_feature', 'value' => '1', 'compare' => '=', );
}

$currency = '';
if ( isset( $_GET['c'] ) && $_GET['c'] != "" ) {
	$currency = array( 'key' => '_adforest_ad_currency', 'value' => $_GET['c'], 'compare' => '=', );
}
$price = '';
if ( isset( $_GET['min_price'] ) && $_GET['min_price'] != "" ) {
	$price = array(
		'key'     => '_adforest_ad_price',
		'value'   => array( $_GET['min_price'], $_GET['max_price'] ),
		'type'    => 'numeric',
		'compare' => 'BETWEEN',
	);
}
$location = '';
if ( isset( $_GET['location'] ) && $_GET['location'] != "" && ! $allow_rd ) {
	$location = array( 'key' => '_adforest_ad_location', 'value' => trim( $_GET['location'] ), 'compare' => 'LIKE', );
}
//Location
$countries_location = '';
if ( isset( $_GET['country_id'] ) && $_GET['country_id'] != "" ) {
	$countries_location = array(
		array(
			'taxonomy'         => 'ad_country',
			'field'            => 'term_id',
			'terms'            => $_GET['country_id'],
			'include_children' => ( function_exists( 'adforest_include_child_locations' ) && adforest_include_child_locations() ) ? 1 : 0,
		),
	);
}

$ad_currency = '';
if ( isset( $_GET['ad_currency'] ) && $_GET['ad_currency'] != "" ) {
	$ad_currency = array(
		array(
			'taxonomy' => 'ad_currency',
			'field'    => 'term_id',
			'terms'    => $_GET['ad_currency'],
		),
	);
}


$countries_location = apply_filters( 'adforest_site_location_ads', $countries_location, 'search' );
$order              = 'desc';
$orderBy            = 'date';
$ordering_price     = "";


if ( isset( $_GET['sort'] ) && $_GET['sort'] != "" ) {
	$orde_arr = explode( '-', $_GET['sort'] );
	$order    = isset( $orde_arr[1] ) ? $orde_arr[1] : 'desc';
	if ( isset( $orde_arr[0] ) && $orde_arr[0] == 'price' ) {
		$orderBy        = 'meta_value_num';
		$ordering_price = '_adforest_ad_price';
	} else {
		$orderBy = isset( $orde_arr[0] ) ? $orde_arr[0] : 'date';
	}
}
$category = '';
if ( isset( $_GET['cat_id'] ) && $_GET['cat_id'] != "" ) {
	$category = array(
		array(
			'taxonomy'         => 'ad_cats',
			'field'            => 'term_id',
			'terms'            => $_GET['cat_id'],
			'include_children' => ( function_exists( 'adforest_include_child_categories' ) && adforest_include_child_categories() ) ? 1 : 0,
		),
	);
}
$title = '';
if ( isset( $_GET['ad_title'] ) && $_GET['ad_title'] != "" ) {
	$title = $_GET['ad_title'];
}
$custom_search = array();
if ( isset( $_GET['min_custom'] ) ) {
	foreach ( $_GET['min_custom'] as $key => $val ) {
		$get_minVal = $val;
		$get_maxVal = ( isset( $_GET['max_custom']["$key"] ) && $_GET['max_custom']["$key"] != "" ) ? $_GET['max_custom']["$key"] : '';
		if ( $get_minVal != "" && $get_maxVal != "" ) {
			$metaKey = '_adforest_tpl_field_' . $key;
			if ( adforest_validateDateFormat( $get_minVal ) && adforest_validateDateFormat( $get_maxVal ) ) {
				$custom_search[] = array(
					'key'     => $metaKey,
					'value'   => array( $get_minVal, $get_maxVal ),
					'compare' => 'BETWEEN',
				);
			} else {
				$custom_search[] = array(
					'key'     => $metaKey,
					'value'   => array( $get_minVal, $get_maxVal ),
					'type'    => 'numeric',
					'compare' => 'BETWEEN',
				);
			}
		}
	}
}
if ( isset( $_GET['custom'] ) ) {
	$template_cat_id = ( isset( $_GET['cat_id'] ) && $_GET['cat_id'] != "" ) ? $_GET['cat_id'] : '';
	$cat_tempate     = adforest_dynamic_field_type_template( $template_cat_id );
	foreach ( $_GET['custom'] as $key => $val ) {
		if ( is_array( $val ) ) {
			$arr     = array();
			$metaKey = '_adforest_tpl_field_' . $key;
			foreach ( $val as $v ) {
				$custom_search[] = array( 'key' => $metaKey, 'value' => $v, 'compare' => 'LIKE', );
			}
		} else {
			if ( trim( $val ) == "0" ) {
				continue;
			}
			$field_type = adforest_dynamic_field_type( $cat_tempate, $key );
			$val        = stripslashes_deep( $val );
			$metaKey    = '_adforest_tpl_field_' . $key;
			if ( $field_type == 'checkbox' ) {
				$custom_search[] = array( 'key' => $metaKey, 'value' => ( '"' . $val . '"' ), 'compare' => 'LIKE', );
			} elseif ( $field_type == 'select' ) {
				// $custom_search[] = array('key' => $metaKey, 'value' => '^' . $val, 'compare' => 'REGEXP',);
				$custom_search[] = array( 'key' => $metaKey, 'value' => $val, 'compare' => 'REGEXP', );
			} else {
				$custom_search[] = array( 'key' => $metaKey, 'value' => $val, 'compare' => 'LIKE', );
			}
		}
	}
}
if ( get_query_var( 'paged' ) ) {
	$paged = get_query_var( 'paged' );
} else if ( get_query_var( 'page' ) ) {
	// This will occur if on front page.
	$paged = get_query_var( 'page' );
} else {
	$paged = 1;
}
$args  = array(
	's'              => $title,
	'post_type'      => 'ad_post',
	'post_status'    => 'publish',
	'posts_per_page' => get_option( 'posts_per_page' ),
	'tax_query'      => array( $category, $countries_location, $ad_currency ),
	'meta_key'       => $ordering_price,
	'meta_query'     => array(
		$is_active,
		$condition,
		$ad_type,
		$warranty,
		$feature_or_simple,
		$price,
		$currency,
		$location,
		$custom_search,
		$lat_lng_meta_query,
	),
	'order'          => $order,
	'orderby'        => $orderBy,
	'paged'          => $paged,
);
$args  = apply_filters( 'adforest_wpml_show_all_posts', $args );
$query = new WP_Query( $args );

$ad_count = 0;
while ( $query->have_posts() ) {
	$query->the_post();
	$ad_count ++;
}
?>
<section class="adt-ads-topbar-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="adt-ads-topbar-content">
                    <div class="heading-area">
                        <h3><?php echo esc_html__( 'Search Ads', 'adforest' ); ?></h3>
                        <?php
                        $text = sprintf(
                            __( '<span>%d</span> Ads Available', 'adforest' ),
                            intval( $total_active_ads )
                        );
                        ?>
                        <h4><?php echo wp_kses($text, ADFOREST_ALLOWED_FORM_HTML); ?></h4>
                    </div>
                    <div class="row">
						<?php dynamic_sidebar( 'adforest_search_sidebar' ); ?>
						<?php if ( $GLOBALS['widget_counter'] >= $adforest_theme['search_widget_limit'] ) {
							echo '</div>';
						} ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="adt-recommended-ads-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="adt-ads-sort-box">
                    <h3>
						<span id="adforest-ajax-count"><?php echo esc_html( $query->found_posts ) . ' ' . esc_html__( 'Ad(s) Found:', 'adforest' ); ?></span>
						<?php
						$param = $_SERVER['QUERY_STRING'];
						if ( $param != "" ) {
							?>
                            <span><a class="filterAdType-count"
                                     href="<?php echo get_the_permalink( $adforest_search_page ); ?>"><?php echo esc_html__( 'Reset Search', 'adforest' ); ?></a></span>
						<?php } ?>
                    </h3>
					<?php
					$selectedOldest = $selectedLatest = $selectedTitleAsc = $selectedTitleDesc = $selectedPriceHigh = $selectedPriceLow = $selectedFeatured = '';
					if ( isset( $_GET['sort'] ) ) {
						$selectedOldest    = ( $_GET['sort'] == 'id-asc' ) ? 'selected' : '';
						$selectedLatest    = ( $_GET['sort'] == 'id-desc' ) ? 'selected' : '';
						$selectedTitleAsc  = ( $_GET['sort'] == 'title-asc' ) ? 'selected' : '';
						$selectedFeatured  = ( $_GET['sort'] == 'featured' ) ? 'selected' : '';
						$selectedTitleDesc = ( $_GET['sort'] == 'title-desc' ) ? 'selected' : '';
						$selectedPriceHigh = ( $_GET['sort'] == 'price-desc' ) ? 'selected' : '';
						$selectedPriceLow  = ( $_GET['sort'] == 'price-asc' ) ? 'selected' : '';
					} elseif ( isset( $_GET['ad'] ) ) {
						$selectedFeatured = ( $_GET['ad'] == '1' ) ? 'selected' : '';
					}
					?>
                    <div class="d-flex justify-content-center gap-2">
                        <form id="sort-form" method="get">
                            <select name="sort" class="default-select order_by" id="select-sort">
                                <option value="id-desc" <?php echo esc_attr( $selectedLatest ); ?>>
									<?php echo esc_html__( 'Newest To Oldest', 'adforest' ); ?>
                                </option>
                                <option value="id-asc" <?php echo esc_attr( $selectedOldest ); ?>>
									<?php echo esc_html__( 'Oldest To Newest', 'adforest' ); ?>
                                </option>
                                <option value="featured" <?php echo esc_attr( $selectedFeatured ); ?>>
									<?php echo esc_html__( 'Featured', 'adforest' ); ?>
                                </option>
                                <option value="price-desc" <?php echo esc_attr( $selectedPriceHigh ); ?>>
									<?php echo esc_html__( 'Price: High to Low', 'adforest' ); ?>
                                </option>
                                <option value="price-asc" <?php echo esc_attr( $selectedPriceLow ); ?>>
									<?php echo esc_html__( 'Price: Low to High', 'adforest' ); ?>
                                </option>
                            </select>
							<?php echo adforest_search_params( 'sort' ); ?>
                        </form>
                        <div class="d-flex justify-content-around align-items-center">
	                        <?php
	                        $grid_view = adforest_custom_remove_url_query( 'view-type', 'grid' );
	                        $list_view = adforest_custom_remove_url_query( 'view-type', 'list' );
	                        // Init both — only one branch below sets a value,
	                        // and both are echoed unconditionally further down,
	                        // which triggered "Undefined variable" notices.
	                        $grid_active = '';
	                        $list_active = '';
	                        if ( ( isset( $_GET['view-type'] ) && $_GET['view-type'] == 'grid' ) || ! isset( $_GET['view-type'] ) ) {
		                        $grid_active = 'active';
	                        } elseif ( isset( $_GET['view-type'] ) && $_GET['view-type'] == 'list' ) {
		                        $list_active = 'active';
	                        }
	                        if ( isset( $adforest_theme['search_layout_types'] ) && $adforest_theme['search_layout_types'] == true ) {
		                        ?>
                                <a href="<?php echo esc_url( $grid_view ); ?>"
                                   class="icon-box grid <?php echo esc_attr($grid_active); ?>"><i
                                            class="fas fa-th-large"></i></a>
                                &nbsp;
                                <a href="<?php echo esc_url( $list_view ); ?>"
                                   class="icon-box list <?php echo esc_attr($list_active); ?>"><i
                                            class="fas fa-bars"></i></a>
	                        <?php } ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 col-xs-12 col-sm-12 col-lg-12">
		            <?php get_template_part( 'template-parts/layouts/search/search', 'tags' ); ?>
                </div>
                <div class="clearfix"></div>
				<?php
				if ( isset( $adforest_theme['sb_allow_cats_above_filters'] ) && $adforest_theme['sb_allow_cats_above_filters'] ) {
					if ( isset( $_GET['cat_id'] ) && $_GET['cat_id'] != "" ) {
						?><?php
						$cat_id     = $_GET['cat_id'];
						$ad_cats    = adforest_get_cats( 'ad_cats', $cat_id );
						$res        = '';
						$rows_count = 1;
						$max_rows   = $adforest_theme['sb_max_sub_cats'];
						$show       = true;
						if ( count( $ad_cats ) > 0 ) {
							parse_str( $_SERVER['QUERY_STRING'], $search_params );
							unset( $search_params['cat_id'] );
							$new_params = http_build_query( $search_params );
							$cat_params = '';
							$cls        = '';
							$res        .= '<ul class="city-select-city" >';
							foreach ( $ad_cats as $ad_cat ) {
								if ( $new_params != "" ) {
									$cat_params = '?' . $new_params . '&cat_id=' . $ad_cat->term_id;
									$cat_link   = get_the_permalink( $adforest_search_page ) . $cat_params;
								} else {
									$cat_params = '?cat_id=' . $ad_cat->term_id;
									$cat_link   = get_the_permalink( $adforest_search_page ) . $cat_params;
								}

								$li_col = '3';
								if ( isset( $adforest_theme['sb_li_cols'] ) && $adforest_theme['sb_li_cols'] != "" ) {
									$li_col = $adforest_theme['sb_li_cols'];
								}

								$count = ( $ad_cat->count );
								if ( $rows_count > $max_rows && $show ) {
									$show = false;
									$res  .= '<li class="col-md-12 col-sm-12 col-xs-12 hide_cats text-center margin-top-20"><a href="javascript:void(0);"  class="tax-show-more">' . esc_html__( 'Show more', 'adforest' ) . '</a></li>';
									$cls  = 'no-display show_it';
								}
								$res .= '<li class="col-md-' . esc_attr( $li_col ) . ' col-sm-6 col-xs-12 ' . esc_attr( $cls ) . '"><a href="' . $cat_link . '" >' . $ad_cat->name . ' <span>(' . $count . ')</span> </a></li>';
								$rows_count ++;
							}
							$res .= '</ul>';
							?>
                            <div class="col-md-12 col-sm-12 col-xs-12">
                                <div class="expand-collapse adforest-new-filter">
                                    <h3>
                                        <a role="button" data-bs-toggle="collapse" data-parent="#accordion" href="#collapseOnez" aria-expanded="true" aria-controls="collapseOnez">
                                            <i class="more-less fa fa-minus"></i>
			                                <?php
			                                $title = adforest_get_taxonomy_parents($cat_id, 'ad_cats', false);
			                                $find = '&raquo;';
			                                $replace = '';
			                                $result = preg_replace("/$find/", $replace, $title, 1);
			                                echo '<span>'.adforest_return_echo($result).'</span>';
			                                ?>
                                        </a>
                                    </h3>
                                    <form>
                                        <div id="collapseOnez" class="panel-collapse collapse in show"
                                             role="tabpanel"
                                             aria-labelledby="headingOnez">
                                            <div class="panel-body">
                                                <div class="search-modal">
                                                    <div class="search-block"><?php echo adforest_return_echo( $res ); ?></div>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <div class="clearfix"></div>
							<?php
						}
					}
				}
				?>
            </div>
	        <?php if ( isset( $adforest_theme['featured_first'] ) && $adforest_theme['featured_first'] == '1' ) {
		        echo adforest_featured_grids_on_search();
	        } ?>
	        <?php
	        if ( isset( $adforest_theme['search_ad_720_1'] ) && $adforest_theme['search_ad_720_1'] != "" && $query->have_posts() ) {
		        ?>

                <div class="col-md-12">
                    <div class="margin-bottom-30 margin-top-10 text-center">
				        <?php echo "" . $adforest_theme['search_ad_720_1']; ?>
                    </div>
                </div>
		        <?php
	        }
	        ?>
			<div id="adforest-ajax-results" class="adforest-ajax-results">
			<?php echo adforest_render_ads_in_search( $query, $style_for_infinity_scroll, $loading_ads_mode, $paged, $args, $ad_count ); ?>
			</div>
        </div>
    </div>
</section>
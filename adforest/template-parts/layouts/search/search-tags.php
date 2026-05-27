<?php
global $adforest_theme;
$param         = $_SERVER['QUERY_STRING'];
$search_params = array(
	'cat_id',
	'min_price',
	'ad_type',
	'adtype',
	'warranty',
	'ad_title',
	'location',
	'condition',
	'ad',
	'country_id',
	'ad_currency',
	'sort'
);
$search_label  = array(
	'cat_id'      => __( 'Category', 'adforest' ),
	'min_price'   => __( 'Price', 'adforest' ),
	'ad_type'     => __( 'Ad Type', 'adforest' ),
	'adtype'      => __( 'Ad Type', 'adforest' ),
	'warranty'    => __( 'Warranty', 'adforest' ),
	'ad_title'    => __( 'Keyword', 'adforest' ),
	'location'    => __( 'Google Location', 'adforest' ),
	'condition'   => __( 'Condition', 'adforest' ),
	'ad'          => __( 'Class', 'adforest' ),
	'country_id'  => __( 'Location', 'adforest' ),
	'ad_currency' => __( 'Currency', 'adforest' ),
	'sort'        => __( 'Sort', 'adforest' )
);

$sb_search_page = apply_filters( 'adforest_language_page_id', $adforest_theme['sb_search_page'] );
$sb_search_page = isset( $sb_search_page ) && $sb_search_page != '' ? get_the_permalink( $sb_search_page ) : 'javascript:void(0)';
$sb_search_page = apply_filters( 'adforest_category_widget_form_action', $sb_search_page );

if ( isset( $param ) && ! empty( $param ) ) {
	parse_str( $_SERVER['QUERY_STRING'], $vars );
	if ( array_key_first( $vars ) !== 'view-type' ) {

		// Check if at least one valid tag exists
		$has_tags = false;
		foreach ( $vars as $key => $val ) {
			if ( in_array( $key, $search_params ) && $val != '' ) {
				$has_tags = true;
				break;
			}
		}
		if ( isset($_GET['min_custom']) && count($_GET['min_custom']) > 0 ) $has_tags = true;
		if ( isset($_GET['custom']) && count($_GET['custom']) > 0 ) $has_tags = true;

		// Only display the tag-search div if a valid tag exists
		if ( $has_tags ) {
			?>
			<div class="col-md-12 col-xs-12 col-sm-12 col-lg-12 no-padding">
				<div class="tag-search">
					<?php
					// -----------------------------
					// Standard search params
					// -----------------------------
					foreach ( $vars as $key => $val ) {
						if ( ! in_array( $key, $search_params ) || $val == '' ) {
							continue;
						}
						?>
						<form method="get" action="<?php echo esc_url( $sb_search_page ); ?>">
							<span class="tag label label-info sb_tag">
								<span>
									<?php
									if ( $key == 'cat_id' ) {
										$term      = get_term_by( 'id', $val, 'ad_cats' );
										$term_name = ( $term && ! is_wp_error( $term ) ) ? $term->name : esc_html( $val );
										echo adforest_return_echo( $search_label[ $key ] ) . ': ' . $term_name;
									} elseif ( $key == 'min_price' ) {
										$min = esc_html( $val );
										$max = ( isset( $_GET['max_price'] ) && $_GET['max_price'] != '' ) ? esc_html( $_GET['max_price'] ) : '';
										echo adforest_return_echo( $search_label[ $key ] ) . ': ' . $min . ( $max !== '' ? ' - ' . $max : '' );
									} elseif ( $key == 'sort' ) {
										$sort_val = '';
										if ( $val == 'featured' ) $sort_val = __( "Featured", "adforest" );
										elseif ( $val == 'id-desc' ) $sort_val = __( "Newest", "adforest" );
										elseif ( $val == 'id-asc' ) $sort_val = __( "Oldest", "adforest" );
										elseif ( $val == 'price-asc' ) $sort_val = __( "Price Low to High", "adforest" );
										elseif ( $val == 'price-desc' ) $sort_val = __( "Price High to Low", "adforest" );
										echo adforest_return_echo( $search_label[ $key ] ) . ': ' . $sort_val;
									} elseif ( $key == 'country_id' ) {
										$term      = get_term_by( 'id', $val, 'ad_country' );
										$term_name = ( $term && ! is_wp_error( $term ) ) ? $term->name : esc_html( $val );
										echo adforest_return_echo( $search_label[ $key ] ) . ': ' . $term_name;
									} else {
										echo adforest_return_echo( $search_label[ $key ] ) . ': ' . esc_html( $val );
									}
									?>
								</span>
								<a href="javascript:void(0);" class="submit_on_select"><i class="remove fa fa-remove"></i></a>
							</span>
							<?php
							if ( $key == 'min_price' ) {
								echo adforest_search_params( 'min_price', 'max_price', 'c' );
							} else {
								echo adforest_search_params( $key );
							}
							?>
						</form>
						<?php
					}

					// -----------------------------
					// Custom min/max fields
					// -----------------------------
					if ( isset( $_GET['min_custom'] ) && count( $_GET['min_custom'] ) > 0 ) {
						foreach ( $_GET['min_custom'] as $k => $v ) {
							?>
							<form method="get" action="<?php echo esc_url( $sb_search_page ); ?>">
								<span class="tag label label-info sb_tag">
									<span><?php echo adforest_return_echo( $k ) . ': ' . esc_html( $v ); ?></span>
									<a href="javascript:void(0);" class="submit_on_select"><i class="remove fa fa-remove"></i></a>
								</span>
								<?php echo adforest_search_params( 'min_custom[' . $k . ']', 'max_custom[' . $k . ']' ); ?>
							</form>
							<?php
						}
					}

					// -----------------------------
					// Other custom fields
					// -----------------------------
					if ( isset( $_GET['custom'] ) && count( $_GET['custom'] ) > 0 ) {
						foreach ( $_GET['custom'] as $k => $v ) {
							?>
							<form method="get" action="<?php echo esc_url( $sb_search_page ); ?>">
								<span class="tag label label-info sb_tag">
									<span><?php echo adforest_return_echo( $k ) . ': ' . esc_html( $v ); ?></span>
									<a href="javascript:void(0);" class="submit_on_select"><i class="remove fa fa-remove"></i></a>
								</span>
								<?php echo adforest_search_params( 'custom[' . $k . ']' ); ?>
							</form>
							<?php
						}
					}
					?>
				</div>

				<div class="pagination-item">
					<?php
					if ( isset( $results ) ) {
						adforest_pagination_search( $results );
					}
					?>
				</div>
			</div>
			<?php
		}
	}
}
?>
<?php
/**
 * WordPress widget clone for AdForest search by location.
 *
 * Keeps AdForest's original AJAX flow and adds an "all cities" option.
 *
 * @package My_Custom_Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'mcew_search_location_v2_get_actions' ) ) {
	/**
	 * Resolve shared contextual actions for the widget.
	 *
	 * @return array<string,string>
	 */
	function mcew_search_location_v2_get_actions() {
		if ( function_exists( 'bornado_search_get_actions' ) ) {
			return bornado_search_get_actions(
				array(
					'widget_action' => 'cat_page',
				)
			);
		}

		return array(
			'default_action'    => home_url( '/' ),
			'all_cities_action' => home_url( '/' ),
		);
	}
}

if ( ! function_exists( 'mcew_search_location_v2_get_form_action' ) ) {
	/**
	 * Resolve the base form action used by AdForest location search.
	 *
	 * @return string
	 */
	function mcew_search_location_v2_get_form_action() {
		$search_actions = mcew_search_location_v2_get_actions();
		return $search_actions['default_action'];
	}
}

if ( ! function_exists( 'mcew_search_location_v2_get_all_cities_url' ) ) {
	/**
	 * Build the contextual "all cities" target URL.
	 *
	 * Removes only the current city slug from the route.
	 *
	 * @return string
	 */
	function mcew_search_location_v2_get_all_cities_url() {
		$search_actions = mcew_search_location_v2_get_actions();
		return $search_actions['all_cities_action'];
	}
}

if ( ! class_exists( 'MCEW_Adforest_Search_By_Location_V2' ) ) {
	/**
	 * Separate WordPress widget version of AdForest Country Location.
	 */
	class MCEW_Adforest_Search_By_Location_V2 extends WP_Widget {
		/**
		 * Register widget.
		 */
		public function __construct() {
			parent::__construct(
				'mcew_adforest_search_by_location_v2',
				__( 'Country Location V2', 'my-custom-widgets' ),
				array(
					'classname'   => 'mcew_adforest_search_by_location_v2',
					'description' => __( 'AdForest search by location clone with an all cities option.', 'my-custom-widgets' ),
				)
			);
		}

		/**
		 * Front-end widget output.
		 *
		 * @param array<string,mixed> $args     Widget args.
		 * @param array<string,mixed> $instance Saved values.
		 * @return void
		 */
		public function widget( $args, $instance ) {
			global $adforest_theme;

			if ( ! function_exists( 'adforest_get_ad_taxonomy_callback' ) || ! function_exists( 'adforest_search_layout' ) ) {
				return;
			}

			$title            = ! empty( $instance['title'] ) ? trim( (string) apply_filters( 'widget_title', $instance['title'] ) ) : '';
			$ad_countries     = adforest_get_ad_taxonomy_callback( 'ad_country' );
			$selected_country = isset( $_GET['country_id'] ) ? sanitize_text_field( wp_unslash( $_GET['country_id'] ) ) : '';
			$form_action      = mcew_search_location_v2_get_form_action();
			$all_cities_url   = mcew_search_location_v2_get_all_cities_url();
			$widget_layout    = adforest_search_layout();
			$expand           = '';
			$collapsed        = 'collapsed';

			if ( isset( $adforest_theme['search_popup_loc_disable'] ) && true === (bool) $adforest_theme['search_popup_loc_disable'] ) {
				$ad_countries = array_filter(
					(array) $ad_countries,
					function ( $country ) {
						$country_details = function_exists( 'get_taxonomy_details' ) ? get_taxonomy_details( $country ) : array();
						return isset( $country_details['ad_count'] ) && (int) $country_details['ad_count'] > 0;
					}
				);
			}

			if ( '' !== $selected_country ) {
				$expand    = 'show';
				$collapsed = '';
			}

			if ( isset( $instance['open_widget'] ) && '1' === (string) $instance['open_widget'] ) {
				$expand    = 'show';
				$collapsed = '';
			}

			if ( 'sidebar' === $widget_layout ) {
				if ( '' === $title ) {
					?>
					<div class="mcew-location-search-v2" data-mcew-location-v2="1" data-all-cities-url="<?php echo esc_url( $all_cities_url ); ?>">
						<?php $this->render_form_markup( $ad_countries, $selected_country, $form_action ); ?>
						<?php if ( function_exists( 'adforest_widget_counter' ) ) { adforest_widget_counter(); } ?>
					</div>
					<?php
					if ( function_exists( 'adforest_advance_search_container' ) ) {
						adforest_advance_search_container();
					}
					return;
				}

				?>
				<div class="accordion-item mcew-location-search-v2" data-mcew-location-v2="1" data-all-cities-url="<?php echo esc_url( $all_cities_url ); ?>">
					<h2 class="accordion-header">
						<button class="accordion-button <?php echo esc_attr( $collapsed ); ?>" type="button" data-bs-toggle="collapse" data-bs-target="#mcew-location-v2-<?php echo esc_attr( $this->number ); ?>" aria-expanded="<?php echo 'show' === $expand ? 'true' : 'false'; ?>" aria-controls="mcew-location-v2-<?php echo esc_attr( $this->number ); ?>">
							<?php echo esc_html( $title ); ?>
						</button>
					</h2>
					<div id="mcew-location-v2-<?php echo esc_attr( $this->number ); ?>" class="accordion-collapse collapse <?php echo esc_attr( $expand ); ?>">
						<div class="accordion-body">
							<?php $this->render_form_markup( $ad_countries, $selected_country, $form_action ); ?>
							<?php if ( function_exists( 'adforest_widget_counter' ) ) { adforest_widget_counter(); } ?>
						</div>
					</div>
				</div>
				<?php
				if ( function_exists( 'adforest_advance_search_container' ) ) {
					adforest_advance_search_container();
				}
				return;
			}

			if ( 'map' === $widget_layout ) {
				?>
				<div class="map_search_countries mcew-location-search-v2" data-mcew-location-v2="1" data-all-cities-url="<?php echo esc_url( $all_cities_url ); ?>">
					<?php if ( '' !== $title ) : ?>
						<h3><?php echo esc_html( $title ); ?></h3>
					<?php endif; ?>
					<?php $this->render_form_markup( $ad_countries, $selected_country, $form_action ); ?>
				</div>
				<?php
				return;
			}

			?>
			<div class="col-sm-6 col-md-4 col-lg-3 mcew-location-search-v2" data-mcew-location-v2="1" data-all-cities-url="<?php echo esc_url( $all_cities_url ); ?>">
				<?php $this->render_form_markup( $ad_countries, $selected_country, $form_action, '' !== $title, $title ); ?>
				<?php if ( function_exists( 'adforest_widget_counter' ) ) { adforest_widget_counter(); } ?>
			</div>
			<?php
			if ( function_exists( 'adforest_advance_search_container' ) ) {
				adforest_advance_search_container();
			}
		}

		/**
		 * Render a near-identical clone of the original widget form.
		 *
		 * @param array<int,mixed> $ad_countries Countries list.
		 * @param string           $selected_country Selected country id.
		 * @param string           $form_action Form action URL.
		 * @param bool             $show_label Whether a visible label should be rendered.
		 * @param string           $label Label text.
		 * @return void
		 */
		private function render_form_markup( $ad_countries, $selected_country, $form_action, $show_label = false, $label = '' ) {
			$search_actions = mcew_search_location_v2_get_actions();
			?>
			<form
				method="get"
				id="search_countries"
				action="<?php echo esc_url( $form_action ); ?>"
				data-default-action="<?php echo esc_url( $search_actions['default_action'] ); ?>"
				data-all-cities-action="<?php echo esc_url( $search_actions['all_cities_action'] ); ?>"
			>
				<div class="form-field">
					<?php if ( $show_label ) : ?>
						<label for="topbar_countries" class="form-label"><?php echo esc_html( $label ); ?></label>
					<?php endif; ?>
					<select class="default-select" id="topbar_countries" data-mcew-location-select>
						<option value="" data-country-id="" <?php selected( '', $selected_country ); ?>><?php echo esc_html__( 'تمام شهرها', 'my-custom-widgets' ); ?></option>
						<?php if ( is_array( $ad_countries ) && count( $ad_countries ) > 0 ) : ?>
							<?php foreach ( $ad_countries as $country ) : ?>
								<?php
								$country_details = function_exists( 'get_taxonomy_details' ) ? get_taxonomy_details( $country ) : array();
								$name            = $country_details['name'] ?? '';
								$country_id      = isset( $country->term_id ) ? (string) $country->term_id : '';
								?>
								<option value="<?php echo esc_attr( $country_id ); ?>" data-country-id="<?php echo esc_attr( $country_id ); ?>" <?php echo ( $country_id === $selected_country ) ? 'selected="selected"' : ''; ?>>
									<?php echo esc_html( $name ); ?>
								</option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
				</div>
				<input type="hidden" name="country_id" id="country_id" data-mcew-country-input value="">
				<?php if ( function_exists( 'bornado_search_render_hidden_query_fields' ) ) : ?>
					<?php echo bornado_search_render_hidden_query_fields( null, array( 'country_id', 'ad_country', 'location' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php elseif ( function_exists( 'adforest_search_params' ) ) : ?>
					<?php echo adforest_search_params( 'country_id' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>
				<?php apply_filters( 'adforest_form_lang_field', true ); ?>
			</form>
			<?php
		}

		/**
		 * Render the same open/close behaviour control used by AdForest widgets.
		 *
		 * This keeps the widget self-contained and avoids a hard dependency on
		 * the theme trait being loaded before this plugin file.
		 *
		 * @param array<string,mixed> $instance Saved widget instance.
		 * @return void
		 */
		private function render_open_widget_control( $instance ) {
			global $adforest_theme;

			$search_design = isset( $adforest_theme['search_design'] ) ? (string) $adforest_theme['search_design'] : '';
			if ( 'sidebar' !== $search_design && 'map' !== $search_design ) {
				return;
			}

			$open_widget    = isset( $instance['open_widget'] ) ? (string) $instance['open_widget'] : '0';
			$open_selected  = ( '1' === $open_widget ) ? 'selected="selected"' : '';
			$close_selected = ( '1' === $open_widget ) ? '' : 'selected="selected"';
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'open_widget' ) ); ?>">
					<?php echo esc_html__( 'Widget behaviour:', 'adforest' ); ?>
				</label>
				<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'open_widget' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'open_widget' ) ); ?>">
					<option value="1" <?php echo wp_kses_post( $open_selected ); ?>><?php echo esc_html__( 'Open', 'adforest' ); ?></option>
					<option value="0" <?php echo wp_kses_post( $close_selected ); ?>><?php echo esc_html__( 'Close', 'adforest' ); ?></option>
				</select>
			</p>
			<?php
		}

		/**
		 * Back-end widget form.
		 *
		 * @param array<string,mixed> $instance Saved values.
		 * @return void
		 */
		public function form( $instance ) {
			$title = isset( $instance['title'] ) ? $instance['title'] : '';
			$this->render_open_widget_control( $instance );
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
					<?php echo esc_html__( 'Title:', 'my-custom-widgets' ); ?>
				</label>
				<input
					class="widefat"
					id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
					name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
					type="text"
					value="<?php echo esc_attr( $title ); ?>"
				>
			</p>
			<p class="description"><?php echo esc_html__( 'This is a standalone WordPress widget clone of AdForest location search with an added "all cities" option.', 'my-custom-widgets' ); ?></p>
			<?php
		}

		/**
		 * Save widget settings.
		 *
		 * @param array<string,mixed> $new_instance New values.
		 * @param array<string,mixed> $old_instance Old values.
		 * @return array<string,mixed>
		 */
		public function update( $new_instance, $old_instance ) {
			$instance          = array();
			$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
			$instance['default_value'] = ! empty( $new_instance['default_value'] ) ? sanitize_text_field( $new_instance['default_value'] ) : '';
			$instance['edit_able'] = ! empty( $new_instance['edit_able'] ) ? sanitize_text_field( $new_instance['edit_able'] ) : '';
			$instance['open_widget'] = ! empty( $new_instance['open_widget'] ) ? sanitize_text_field( $new_instance['open_widget'] ) : '';
			return $instance;
		}
	}
}

if ( ! function_exists( 'mcew_register_search_by_location_v2_widget' ) ) {
	/**
	 * Register the custom WordPress widget.
	 *
	 * @return void
	 */
	function mcew_register_search_by_location_v2_widget() {
		if ( class_exists( 'WP_Widget' ) && class_exists( 'MCEW_Adforest_Search_By_Location_V2' ) ) {
			register_widget( 'MCEW_Adforest_Search_By_Location_V2' );
		}
	}
}
add_action( 'widgets_init', 'mcew_register_search_by_location_v2_widget', 99 );

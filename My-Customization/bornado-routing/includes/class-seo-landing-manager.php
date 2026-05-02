<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Bornado_SEO_Landing_Manager {

	const POST_TYPE            = 'seo_landing';
	const META_ROUTE_TYPE      = '_bornado_landing_route_type';
	const META_COUNTRY_TERM_ID = '_bornado_landing_country_term_id';
	const META_CITY_TERM_ID    = '_bornado_landing_city_term_id';
	const META_CATEGORY_TERM_ID = '_bornado_landing_category_term_id';
	const META_INDEXABLE       = '_bornado_landing_indexable';
	const META_ROUTE_KEY       = '_bornado_landing_route_key';

	/**
	 * Boot the SEO landing content layer.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'register_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta_boxes' ), 10, 2 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'filter_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_admin_columns' ), 10, 2 );
		add_filter( 'post_row_actions', array( __CLASS__, 'filter_row_actions' ), 10, 2 );
		add_filter( 'get_sample_permalink_html', array( __CLASS__, 'filter_sample_permalink_html' ), 10, 5 );
		add_filter( 'post_type_link', array( __CLASS__, 'filter_post_type_link' ), 10, 2 );
	}

	/**
	 * Register the SEO landing CPT used as a real WP object.
	 *
	 * @return void
	 */
	public static function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name'               => __( 'SEO Landing Pages', 'bornado-routing' ),
					'singular_name'      => __( 'SEO Landing Page', 'bornado-routing' ),
					'add_new_item'       => __( 'Add New SEO Landing Page', 'bornado-routing' ),
					'edit_item'          => __( 'Edit SEO Landing Page', 'bornado-routing' ),
					'new_item'           => __( 'New SEO Landing Page', 'bornado-routing' ),
					'view_item'          => __( 'View SEO Landing Page', 'bornado-routing' ),
					'search_items'       => __( 'Search SEO Landing Pages', 'bornado-routing' ),
					'not_found'          => __( 'No SEO Landing Pages found.', 'bornado-routing' ),
					'not_found_in_trash' => __( 'No SEO Landing Pages found in Trash.', 'bornado-routing' ),
					'menu_name'          => __( 'SEO Landing Pages', 'bornado-routing' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'exclude_from_search' => true,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_rest'        => true,
				'has_archive'         => false,
				'rewrite'             => false,
				'query_var'           => true,
				'menu_position'       => 26,
				'menu_icon'           => 'dashicons-analytics',
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'capability_type'     => 'page',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Register admin meta boxes.
	 *
	 * @return void
	 */
	public static function register_meta_boxes() {
		add_meta_box(
			'bornado-seo-landing-route',
			__( 'Landing Route Mapping', 'bornado-routing' ),
			array( __CLASS__, 'render_route_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Render route mapping controls.
	 *
	 * @param WP_Post $post Current post.
	 * @return void
	 */
	public static function render_route_meta_box( $post ) {
		wp_nonce_field( 'bornado_seo_landing_save', 'bornado_seo_landing_nonce' );

		$route_type       = self::get_meta( $post->ID, self::META_ROUTE_TYPE, 'country_city_category' );
		$country_term_id  = (int) self::get_meta( $post->ID, self::META_COUNTRY_TERM_ID, 0 );
		$city_term_id     = (int) self::get_meta( $post->ID, self::META_CITY_TERM_ID, 0 );
		$category_term_id = (int) self::get_meta( $post->ID, self::META_CATEGORY_TERM_ID, 0 );
		$indexable        = '1' === self::get_meta( $post->ID, self::META_INDEXABLE, '1' );
		$route_url        = self::get_preview_url( $post->ID );
		$route_key        = self::get_meta( $post->ID, self::META_ROUTE_KEY, '' );
		$duplicate_notice = self::get_meta( $post->ID, '_bornado_landing_duplicate_notice', '' );
		if ( '' !== $duplicate_notice ) :
			?>
			<div class="notice notice-warning inline"><p><?php echo esc_html( $duplicate_notice ); ?></p></div>
			<?php
		endif;
		?>
		<p>
			<label for="bornado_landing_route_type"><strong><?php esc_html_e( 'Route Type', 'bornado-routing' ); ?></strong></label>
			<select id="bornado_landing_route_type" name="bornado_landing_route_type" class="widefat">
				<option value="category_only" <?php selected( $route_type, 'category_only' ); ?>><?php esc_html_e( 'Category only', 'bornado-routing' ); ?></option>
				<option value="country_only" <?php selected( $route_type, 'country_only' ); ?>><?php esc_html_e( 'Country only', 'bornado-routing' ); ?></option>
				<option value="country_city" <?php selected( $route_type, 'country_city' ); ?>><?php esc_html_e( 'Country + City', 'bornado-routing' ); ?></option>
				<option value="country_category" <?php selected( $route_type, 'country_category' ); ?>><?php esc_html_e( 'Country + Category', 'bornado-routing' ); ?></option>
				<option value="country_city_category" <?php selected( $route_type, 'country_city_category' ); ?>><?php esc_html_e( 'Country + City + Category', 'bornado-routing' ); ?></option>
			</select>
		</p>
		<p>
			<label for="bornado_landing_country_term_id"><strong><?php esc_html_e( 'Country (ad_country root)', 'bornado-routing' ); ?></strong></label>
			<?php
			wp_dropdown_categories(
				array(
					'taxonomy'          => 'ad_country',
					'hide_empty'        => false,
					'name'              => 'bornado_landing_country_term_id',
					'id'                => 'bornado_landing_country_term_id',
					'selected'          => $country_term_id,
					'show_option_none'  => __( 'None', 'bornado-routing' ),
					'option_none_value' => '0',
					'class'             => 'widefat',
					'value_field'       => 'term_id',
				)
			);
			?>
		</p>
		<p>
			<label for="bornado_landing_city_term_id"><strong><?php esc_html_e( 'City (ad_country child)', 'bornado-routing' ); ?></strong></label>
			<?php
			wp_dropdown_categories(
				array(
					'taxonomy'          => 'ad_country',
					'hide_empty'        => false,
					'name'              => 'bornado_landing_city_term_id',
					'id'                => 'bornado_landing_city_term_id',
					'selected'          => $city_term_id,
					'show_option_none'  => __( 'None', 'bornado-routing' ),
					'option_none_value' => '0',
					'class'             => 'widefat',
					'value_field'       => 'term_id',
				)
			);
			?>
		</p>
		<p>
			<label for="bornado_landing_category_term_id"><strong><?php esc_html_e( 'Deepest Category (ad_cats)', 'bornado-routing' ); ?></strong></label>
			<?php
			wp_dropdown_categories(
				array(
					'taxonomy'          => 'ad_cats',
					'hide_empty'        => false,
					'name'              => 'bornado_landing_category_term_id',
					'id'                => 'bornado_landing_category_term_id',
					'selected'          => $category_term_id,
					'show_option_none'  => __( 'None', 'bornado-routing' ),
					'option_none_value' => '0',
					'class'             => 'widefat',
					'value_field'       => 'term_id',
					'hierarchical'      => true,
				)
			);
			?>
		</p>
		<p>
			<label>
				<input type="checkbox" name="bornado_landing_indexable" value="1" <?php checked( $indexable ); ?> />
				<?php esc_html_e( 'Indexable landing page', 'bornado-routing' ); ?>
			</label>
		</p>
		<p>
			<strong><?php esc_html_e( 'Route key', 'bornado-routing' ); ?></strong><br />
			<code><?php echo esc_html( $route_key ? $route_key : __( 'Will be generated on save', 'bornado-routing' ) ); ?></code>
		</p>
		<p>
			<strong><?php esc_html_e( 'Preview URL', 'bornado-routing' ); ?></strong><br />
			<?php if ( $route_url ) : ?>
				<a href="<?php echo esc_url( $route_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $route_url ); ?></a>
			<?php else : ?>
				<?php esc_html_e( 'Incomplete route mapping.', 'bornado-routing' ); ?>
			<?php endif; ?>
		</p>
		<?php
	}

	/**
	 * Persist route mapping meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post object.
	 * @return void
	 */
	public static function save_meta_boxes( $post_id, $post ) {
		if ( ! isset( $_POST['bornado_seo_landing_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['bornado_seo_landing_nonce'] ) ), 'bornado_seo_landing_save' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$route_type       = isset( $_POST['bornado_landing_route_type'] ) ? sanitize_key( wp_unslash( $_POST['bornado_landing_route_type'] ) ) : 'country_city_category';
		$allowed_types    = array( 'category_only', 'country_only', 'country_city', 'country_category', 'country_city_category' );
		$route_type       = in_array( $route_type, $allowed_types, true ) ? $route_type : 'country_city_category';
		$country_term_id  = isset( $_POST['bornado_landing_country_term_id'] ) ? max( 0, (int) wp_unslash( $_POST['bornado_landing_country_term_id'] ) ) : 0;
		$city_term_id     = isset( $_POST['bornado_landing_city_term_id'] ) ? max( 0, (int) wp_unslash( $_POST['bornado_landing_city_term_id'] ) ) : 0;
		$category_term_id = isset( $_POST['bornado_landing_category_term_id'] ) ? max( 0, (int) wp_unslash( $_POST['bornado_landing_category_term_id'] ) ) : 0;
		$indexable        = ! empty( $_POST['bornado_landing_indexable'] ) ? '1' : '0';

		if ( $city_term_id > 0 && $country_term_id < 1 ) {
			$country_term_id = self::get_root_country_term_id( $city_term_id );
		}

		if ( 'category_only' === $route_type ) {
			$country_term_id = 0;
			$city_term_id    = 0;
		} elseif ( 'country_only' === $route_type ) {
			$city_term_id     = 0;
			$category_term_id = 0;
		} elseif ( 'country_city' === $route_type ) {
			$category_term_id = 0;
		} elseif ( 'country_category' === $route_type ) {
			$city_term_id = 0;
		}

		if ( $city_term_id > 0 && ! self::is_term_within_country( $city_term_id, $country_term_id ) ) {
			$city_term_id = 0;
		}

		update_post_meta( $post_id, self::META_ROUTE_TYPE, $route_type );
		update_post_meta( $post_id, self::META_COUNTRY_TERM_ID, $country_term_id );
		update_post_meta( $post_id, self::META_CITY_TERM_ID, $city_term_id );
		update_post_meta( $post_id, self::META_CATEGORY_TERM_ID, $category_term_id );
		update_post_meta( $post_id, self::META_INDEXABLE, $indexable );

		$route_key = self::build_route_key( $route_type, $country_term_id, $city_term_id, $category_term_id );
		update_post_meta( $post_id, self::META_ROUTE_KEY, $route_key );

		if ( '' !== $route_key ) {
			self::maybe_flag_duplicate_route( $post_id, $post->post_title, $route_key );
		}
	}

	/**
	 * Find a published landing page for a resolved route.
	 *
	 * @param array<string,mixed> $route_context Route context.
	 * @return WP_Post|null
	 */
	public static function find_matching_landing( array $route_context ) {
		$route_type = self::determine_route_type( $route_context );
		if ( ! $route_type ) {
			return null;
		}

		$country_term_id  = ! empty( $route_context['country_term'] ) && $route_context['country_term'] instanceof WP_Term ? (int) $route_context['country_term']->term_id : 0;
		$city_term_id     = ! empty( $route_context['city_term'] ) && $route_context['city_term'] instanceof WP_Term ? (int) $route_context['city_term']->term_id : 0;
		$category_term_id = ! empty( $route_context['deepest_term'] ) && $route_context['deepest_term'] instanceof WP_Term ? (int) $route_context['deepest_term']->term_id : 0;
		$route_key        = self::build_route_key( $route_type, $country_term_id, $city_term_id, $category_term_id );

		if ( '' === $route_key ) {
			return null;
		}

		$posts = get_posts(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'orderby'                => 'modified',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'cache_results'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => self::META_ROUTE_KEY,
						'value' => $route_key,
					),
				),
			)
		);

		return ! empty( $posts[0] ) && $posts[0] instanceof WP_Post ? $posts[0] : null;
	}

	/**
	 * Whether a landing should be indexable.
	 *
	 * @param WP_Post|null $post Landing post.
	 * @return bool
	 */
	public static function is_indexable( $post ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return false;
		}

		return '1' === self::get_meta( $post->ID, self::META_INDEXABLE, '1' );
	}

	/**
	 * Get preview URL for a landing.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	public static function get_preview_url( $post_id ) {
		$route_type       = self::get_meta( $post_id, self::META_ROUTE_TYPE, '' );
		$country_term_id  = (int) self::get_meta( $post_id, self::META_COUNTRY_TERM_ID, 0 );
		$city_term_id     = (int) self::get_meta( $post_id, self::META_CITY_TERM_ID, 0 );
		$category_term_id = (int) self::get_meta( $post_id, self::META_CATEGORY_TERM_ID, 0 );

		if ( 'category_only' === $route_type && ! $category_term_id ) {
			return '';
		}

		if ( 'country_only' === $route_type && ! $country_term_id ) {
			return '';
		}

		if ( 'country_city' === $route_type && ( ! $country_term_id || ! $city_term_id ) ) {
			return '';
		}

		if ( 'country_category' === $route_type && ( ! $country_term_id || ! $category_term_id ) ) {
			return '';
		}

		if ( 'country_city_category' === $route_type && ( ! $country_term_id || ! $city_term_id || ! $category_term_id ) ) {
			return '';
		}

		if ( ! class_exists( 'Bornado_SEO_Routing' ) ) {
			return '';
		}

		return Bornado_SEO_Routing::get_semantic_url_preview( $country_term_id, $city_term_id, $category_term_id );
	}

	/**
	 * Resolve the public semantic route for a landing post.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 * @return string
	 */
	public static function get_public_route_url( $post ) {
		$post = get_post( $post );
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return '';
		}

		return self::get_preview_url( $post->ID );
	}

	/**
	 * Extend admin columns for quick inspection.
	 *
	 * @param array<string,string> $columns Existing columns.
	 * @return array<string,string>
	 */
	public static function filter_admin_columns( $columns ) {
		$columns['bornado_route']     = __( 'Semantic Route', 'bornado-routing' );
		$columns['bornado_indexable'] = __( 'Indexable', 'bornado-routing' );

		return $columns;
	}

	/**
	 * Render custom admin columns.
	 *
	 * @param string $column_name Column slug.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public static function render_admin_columns( $column_name, $post_id ) {
		if ( 'bornado_route' === $column_name ) {
			$route_url = self::get_preview_url( $post_id );
			if ( $route_url ) {
				echo '<a href="' . esc_url( $route_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( wp_parse_url( $route_url, PHP_URL_PATH ) ) . '</a>';
			} else {
				echo esc_html__( 'Incomplete', 'bornado-routing' );
			}
			return;
		}

		if ( 'bornado_indexable' === $column_name ) {
			echo self::is_indexable( get_post( $post_id ) ) ? esc_html__( 'Yes', 'bornado-routing' ) : esc_html__( 'No', 'bornado-routing' );
		}
	}

	/**
	 * Add direct preview link in row actions.
	 *
	 * @param array<string,string> $actions Row actions.
	 * @param WP_Post              $post Current post.
	 * @return array<string,string>
	 */
	public static function filter_row_actions( $actions, $post ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return $actions;
		}

		$route_url = self::get_preview_url( $post->ID );
		if ( $route_url ) {
			$actions['bornado_route_preview'] = '<a href="' . esc_url( $route_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Open semantic route', 'bornado-routing' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Replace the editor permalink box with the semantic route.
	 *
	 * @param string   $html Existing markup.
	 * @param int      $post_id Post ID.
	 * @param string   $new_title Suggested title.
	 * @param string   $new_slug Suggested slug.
	 * @param WP_Post  $post Current post object.
	 * @return string
	 */
	public static function filter_sample_permalink_html( $html, $post_id, $new_title, $new_slug, $post ) {
		unset( $new_title, $new_slug );

		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return $html;
		}

		$route_url = self::get_preview_url( $post_id );
		if ( ! $route_url ) {
			return $html;
		}

		return sprintf(
			'<strong>%1$s</strong> <a href="%2$s" target="_blank" rel="noopener noreferrer">%3$s</a>',
			esc_html__( 'Semantic URL:', 'bornado-routing' ),
			esc_url( $route_url ),
			esc_html( $route_url )
		);
	}

	/**
	 * Make the public permalink of the landing post equal the semantic route.
	 *
	 * This keeps sitemaps, schema builders, and SEO plugins aligned with the real public URL.
	 *
	 * @param string  $post_link Generated permalink.
	 * @param WP_Post $post Current post.
	 * @return string
	 */
	public static function filter_post_type_link( $post_link, $post ) {
		if ( ! $post instanceof WP_Post || self::POST_TYPE !== $post->post_type ) {
			return $post_link;
		}

		$route_url = self::get_public_route_url( $post );

		return $route_url ? $route_url : $post_link;
	}

	/**
	 * Determine landing route type from resolved route context.
	 *
	 * @param array<string,mixed> $route_context Route context.
	 * @return string
	 */
	private static function determine_route_type( array $route_context ) {
		$has_country  = ! empty( $route_context['country_term'] ) && $route_context['country_term'] instanceof WP_Term;
		$has_city     = ! empty( $route_context['city_term'] ) && $route_context['city_term'] instanceof WP_Term;
		$has_category = ! empty( $route_context['deepest_term'] ) && $route_context['deepest_term'] instanceof WP_Term;

		if ( $has_country && $has_city && $has_category ) {
			return 'country_city_category';
		}

		if ( $has_country && $has_city ) {
			return 'country_city';
		}

		if ( $has_country && $has_category ) {
			return 'country_category';
		}

		if ( $has_country ) {
			return 'country_only';
		}

		if ( $has_category ) {
			return 'category_only';
		}

		return '';
	}

	/**
	 * Build a deterministic route key.
	 *
	 * @param string $route_type Route type.
	 * @param int    $country_term_id Country term ID.
	 * @param int    $city_term_id City term ID.
	 * @param int    $category_term_id Deepest category term ID.
	 * @return string
	 */
	private static function build_route_key( $route_type, $country_term_id, $city_term_id, $category_term_id ) {
		$route_type = sanitize_key( (string) $route_type );
		if ( '' === $route_type ) {
			return '';
		}

		return implode(
			':',
			array(
				$route_type,
				(string) (int) $country_term_id,
				(string) (int) $city_term_id,
				(string) (int) $category_term_id,
			)
		);
	}

	/**
	 * Surface duplicate route keys in the editor.
	 *
	 * @param int    $post_id Post ID being saved.
	 * @param string $post_title Post title.
	 * @param string $route_key Generated route key.
	 * @return void
	 */
	private static function maybe_flag_duplicate_route( $post_id, $post_title, $route_key ) {
		$duplicates = get_posts(
			array(
				'post_type'              => self::POST_TYPE,
				'post_status'            => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'post__not_in'           => array( $post_id ),
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'cache_results'          => false,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
				'meta_query'             => array(
					array(
						'key'   => self::META_ROUTE_KEY,
						'value' => $route_key,
					),
				),
			)
		);

		if ( empty( $duplicates ) ) {
			delete_post_meta( $post_id, '_bornado_landing_duplicate_notice' );
			return;
		}

		update_post_meta(
			$post_id,
			'_bornado_landing_duplicate_notice',
			sprintf(
				/* translators: 1: current title, 2: duplicate post ID */
				__( 'Landing "%1$s" shares the same route mapping with post ID %2$d. Keep only one published landing per semantic route.', 'bornado-routing' ),
				$post_title ? $post_title : '#' . $post_id,
				(int) $duplicates[0]->ID
			)
		);
	}

	/**
	 * Resolve the root country term id for a location term.
	 *
	 * @param int $term_id Location term id.
	 * @return int
	 */
	private static function get_root_country_term_id( $term_id ) {
		$term_id = (int) $term_id;
		if ( $term_id < 1 ) {
			return 0;
		}

		$term = get_term( $term_id, 'ad_country' );
		if ( ! $term instanceof WP_Term ) {
			return 0;
		}

		if ( 0 === (int) $term->parent ) {
			return (int) $term->term_id;
		}

		$ancestors = array_reverse( array_map( 'intval', get_ancestors( (int) $term->term_id, 'ad_country', 'taxonomy' ) ) );

		return ! empty( $ancestors ) ? (int) $ancestors[0] : 0;
	}

	/**
	 * Return whether a location term belongs to a given country term.
	 *
	 * @param int $term_id Location term id.
	 * @param int $country_term_id Country term id.
	 * @return bool
	 */
	private static function is_term_within_country( $term_id, $country_term_id ) {
		$term_id         = (int) $term_id;
		$country_term_id = (int) $country_term_id;

		if ( $term_id < 1 || $country_term_id < 1 ) {
			return false;
		}

		if ( $term_id === $country_term_id ) {
			return true;
		}

		return in_array( $country_term_id, array_map( 'intval', get_ancestors( $term_id, 'ad_country', 'taxonomy' ) ), true );
	}

	/**
	 * Read a post meta value with default fallback.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key Meta key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private static function get_meta( $post_id, $key, $default = '' ) {
		$value = get_post_meta( $post_id, $key, true );

		return '' === $value ? $default : $value;
	}
}

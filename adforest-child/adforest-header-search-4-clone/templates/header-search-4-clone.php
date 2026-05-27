<?php
global $adforest_theme;
$site_logo = isset($adforest_theme['sb_site_logo']['url']) ? $adforest_theme['sb_site_logo']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$sb_sign_in_page = isset($adforest_theme['sb_sign_in_page']) ? $adforest_theme['sb_sign_in_page'] : "";
$sb_sign_up_page = isset($adforest_theme['sb_sign_up_page']) ? $adforest_theme['sb_sign_up_page'] : "";
$ad_in_menu_text = isset($adforest_theme['ad_in_menu_text']) ? $adforest_theme['ad_in_menu_text'] : "";
// Apply WPML translation using icl_t()
if (function_exists('icl_t')) {
    $ad_in_menu_text = icl_t('adforest_theme', 'ad_in_menu_text', $ad_in_menu_text);
}
$sb_post_ad_page = isset($adforest_theme['sb_post_ad_page']) ? $adforest_theme['sb_post_ad_page'] : "";
$sign_in_url = !empty($sb_sign_in_page) ? get_the_permalink($sb_sign_in_page) : '';
$sign_up_url = !empty($sb_sign_up_page) ? get_the_permalink($sb_sign_up_page) : '';
$show_sign_in = !empty($sign_in_url);
$show_sign_up = !empty($sign_up_url);
$responsive_logo = isset($adforest_theme['sb_site_logo_mobile']['url']) ? $adforest_theme['sb_site_logo_mobile']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$home_page_logo = isset($adforest_theme['sb_home_logo']['url']) ? $adforest_theme['sb_home_logo']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$user_id = get_current_user_id();

$is_sticky_header = isset($adforest_theme['sb_sticky_header']) ? $adforest_theme['sb_sticky_header'] : '';
$sticky_class = "";
if ($is_sticky_header == '1') {
    $sticky_class = "sticky-header";
}

$sb_profile_page = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';
$selected_search_context = function_exists('bornado_search_get_selected_context')
    ? bornado_search_get_selected_context()
    : array();
$selected_location_value = '';
if (!empty($selected_search_context['city'])) {
    $selected_location_value = (string) $selected_search_context['city'];
} elseif (!empty($selected_search_context['country'])) {
    $selected_location_value = (string) $selected_search_context['country'];
}
$selected_category_value = !empty($selected_search_context['category']) ? (int) $selected_search_context['category'] : 0;

$topbar_cats = $adforest_theme['adforest_header_ad_cats_selection'] ?? [];
$center_index = floor((count($topbar_cats) - 1) / 2);
$default_cat_id = isset($topbar_cats[$center_index]) ? intval($topbar_cats[$center_index]) : 0;
$default_slug = '';

if ($default_cat_id) {
    $default_term = get_term($default_cat_id, 'ad_cats');
    if ($default_term && !is_wp_error($default_term)) {
        $default_slug = $default_term->slug;
    }
}

$default_search_fields = array('keyword', 'ad_type', 'location');
$enabled_fields = array();

if (isset($adforest_theme['header_search_enabled_fields']) && is_array($adforest_theme['header_search_enabled_fields'])) {
    foreach ($adforest_theme['header_search_enabled_fields'] as $field_key) {
        $field_key = sanitize_key($field_key);
        if (in_array($field_key, array('keyword', 'ad_type', 'location', 'category'), true)) {
            $enabled_fields[] = $field_key;
        }
    }
}

if (!empty($enabled_fields)) {
    $enabled_fields = array_values(array_unique($enabled_fields));
}

if (empty($enabled_fields)) {
    $enabled_fields = $default_search_fields;
}

$default_field_labels = array(
    'keyword' => esc_html__('Explore', 'adforest'),
    'ad_type' => esc_html__('Type', 'adforest'),
    'location' => esc_html__('Location', 'adforest'),
    'category' => esc_html__('Category', 'adforest'),
);

$default_field_placeholders = array(
    'keyword' => esc_html__('What Are You Looking for...', 'adforest'),
    'ad_type' => esc_html__('Select an Option', 'adforest'),
    'location' => esc_html__('Select an Option', 'adforest'),
    'category' => esc_html__('Select an Option', 'adforest'),
);

$field_labels = array(
    'keyword' => isset($adforest_theme['header_search_keyword_label']) && $adforest_theme['header_search_keyword_label'] !== '' ? $adforest_theme['header_search_keyword_label'] : $default_field_labels['keyword'],
    'ad_type' => isset($adforest_theme['header_search_ad_type_label']) && $adforest_theme['header_search_ad_type_label'] !== '' ? $adforest_theme['header_search_ad_type_label'] : $default_field_labels['ad_type'],
    'location' => isset($adforest_theme['header_search_location_label']) && $adforest_theme['header_search_location_label'] !== '' ? $adforest_theme['header_search_location_label'] : $default_field_labels['location'],
    'category' => isset($adforest_theme['header_search_category_label']) && $adforest_theme['header_search_category_label'] !== '' ? $adforest_theme['header_search_category_label'] : $default_field_labels['category'],
);

$field_display_labels = array(
    'keyword' => $field_labels['keyword'],
    'ad_type' => $field_labels['ad_type'],
    'location' => esc_html__('کشور و شهر', 'adforest'),
    'category' => $field_labels['category'],
);

$field_placeholders = array(
    'keyword' => isset($adforest_theme['header_search_keyword_placeholder']) && $adforest_theme['header_search_keyword_placeholder'] !== '' ? $adforest_theme['header_search_keyword_placeholder'] : $default_field_placeholders['keyword'],
    'ad_type' => isset($adforest_theme['header_search_ad_type_placeholder']) && $adforest_theme['header_search_ad_type_placeholder'] !== '' ? $adforest_theme['header_search_ad_type_placeholder'] : $default_field_placeholders['ad_type'],
    'location' => isset($adforest_theme['header_search_location_placeholder']) && $adforest_theme['header_search_location_placeholder'] !== '' ? $adforest_theme['header_search_location_placeholder'] : $default_field_placeholders['location'],
    'category' => isset($adforest_theme['header_search_category_placeholder']) && $adforest_theme['header_search_category_placeholder'] !== '' ? $adforest_theme['header_search_category_placeholder'] : $default_field_placeholders['category'],
);

$needs_ad_types = in_array('ad_type', $enabled_fields, true);
$needs_locations = in_array('location', $enabled_fields, true);
$needs_categories_dropdown = in_array('category', $enabled_fields, true);
$has_category_field = $needs_categories_dropdown;

$ad_types = ($needs_ad_types && function_exists('adforest_get_ad_taxonomy_callback')) ? adforest_get_ad_taxonomy_callback('ad_type') : array();

if (!function_exists('adforest_header_get_hierarchical_terms')) {
    function adforest_header_get_hierarchical_terms($taxonomy, $args = array(), $parent = 0, $depth = 0)
    {
        $defaults = array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'parent' => $parent,
        );
        $terms = get_terms(array_merge($defaults, $args));
        $results = array();

        if (is_wp_error($terms) || empty($terms)) {
            return $results;
        }

        foreach ($terms as $term) {
            $label_prefix = $depth > 0 ? str_repeat('— ', $depth) : '';
            $results[] = array(
                'term' => $term,
                'label' => $label_prefix . $term->name,
            );

            $child_terms = adforest_header_get_hierarchical_terms($taxonomy, $args, $term->term_id, $depth + 1);
            if (!empty($child_terms)) {
                $results = array_merge($results, $child_terms);
            }
        }

        return $results;
    }
}

$location_terms = array();
if ($needs_locations && taxonomy_exists('ad_country')) {
    $location_terms = get_terms(array(
        'taxonomy' => 'ad_country',
        'hide_empty' => false,
        'parent' => 0,
        'number' => 0,
        'orderby' => 'name',
        'order' => 'ASC',
    ));
    if (is_wp_error($location_terms) || !is_array($location_terms)) {
        $location_terms = array();
    }
}
$category_terms = $needs_categories_dropdown ? adforest_header_get_hierarchical_terms('ad_cats') : array();

if (!function_exists('adforest_header_render_hidden_fields')) {
    function adforest_header_render_hidden_fields($name, $value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                adforest_header_render_hidden_fields($name . '[' . $key . ']', $val);
            }
        } else {
            if (!is_numeric($value) && trim((string) $value) === '') {
                return;
            }
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">' . "\n";
        }
    }
}

if (!function_exists('adforest_header_get_clean_hidden_query_args')) {
    /**
     * Return sanitized current query args for hidden field rendering.
     *
     * @param array<int,string> $excluded_params Parameters that should not be re-rendered.
     * @return array<string,mixed>
     */
    function adforest_header_get_clean_hidden_query_args($excluded_params = array())
    {
        if (function_exists('bornado_search_get_current_query_args')) {
            return bornado_search_get_current_query_args($excluded_params);
        }

        $source = isset($_GET) ? wp_unslash($_GET) : array();
        if (!is_array($source)) {
            return array();
        }

        $clean = array();
        foreach ($source as $key => $value) {
            $key = is_string($key) ? trim($key) : '';
            if ($key === '' || in_array($key, $excluded_params, true)) {
                continue;
            }

            if (is_array($value)) {
                $value = array_filter($value, function ($item) {
                    return is_numeric($item) || trim((string) $item) !== '';
                });
                if (empty($value)) {
                    continue;
                }
                $clean[$key] = $value;
                continue;
            }

            if (is_numeric($value) || trim((string) $value) !== '') {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const searchCore = window.BornadoSearchCore || null;
        const params = searchCore && typeof searchCore.getCleanCurrentSearchParams === "function"
            ? searchCore.getCleanCurrentSearchParams()
            : new URLSearchParams(window.location.search);

        const defaultCatId = <?php echo json_encode( (int) $default_cat_id ); ?>;
        const defaultSlug = <?php echo json_encode( sanitize_title( $default_slug ) ); ?>;
        if (!params.has('cat_id') && defaultCatId && defaultSlug) {
rams.toString()}`;
            window.history.pushState({}, '', newUrl);

            const tabTriggerEl = document.querySelector(`#pills-${defaultSlug}-tab`);
            if (tabTriggerEl) {
                tabTriggerEl.click();
            }
            if (tabTriggerEl) {
                tabTriggerEl.classList.add('active');
                tabTriggerEl.setAttribute('aria-selected', 'true');

                document.querySelectorAll('.nav-link').forEach(el => {
                    if (el !== tabTriggerEl) {
                        el.classList.remove('active');
                        el.setAttribute('aria-selected', 'false');
                    }
                });

                const tabContentEl = document.querySelector(`#pills-${defaultSlug}`);
                if (tabContentEl) {
                    document.querySelectorAll('.tab-pane').forEach(el => el.classList.remove('show', 'active'));
                    tabContentEl.classList.add('show', 'active');
                }
            }
        }
    });
</script>

<div class="adt-top-tabs-header <?php echo esc_attr($sticky_class); ?>">
    <div class="container">
        <div class="tabs-container">
            <div class="logo" data-mobile-logo="<?php echo esc_url($responsive_logo) ?>"
                 data-sticky-logo="<?php echo esc_url($responsive_logo) ?>" <?php echo is_user_logged_in() ? 'style="margin-right: 0px !important"' : ""; ?> >
                <a href="<?php echo esc_url($brand_home_url); ?>"><img src="<?php echo esc_url($site_logo); ?>"
                <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url($site_logo); ?>"
            </div>
            <div class="tabs-wrapper">
                <?php if (!empty($topbar_cats)) : ?>
                    <ol class="nav nav-pills" id="pills-tab" role="tablist">
                        <?php foreach ($topbar_cats as $cat_id) :
                            $taxonomy = get_term($cat_id, 'ad_cats');

                            if (!is_wp_error($taxonomy) && $taxonomy) :
                                $taxonomy_image = get_option('adforest_taxonomy_image' . $taxonomy->term_id);
                                $current_cat_id = $selected_category_value;
                                $is_active = ($current_cat_id == $cat_id) ? 'active' : '';
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo esc_attr($is_active); ?>"
                                            id="pills-<?php echo esc_attr($taxonomy->slug); ?>-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-<?php echo esc_attr($taxonomy->slug); ?>"
                                            type="button" role="tab"
                                            aria-controls="pills-<?php echo esc_attr($taxonomy->slug); ?>"
                                                       data-target-url="<?php echo esc_url($tab_target_url); ?>"
                                            type="button" role="tab"
                                            aria-controls="pills-<?php echo esc_attr($taxonomy->slug); ?>"
                                            aria-selected="<?php echo esc_attr( $current_cat_id === $cat_id ? 'true' : 'false' ); ?>"
                                            onclick="setCategory(<?php echo esc_js($cat_id); ?>)">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <img style="width: 18px; margin-right: 5px"
                                                 src="<?php echo esc_url($taxonomy_image); ?>"
                                                 alt="<?php echo esc_attr($taxonomy->name); ?>"/>
                                            <span><?php echo esc_html($taxonomy->name); ?></span>
                                        </div>
                                    </button>
                                </li>
                            <?php endif; endforeach; ?>
                    </ol>
                <?php endif; ?>

                <div class="tab-content" id="pills-tabContent">
                    <?php
                    $search_actions = function_exists('bornado_search_get_actions')
                        ? bornado_search_get_actions()
                        : array(
                            'default_action' => home_url('/'),
                            'all_cities_action' => home_url('/'),
                            'all_categories_action' => home_url('/'),
                            'all_filters_action' => home_url('/'),
                        );
                    $safe_action_url = $search_actions['default_action'];
                    $all_cities_target_url = $search_actions['all_cities_action'];
                    $all_categories_target_url = $search_actions['all_categories_action'];
                    $all_filters_target_url = $search_actions['all_filters_action'];
                    ?>
                    <style>
                        .adt-top-tabs-header {
                            height: auto;
                            padding: 10px 0;
                            background: #ffffff;
                            border-bottom: 1px solid #e7ebf0;
                            box-shadow: 0 6px 20px rgba(15, 23, 42, 0.04);
                            z-index: 100;
                        }

                        .adt-top-tabs-header .tabs-container {
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            gap: 16px;
                        }

                        .adt-top-tabs-header .tabs-container .logo {
                            margin-right: 0;
                            flex: 0 0 auto;
                        }

                        .adt-top-tabs-header .tabs-container .logo img {
                            display: block;
                            max-height: 38px;
                            width: auto;
                        }

                        @media (max-width: 767.98px) {
                            .adt-top-tabs-header {
                                display: none;
                            }
                        }

                        .adt-top-tabs-header .tabs-wrapper {
                            flex: 1 1 auto;
                            min-width: 0;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            gap: 12px;
                        }

                        .adt-top-tabs-header .tabs-wrapper .nav.nav-pills {
                            display: flex;
                            flex-wrap: nowrap;
                            align-items: center;
                            gap: 6px;
                            flex: 0 1 auto;
                            max-width: 26%;
                            min-width: 165px;
                            margin: 0;
                            padding: 0;
                            overflow-x: auto;
                            scrollbar-width: none;
                        }

                        .adt-top-tabs-header .tabs-wrapper .nav.nav-pills::-webkit-scrollbar {
                            display: none;
                        }

                        .adt-top-tabs-header .tabs-wrapper ol li {
                            margin: 0;
                            flex: 0 0 auto;
                        }

                        .adt-top-tabs-header .tabs-wrapper ol li::before,
                        .adt-top-tabs-header .tabs-wrapper ol li .nav-link.active::after {
                            content: none;
                        }

                        .adt-top-tabs-header .tabs-wrapper ol li .nav-link {
                            width: auto;
                            min-width: max-content;
                            padding: 8px 12px;
                            border: 1px solid #e7ebf0;
                            border-radius: 999px;
                            background: #f8fafc;
                            color: #475569;
                            font-size: 12px;
                            font-weight: 500;
                            line-height: 1.2;
                            transition: all 0.2s ease;
                        }

                        .adt-top-tabs-header .tabs-wrapper ol li .nav-link:hover,
                        .adt-top-tabs-header .tabs-wrapper ol li .nav-link.active {
                            background: #0f172a;
                            border-color: #0f172a;
                            color: #ffffff;
                        }

                        .adt-top-tabs-header .tabs-wrapper ol li .nav-link img {
                            width: 15px;
                            height: 15px;
                            margin-inline-end: 6px;
                            object-fit: contain;
                        }

                        .adt-top-tabs-header .tabs-wrapper .tab-content {
                            flex: 0 1 620px;
                            max-width: 620px;
                            min-width: 0;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs {
                            margin-bottom: 0;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar {
                            position: static;
                            width: 100%;
                            min-width: 0;
                            margin: 0;
                            padding: 5px 8px;
                            display: flex;
                            align-items: center;
                            gap: 0;
                            border: 1px solid #e7ebf0;
                            border-radius: 16px;
                            background: #fbfcfe;
                            box-shadow: 0 4px 14px rgba(15, 23, 42, 0.035);
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box {
                            position: relative;
                            min-height: 0;
                            min-width: 0;
                            width: auto;
                            flex: 1 1 0;
                            padding: 0 10px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box--keyword {
                            min-width: 135px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box--ad-type {
                            min-width: 125px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box--location {
                            min-width: 155px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box--category {
                            min-width: 145px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box + .filter-box {
                            border-inline-start: 1px solid #eef2f7;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .filter-box.type-box::before {
                            content: none;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .filter-box label,
                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__trigger-label {
                            position: absolute !important;
                            width: 1px;
                            height: 1px;
                            padding: 0;
                            margin: -1px;
                            overflow: hidden;
                            clip: rect(0, 0, 0, 0);
                            white-space: nowrap;
                            border: 0;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box input,
                        .adt-hero-search-tabs .search-filters-bar .filter-box select.default-select {
                            width: 100%;
                            height: 40px;
                            display: block;
                            background: #f8fafc;
                            border: 0;
                            border-radius: 10px;
                            font-size: 13px;
                            font-weight: 400;
                            color: #0f172a;
                            line-height: 40px;
                            padding: 0 34px 0 8px;
                            box-shadow: none;
                            transition: background-color 0.2s ease;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box input::placeholder {
                            color: #94a3b8;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box input:focus,
                        .adt-hero-search-tabs .search-filters-bar .filter-box select.default-select:focus {
                            outline: none;
                            background: #f8fafc;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container {
                            width: 100% !important;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container--default .select2-selection--single {
                            height: 40px;
                            margin-bottom: 0;
                            border: 0;
                            border-radius: 10px;
                            background: #f8fafc;
                            box-shadow: none;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container--default .select2-selection--single .select2-selection__rendered {
                            height: 40px;
                            padding: 0 34px 0 8px;
                            font-size: 13px;
                            font-weight: 400;
                            line-height: 40px;
                            color: #0f172a;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container--default .select2-selection--single .select2-selection__arrow {
                            width: 26px;
                            height: 26px;
                            top: 7px;
                            right: 8px;
                            background: transparent;
                            border: 0;
                            border-radius: 0;
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container--default .select2-selection--single .select2-selection__arrow b {
                            border-color: #94a3b8 #94a3b8 transparent transparent;
                            border-width: 1px 1px 0 0;
                            width: 6px;
                            height: 6px;
                            margin-top: -4px;
                            margin-left: -3px;
                            transform: rotate(135deg);
                        }

                        .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container--default.select2-container--open .select2-selection--single .select2-selection__arrow b {
                            margin-top: -1px;
                            transform: rotate(-45deg);
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box select.default-select {
                            -webkit-appearance: none;
                            -moz-appearance: none;
                            appearance: none;
                            line-height: 40px;
                            padding-top: 0;
                            padding-bottom: 0;
                            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'%3E%3Cpath fill='none' stroke='%2394A3B8' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M1 1l4 4 4-4'/%3E%3C/svg%3E");
                            background-repeat: no-repeat;
                            background-position: right 10px center;
                            background-size: 10px 6px;
                        }

                        html[dir="rtl"] .adt-hero-search-tabs .search-filters-bar .filter-box input,
                        html[dir="rtl"] .adt-hero-search-tabs .search-filters-bar .filter-box select.default-select {
                            padding: 0 8px 0 34px;
                        }

                        html[dir="rtl"] .adt-hero-search-tabs .search-filters-bar .filter-box select.default-select {
                            background-position: left 10px center;
                        }

                        html[dir="rtl"] .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container--default .select2-selection--single .select2-selection__rendered {
                            padding: 0 8px 0 34px;
                        }

                        html[dir="rtl"] .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar .select2-container--default .select2-selection--single .select2-selection__arrow {
                            right: auto;
                            left: 8px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-keyword-submit {
                            flex: 0.95 1 180px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__trigger {
                            width: 100%;
                            min-height: 40px;
                            padding: 0 8px;
                            display: flex;
                            align-items: center;
                            justify-content: space-between;
                            border: 0;
                            border-radius: 10px;
                            background: #f8fafc;
                            box-shadow: none;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__trigger:hover,
                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__trigger:focus-within {
                            background: #f8fafc;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__summary {
                            font-size: 13px;
                            color: #0f172a;
                            white-space: nowrap;
                            overflow: hidden;
                            text-overflow: ellipsis;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__trigger-icon {
                            width: 26px;
                            height: 26px;
                            background: transparent;
                            border: 0;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__panel {
                            width: min(840px, 92vw);
                        }

                        .adt-hero-search-tabs .search-filters-bar .bornado-keyword-submit__row {
                            display: flex;
                            align-items: center;
                            gap: 6px;
                        }

                        .adt-hero-search-tabs .search-filters-bar .bornado-keyword-submit__row input {
                            flex: 1 1 auto;
                            min-width: 0;
                        }

                        .adt-hero-search-tabs .search-filters-bar .search-button.bornado-inline-search-button {
                            width: 40px;
                            min-width: 40px;
                            height: 40px;
                            flex: 0 0 auto;
                            padding: 0;
                            border-radius: 10px;
                            background: #f3f4f6;
                            border: 1px solid #0f172a;
                            color: #0f172a;
                            box-shadow: none;
                            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
                        }

                        .adt-hero-search-tabs .search-filters-bar .search-button.bornado-inline-search-button i {
                            margin: 0;
                        }

                        .adt-hero-search-tabs .search-filters-bar .search-button.bornado-inline-search-button:hover,
                        .adt-hero-search-tabs .search-filters-bar .search-button.bornado-inline-search-button:focus {
                            background: #e5e7eb;
                            border-color: #0f172a;
                            color: #0f172a;
                        }

                        .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-location-filter .blp__panel {
                            position: fixed;
                            top: 92px;
                            left: 50% !important;
                            right: auto !important;
                            width: min(840px, calc(100vw - 32px));
                            max-width: none;
                            transform: translateX(-50%) !important;
                        }

                        .adt-top-tabs-header .buttons-box {
                            display: flex;
                            align-items: center;
                            gap: 4px;
                            flex: 0 0 auto;
                            white-space: nowrap;
                        }

                        .adt-top-tabs-header .buttons-box .sign-in,
                        .adt-top-tabs-header .buttons-box .sign-up {
                            padding: 8px 10px;
                            border: 0;
                            border-radius: 8px;
                            background: transparent;
                            color: #334155;
                            font-size: 12px;
                        }

                        .adt-top-tabs-header .buttons-box .sign-in.sign-in--with-register {
                            border-right: 1px solid #e7ebf0;
                        }

                        .adt-top-tabs-header .buttons-box .ad-post-btn {
                            margin-left: 0;
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            gap: 6px;
                            min-height: 40px;
                            padding: 0 16px;
                            border: 1px solid #0f172a;
                            border-radius: 11px;
                            background: #0f172a;
                            color: #ffffff;
                            font-size: 12px;
                            font-weight: 600;
                            line-height: 1;
                            box-shadow: none;
                            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
                        }

                        .adt-top-tabs-header .buttons-box .ad-post-btn:hover,
                        .adt-top-tabs-header .buttons-box .ad-post-btn:focus {
                            background: #1e293b;
                            border-color: #1e293b;
                            color: #ffffff;
                        }

                        html[dir="rtl"] .adt-top-tabs-header .buttons-box .adt-user-avatar ul.dropdown-user-login {
                            right: 0;
                            left: auto;
                        }

                        @media (min-width: 768px) and (max-width: 991.98px) {
                            .adt-top-tabs-header {
                                padding: 10px 0 12px;
                            }

                            .adt-top-tabs-header .tabs-container {
                                display: grid;
                                grid-template-columns: auto 1fr auto;
                                grid-template-areas:
                                    "logo spacer buttons"
                                    "search search search";
                                align-items: center;
                                row-gap: 12px;
                                column-gap: 16px;
                                direction: ltr;
                            }

                            .adt-top-tabs-header .tabs-container .logo {
                                grid-area: logo;
                                direction: rtl;
                            }

                            .adt-top-tabs-header .buttons-box {
                                grid-area: buttons;
                                justify-self: end;
                                margin: 0;
                                justify-content: flex-start;
                                width: auto;
                                flex-wrap: nowrap;
                                gap: 6px;
                                direction: rtl;
                            }

                            .adt-top-tabs-header .buttons-box .sign-in,
                            .adt-top-tabs-header .buttons-box .sign-up {
                                display: inline-flex !important;
                                align-items: center;
                                padding: 8px 8px;
                                font-size: 11px;
                            }

                            .adt-top-tabs-header .buttons-box .ad-post-btn {
                                min-height: 38px;
                                padding: 0 12px;
                                font-size: 11px;
                            }

                            .adt-top-tabs-header .tabs-wrapper {
                                grid-area: search;
                                width: 100%;
                                flex: 0 0 100%;
                                justify-content: flex-start;
                                gap: 10px;
                                flex-wrap: nowrap;
                                direction: rtl;
                            }

                            .adt-top-tabs-header .tabs-wrapper .nav.nav-pills,
                            .adt-top-tabs-header .tabs-wrapper .tab-content {
                                width: auto;
                            }

                            .adt-top-tabs-header .tabs-wrapper .nav.nav-pills {
                                flex: 0 0 auto;
                                max-width: 220px;
                                min-width: 180px;
                            }

                            .adt-top-tabs-header .tabs-wrapper .tab-content {
                                flex: 1 1 auto;
                                max-width: none;
                                min-width: 0;
                            }

                            .adt-top-tabs-header .adt-hero-search-tabs .search-filters-bar {
                                flex-wrap: nowrap;
                                padding: 5px 8px;
                                gap: 0;
                            }

                            .adt-hero-search-tabs .search-filters-bar .filter-box,
                            .adt-hero-search-tabs .search-filters-bar .filter-box.bornado-keyword-submit {
                                flex: 1 1 0;
                                flex-basis: auto;
                                padding: 0 8px;
                            }

                            .adt-hero-search-tabs .search-filters-bar .filter-box + .filter-box {
                                border-inline-start: 1px solid #eef2f7;
                                border-top: 0;
                                padding-top: 0;
                            }

                            .adt-hero-search-tabs .search-filters-bar .bornado-keyword-submit__row {
                                flex-wrap: nowrap;
                            }

                            .adt-hero-search-tabs .search-filters-bar .search-button.bornado-inline-search-button {
                                width: 40px;
                            }

                            .adt-top-tabs-header .buttons-box .sign-in,
                            .adt-top-tabs-header .buttons-box .adt-user-avatar {
                                order: 1;
                            }

                            .adt-top-tabs-header .buttons-box .ad-post-btn {
                                order: 2;
                            }

                            .adt-top-tabs-header .buttons-box .sign-up {
                                order: 3;
                                display: none !important;
                            }
                        }
                    </style>
                    <form action="<?php echo esc_url($safe_action_url); ?>" method="GET"
                          class="adt-hero-search-tabs"
                          data-default-action="<?php echo esc_url($safe_action_url); ?>"
                          data-all-cities-action="<?php echo esc_url($all_cities_target_url); ?>"
                          data-all-categories-action="<?php echo esc_url($all_categories_target_url); ?>"
                          data-all-filters-action="<?php echo esc_url($all_filters_target_url); ?>">
                        <?php $form_field_names = array(); ?>

                        <div class="search-filters-bar">
                            <?php foreach ($enabled_fields as $index => $field_type) :
                                $field_type = sanitize_key($field_type);
                                if (!in_array($field_type, array('keyword', 'ad_type', 'location', 'category'), true)) {
                                    continue;
                                }

                                $label_text = $field_display_labels[$field_type];
                                $field_id = 'header-search-field-' . $field_type . '-' . $index;
                                $wrapper_classes = 'filter-box filter-box--' . str_replace('_', '-', $field_type);

                                if (in_array($field_type, array('ad_type', 'location', 'category'), true)) {
                                    $wrapper_classes .= ' type-box';
                                }

                                switch ($field_type) {
                                    case 'keyword':
                                        $field_name = 'ad_title';
                                        $current_value = isset($_GET[$field_name]) ? sanitize_text_field(wp_unslash($_GET[$field_name])) : '';
                                        $form_field_names[] = $field_name;
                                        ?>
                                        <div class="<?php echo esc_attr($wrapper_classes); ?> bornado-keyword-submit">
                                            <label><?php echo esc_html($label_text); ?></label>
                                            <div class="bornado-keyword-submit__row">
                                                <input type="text"
                                                       id="<?php echo esc_attr($field_id); ?>"
                                                       name="<?php echo esc_attr($field_name); ?>"
                                                       data-search-role="title"
                                                       placeholder="<?php echo esc_attr($label_text); ?>"
                                                       value="<?php echo esc_attr($current_value); ?>">
                                                <button class="search-button bornado-inline-search-button" type="submit" aria-label="<?php echo esc_attr__('Search', 'adforest'); ?>">
                                                    <i class="fas fa-search" aria-hidden="true"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <?php
                                        break;

                                    case 'ad_type':
                                        $field_name = 'ad_type';
                                        $current_value = '';
                                        if (isset($_GET['ad_type'])) {
                                            $current_value = sanitize_text_field(wp_unslash($_GET['ad_type']));
                                        } elseif (isset($_GET['type'])) {
                                            $current_value = sanitize_text_field(wp_unslash($_GET['type']));
                                        }
                                        $form_field_names[] = $field_name;
                                        ?>
                                        <div class="<?php echo esc_attr($wrapper_classes); ?>">
                                            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label_text); ?></label>
                                            <select class="default-select"
                                                    id="<?php echo esc_attr($field_id); ?>"
                                                    name="<?php echo esc_attr($field_name); ?>"
                                                    data-search-role="ad_type">
                                                <option value=""><?php echo esc_html($label_text); ?></option>
                                                <?php
                                                if (is_array($ad_types)) :
                                                    foreach ($ad_types as $type_term) :
                                                        if (!is_object($type_term)) {
                                                            continue;
                                                        }
                                                        $selected = ($current_value !== '' && $current_value === $type_term->name) ? 'selected' : '';
                                                        ?>
                                                        <option value="<?php echo esc_attr($type_term->name); ?>" <?php echo esc_attr($selected); ?>>
                                                            <?php echo esc_html($type_term->name); ?>
                                                        </option>
                                                    <?php
                                                    endforeach;
                                                endif;
                                                ?>
                                            </select>
                                        </div>
                                        <?php
                                        break;

                                    case 'location':
                                        $field_name = 'country_id';
                                        $current_value = '';
                                        $current_value = $selected_location_value;
                                        $form_field_names[] = $field_name;
                                        ?>
                                        <div class="<?php echo esc_attr(trim($wrapper_classes . ' bornado-location-filter')); ?>">
                                            <label><?php echo esc_html($label_text); ?></label>
                                            <?php
                                            if (function_exists('bornado_render_location_picker')) {
                                                echo bornado_render_location_picker(
                                                    array(
                                                        'mode' => 'compact',
                                                        'class_name' => 'adt-header-location-picker',
                                                        'button_label' => $label_text,
                                                        'summary_fallback' => $label_text,
                                                        'submit_label' => esc_html__('اعمال', 'adforest'),
                                                        'reset_label' => esc_html__('همه کشورها', 'adforest'),
                                                        'panel_heading' => esc_html__('Select location', 'adforest'),
                                                        'country_heading' => esc_html__('کشورها', 'adforest'),
                                                        'city_heading' => esc_html__('شهرها', 'adforest'),
                                                        'search_label' => esc_html__('جستجو در کشورها', 'adforest'),
                                                        'city_label' => esc_html__('جستجو در شهرها', 'adforest'),
                                                        'external_form_selector' => '.adt-hero-search-tabs',
                                                        'render_hidden_input' => true,
                                                        'submit_on_apply' => true,
                                                        'input_name' => $field_name,
                                                        'input_id' => $field_id,
                                                        'input_data_role' => 'country',
                                                    )
                                                ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            } else {
                                                ?>
                                                <input type="hidden"
                                                       id="<?php echo esc_attr($field_id); ?>"
                                                       name="<?php echo esc_attr($field_name); ?>"
                                                       value="<?php echo esc_attr($current_value); ?>"
                                                       data-search-role="country">
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                        break;

                                    case 'category':
                                        $field_name = 'cat_id';
                                        $current_value = $selected_category_value;
                                        $form_field_names[] = $field_name;
                                        ?>
                                        <div class="<?php echo esc_attr($wrapper_classes); ?>">
                                            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label_text); ?></label>
                                            <select class="default-select"
                                                    id="<?php echo esc_attr($field_id); ?>"
                                                    name="<?php echo esc_attr($field_name); ?>"
                                                    data-search-role="category">
                                                <option value=""><?php echo esc_html($label_text); ?></option>
                                                <?php
                                    if (!empty($category_terms)) :
                                        foreach ($category_terms as $entry) :
                                            $category_term = $entry['term'];
                                            if (!is_object($category_term)) {
                                                continue;
                                            }
                                            $option_label = $entry['label'];
                                            $selected = ($current_value && $current_value === (int) $category_term->term_id) ? 'selected' : '';
                                            $option_target_url = function_exists('bornado_seo_routing_get_contextual_url')
                                                ? bornado_seo_routing_get_contextual_url(array('cat_id' => (int) $category_term->term_id))
                                                : '';
                                            ?>
                                            <option value="<?php echo esc_attr($category_term->term_id); ?>" data-target-url="<?php echo esc_url($option_target_url); ?>" <?php echo esc_attr($selected); ?>>
                                                <?php echo esc_html($option_label); ?>
                                            </option>
                                        <?php
                                        endforeach;
                                    endif;
                                    ?>
                                            </select>
                                        </div>
                                        <?php
                                        break;
                                }
                            endforeach; ?>
                        </div>
                        <?php
                        $excluded_params = array_unique(array_merge(
                            $form_field_names,
                            array('type', 'title', 'paged', 'ad_country', 'location')
                        ));

                        if (!$has_category_field && $selected_category_value > 0) {
                            adforest_header_render_hidden_fields('cat_id', $selected_category_value);
                            $excluded_params[] = 'cat_id';
                        }

                        foreach (adforest_header_get_clean_hidden_query_args($excluded_params) as $param_key => $param_value) {
                            if (in_array($param_key, $excluded_params, true)) {
                                continue;
                            }
                            adforest_header_render_hidden_fields($param_key, $param_value);
                        }
                        ?>
                    </form>
                </div>
            </div>

            <script>
                function setCategory(catId) {
                    const sharedSearchCore = window.BornadoSearchCore || null;
                    const urlParams = sharedSearchCore && typeof sharedSearchCore.getCleanCurrentSearchParams === "function"
                        ? sharedSearchCore.getCleanCurrentSearchParams()
                        : new URLSearchParams(window.location.search);
                    const targetButton = document.querySelector('#pills-tab [onclick="setCategory(' + String(catId) + ')"]');
n;
                    }

                    function submitSearchFormState() {
                        let selectedAction = searchForm.getAttribute("data-default-action") || searchForm.getAttribute("action");

                        const titleInput = searchForm.querySelector('[data-search-role="title"]');
                        if (titleInput) {
                            if (searchCore && typeof searchCore.toggleFieldName === "function") {
                                searchCore.toggleFieldName(titleInput, "ad_title");
                            } else {
                                const titleValue = titleInput.value ? titleInput.value.trim() : "";
                                if (titleValue === "") {
                                    titleInput.removeAttribute("name");
                                } else {
                                    titleInput.setAttribute("name", "ad_title");
                                }
                            }
                        }

                        const cityInput = searchForm.querySelector('[data-search-role="country"]');
                        if (cityInput) {
                            const cityValue = cityInput.value ? cityInput.value.trim() : "";
                            if (cityValue === "") {
                                const allCitiesAction = searchForm.getAttribute("data-all-cities-action");
                                if (allCitiesAction) {
                                    selectedAction = allCitiesAction;
                                }
                                if (searchCore && typeof searchCore.toggleFieldName === "function") {
                                    searchCore.toggleFieldName(cityInput, "country_id");
                                } else {
                                    cityInput.removeAttribute("name");
                                }
                            } else {
                                cityInput.setAttribute("name", "country_id");
                            }
                        }

                        const categoryInput = searchForm.querySelector('[data-search-role="category"]');
                        if (categoryInput) {
                            const categoryValue = categoryInput.value ? categoryInput.value.trim() : "";
                            if (categoryValue === "") {
                                const allCategoriesAction = searchForm.getAttribute("data-all-categories-action");
                                if (allCategoriesAction) {
                                    selectedAction = allCategoriesAction;
                                }
                                if (searchCore && typeof searchCore.toggleFieldName === "function") {
                                    searchCore.toggleFieldName(categoryInput, "cat_id");
                                } else {
                                    categoryInput.removeAttribute("name");
                                }
                            } else {
                                categoryInput.setAttribute("name", "cat_id");
                            }
                        }

                        if (
                            cityInput &&
                            categoryInput &&
                            (!cityInput.value || cityInput.value.trim() === "") &&
                            (!categoryInput.value || categoryInput.value.trim() === "")
                        ) {
                            const allFiltersAction = searchForm.getAttribute("data-all-filters-action");
                            if (allFiltersAction) {
                                selectedAction = allFiltersAction;
                            }
                        }

                        if (searchCore && typeof searchCore.navigateWithForm === "function") {
                            searchCore.navigateWithForm(searchForm, selectedAction || window.location.href);
                            return false;
                        }

                        const formData = new FormData(searchForm);
                        const searchParams = new URLSearchParams();
                        formData.forEach(function (value, key) {
                            const safeKey = key ? String(key).trim() : "";
                            const safeValue = value == null ? "" : String(value).trim();
                            if (safeKey !== "" && safeValue !== "") {
                                searchParams.append(safeKey, safeValue);
                            }
                        });

                        const targetUrl = new URL(selectedAction || window.location.href, window.location.origin);
                        targetUrl.search = searchParams.toString();
                        window.location.href = targetUrl.toString();
                        return false;
                    }

                    searchForm.addEventListener("submit", function (event) {
                        event.preventDefault();
                        submitSearchFormState();
                    });

                    ["[data-search-role=\"country\"]", "[data-search-role=\"category\"]"].forEach(function (selector) {
                        const field = searchForm.querySelector(selector);
                        if (!field) {
                            return;
                        }
                        field.addEventListener("change", function () {
                            submitSearchFormState();
                        });
                    });

                    if (window.jQuery) {
                        window.jQuery(searchForm).on("select2:select", "[data-search-role=\"country\"], [data-search-role=\"category\"]", function () {
                            submitSearchFormState();
                        });
                    }
                });
            </script>
            <div class="buttons-box">
                <?php if (!is_user_logged_in()) { ?>
                    <?php if ($show_sign_in) : ?>
                        <a href="<?php echo esc_url($sign_in_url); ?>" <?php echo function_exists('bornado_auth_modal_trigger_attrs') ? bornado_auth_modal_trigger_attrs('login', 'phone') : ''; ?> class="sign-in<?php echo $show_sign_up ? ' sign-in--with-register' : ''; ?>"><i
                                    class="fas fa-sign-in-alt"></i><?php echo esc_html__("Sign in", "adforest"); ?></a>
                    <?php endif; ?>
                    <?php if ($show_sign_up) : ?>
                        <a href="<?php echo esc_url($sign_up_url); ?>" <?php echo function_exists('bornado_auth_modal_trigger_attrs') ? bornado_auth_modal_trigger_attrs('register', 'phone') : ''; ?> class="sign-up"><i
                                    class="fas fa-sign-in-alt"></i><?php echo esc_html__("Register", "adforest"); ?></a>
                    <?php endif; ?>
                <?php } else { ?>
                    <div class="adt-user-avatar">
                        <a href="javascript:void(0)" data-bs-toggle="tooltip" data-bs-placement="top"
                           class="login-user">
                            <?php
                            $dp = '';
                            $unread_msgs = ADFOREST_MESSAGE_COUNT;
                            if (function_exists('adforest_get_user_dp')) {
                                $dp = adforest_get_user_dp($user_id);
                            }
                            ?>
                            <img class="img-circle" src="<?php echo esc_url($dp); ?>"
                                 alt="<?php esc_html__('user prfile picture', 'adforest'); ?>" width="32" height="32"></a>

                        <ul class="dropdown-user-login">
                            <li><a class="user_login_dropdown_text"
                                   href="<?php echo get_the_permalink($sb_profile_page); ?>"><i
                                            class="fa fa-user"></i> <?php echo esc_html__("Profile", "adforest"); ?></a></li>
                            <?php echo apply_filters('adforest_vendor_dashboard_profile', '', $user_id); ?>
                            <?php
                            if (isset($adforest_theme['communication_mode']) && ($adforest_theme['communication_mode'] == 'both' || $adforest_theme['communication_mode'] == 'message')) {
                                ?>
                                <li><a class="user_login_dropdown_text  "
                                       href="<?php echo adforest_set_url_param(trailingslashit(get_the_permalink($sb_profile_page)), 'page_type', 'msg'); ?>"><i
                                                class="fa fa-envelope"></i> <?php echo esc_html__('Messages', 'adforest'); ?>
                                        <span
                                                class="badge bg-danger"><?php echo esc_html(adforest_get_message_count()); ?></span></a>
                                </li>
                                <?php
                            }
                            if (isset($adforest_theme['sb_cart_in_menu']) && $adforest_theme['sb_cart_in_menu']) {
                                global $woocommerce;
                                ?>
                                <li><a class="user_login_dropdown_text" href="<?php echo wc_get_cart_url(); ?>"><i
                                                class="fa fa-shopping-cart"></i> <?php echo esc_html__('Cart', 'adforest'); ?>
                                        <span
                                                class="badge bg-danger"><?php echo adforest_return_echo($woocommerce->cart->cart_contents_count); ?></span></a>
                                </li> <?php } ?>

                            <li><a class="user_login_dropdown_text"
                                   href="<?php echo wp_logout_url(get_the_permalink($sb_sign_in_page)); ?>"><i
                                            class="fa fa-power-off"></i> <?php echo esc_html__("Logout", "adforest"); ?></a>
                            </li>
                        </ul>
                    </div>

                <?php } ?>
                <?php if ( isset($adforest_theme['ad_in_menu']) && $adforest_theme['ad_in_menu'] ) { ?>
                    <a href="<?php echo get_the_permalink($sb_post_ad_page); ?>"
                       class="btn-theme-secondary ad-post-btn"><i
                                class="fas fa-plus"></i><?php echo esc_html($ad_in_menu_text) ?></a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

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
$responsive_logo = isset($adforest_theme['sb_site_logo_mobile']['url']) ? $adforest_theme['sb_site_logo_mobile']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$home_page_logo = isset($adforest_theme['sb_home_logo']['url']) ? $adforest_theme['sb_home_logo']['url'] : ADFOREST_IMAGE_PATH . "/adt-logo.png";
$user_id = get_current_user_id();

$is_sticky_header = isset($adforest_theme['sb_sticky_header']) ? $adforest_theme['sb_sticky_header'] : '';
$sticky_class = "";
if ($is_sticky_header == '1') {
    $sticky_class = "";
}

$sb_profile_page = isset($adforest_theme['sb_profile_page']) ? $adforest_theme['sb_profile_page'] : '';

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

$location_terms = $needs_locations ? adforest_header_get_hierarchical_terms('ad_country') : array();
$category_terms = $needs_categories_dropdown ? adforest_header_get_hierarchical_terms('ad_cats') : array();

if (!function_exists('adforest_header_render_hidden_fields')) {
    function adforest_header_render_hidden_fields($name, $value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                adforest_header_render_hidden_fields($name . '[' . $key . ']', $val);
            }
        } else {
            echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">' . "\n";
        }
    }
}
?>

<script>
    document.addEventListener("DOMContentLoaded", function () {
        const params = new URLSearchParams(window.location.search);

        const defaultCatId = <?php echo json_encode( (int) $default_cat_id ); ?>;
        const defaultSlug = <?php echo json_encode( sanitize_title( $default_slug ) ); ?>;
        if (!params.has('cat_id') && defaultCatId && defaultSlug) {
            params.set('cat_id', defaultCatId);
            const newUrl = `${window.location.pathname}?${params.toString()}`;
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
                <a href="<?php echo esc_url(home_url('/')); ?>"><img src="<?php echo esc_url($site_logo); ?>"
                                                            alt="<?php echo esc_attr__('logo', 'adforest') ?>"></a>
            </div>
            <div class="tabs-wrapper">
                <?php if (!empty($topbar_cats)) : ?>
                    <ol class="nav nav-pills" id="pills-tab" role="tablist">
                        <?php foreach ($topbar_cats as $cat_id) :
                            $taxonomy = get_term($cat_id, 'ad_cats');

                            if (!is_wp_error($taxonomy) && $taxonomy) :
                                $taxonomy_image = get_option('adforest_taxonomy_image' . $taxonomy->term_id);
                                $current_cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
                                $is_active = ($current_cat_id == $cat_id) ? 'active' : '';
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?php echo esc_attr($is_active); ?>"
                                            id="pills-<?php echo esc_attr($taxonomy->slug); ?>-tab"
                                            data-bs-toggle="pill"
                                            data-bs-target="#pills-<?php echo esc_attr($taxonomy->slug); ?>"
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
                    // Get the search results page URL from theme settings
                    $sb_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
                    $safe_action_url = isset($sb_search_page) && $sb_search_page != '' ? get_the_permalink($sb_search_page) : home_url('/');
                    $safe_action_url = apply_filters('adforest_category_widget_form_action', $safe_action_url);
                    ?>
                    <form action="<?php echo esc_url($safe_action_url); ?>" method="GET"
                          class="adt-hero-search-tabs">
                        <?php $form_field_names = array(); ?>

                        <div class="search-filters-bar">
                            <?php foreach ($enabled_fields as $index => $field_type) :
                                $field_type = sanitize_key($field_type);
                                if (!in_array($field_type, array('keyword', 'ad_type', 'location', 'category'), true)) {
                                    continue;
                                }

                                $label_text = $field_labels[$field_type];
                                $placeholder_text = $field_placeholders[$field_type];
                                $field_id = 'header-search-field-' . $field_type . '-' . $index;
                                $wrapper_classes = 'filter-box';

                                if (in_array($field_type, array('ad_type', 'location', 'category'), true)) {
                                    $wrapper_classes .= ' type-box';
                                }

                                switch ($field_type) {
                                    case 'keyword':
                                        $field_name = 'ad_title';
                                        $current_value = isset($_GET[$field_name]) ? sanitize_text_field(wp_unslash($_GET[$field_name])) : '';
                                        $form_field_names[] = $field_name;
                                        ?>
                                        <div class="<?php echo esc_attr($wrapper_classes); ?>">
                                            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label_text); ?></label>
                                            <input type="text"
                                                   id="<?php echo esc_attr($field_id); ?>"
                                                   name="<?php echo esc_attr($field_name); ?>"
                                                   placeholder="<?php echo esc_attr($placeholder_text); ?>"
                                                   value="<?php echo esc_attr($current_value); ?>">
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
                                                    name="<?php echo esc_attr($field_name); ?>">
                                                <option value=""><?php echo esc_html($placeholder_text); ?></option>
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
                                        $field_name = 'location';
                                        $current_value = isset($_GET[$field_name]) ? sanitize_text_field(wp_unslash($_GET[$field_name])) : '';
                                        $form_field_names[] = $field_name;
                                        ?>
                                        <div class="<?php echo esc_attr($wrapper_classes); ?>">
                                            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label_text); ?></label>
                                            <select class="default-select"
                                                    id="<?php echo esc_attr($field_id); ?>"
                                                    name="<?php echo esc_attr($field_name); ?>">
                                                <option value=""><?php echo esc_html($placeholder_text); ?></option>
                                                <?php
                                    if (!empty($location_terms)) :
                                        foreach ($location_terms as $entry) :
                                            $location_term = $entry['term'];
                                            if (!is_object($location_term)) {
                                                continue;
                                            }
                                            $option_label = $entry['label'];
                                            $selected = ($current_value !== '' && $current_value === $location_term->name) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo esc_attr($location_term->name); ?>" <?php echo esc_attr($selected); ?>>
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

                                    case 'category':
                                        $field_name = 'cat_id';
                                        $current_value = isset($_GET[$field_name]) ? intval($_GET[$field_name]) : 0;
                                        $form_field_names[] = $field_name;
                                        ?>
                                        <div class="<?php echo esc_attr($wrapper_classes); ?>">
                                            <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label_text); ?></label>
                                            <select class="default-select"
                                                    id="<?php echo esc_attr($field_id); ?>"
                                                    name="<?php echo esc_attr($field_name); ?>">
                                                <option value=""><?php echo esc_html($placeholder_text); ?></option>
                                                <?php
                                    if (!empty($category_terms)) :
                                        foreach ($category_terms as $entry) :
                                            $category_term = $entry['term'];
                                            if (!is_object($category_term)) {
                                                continue;
                                            }
                                            $option_label = $entry['label'];
                                            $selected = ($current_value && $current_value === (int) $category_term->term_id) ? 'selected' : '';
                                            ?>
                                            <option value="<?php echo esc_attr($category_term->term_id); ?>" <?php echo esc_attr($selected); ?>>
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
                            <button class="search-button" type="submit">
                                <i class="fas fa-search"></i><?php echo esc_html__("Search", "adforest"); ?>
                            </button>
                        </div>
                        <?php
                        $excluded_params = array_unique(array_merge(
                            $form_field_names,
                            array('type', 'title', 'paged')
                        ));

                        if (!$has_category_field && isset($_GET['cat_id'])) {
                            adforest_header_render_hidden_fields('cat_id', intval($_GET['cat_id']));
                            $excluded_params[] = 'cat_id';
                        }

                        foreach ($_GET as $param_key => $param_value) {
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
                    const searchPageUrl = '<?php echo esc_url($safe_action_url); ?>';
                    const urlParams = new URLSearchParams(window.location.search);

                    urlParams.set('cat_id', catId);

                    // Redirect to the search page with the category parameter
                    const newUrl = searchPageUrl + '?' + urlParams.toString();

                    window.location.href = newUrl;
                }
            </script>
            <div class="buttons-box">
                <?php
                /* Delegate to the shared user-menu helper so the
                   Theme Options → "Modern User Menu" toggle
                   (`sb_header_user_menu_style`) is respected here
                   too. Previously this header rendered a hard-coded
                   sign-in/avatar block that ignored the option, so
                   enabling the modern menu in Theme Options had no
                   effect when "Header Search" was selected.
                   The helper returns:
                     - logged-out → Sign in / Register links
                     - logged-in, classic mode → legacy avatar +
                       classic dropdown (matches the markup this file
                       used to emit inline)
                     - logged-in, modern mode → avatar + Listivo-
                       style dropdown (Add Listing / Awaiting
                       Approval / Invoices / Messages / My Listings /
                       Favorites / My Packages / Profile Settings /
                       Log Out), with its own inline CSS. */
                if (function_exists('adforest_get_header_user_menu_markup')) {
                    echo adforest_get_header_user_menu_markup(array(
                        'sign_in_page' => $sb_sign_in_page,
                        'sign_up_page' => $sb_sign_up_page,
                        'profile_page' => $sb_profile_page,
                        'user_id'      => $user_id,
                    ));
                }
                ?>
                <?php if ( isset($adforest_theme['ad_in_menu']) && $adforest_theme['ad_in_menu'] ) { ?>
                    <a href="<?php echo get_the_permalink($sb_post_ad_page); ?>"
                       class="btn-theme-secondary ad-post-btn"><i
                                class="fas fa-plus"></i><?php echo esc_html($ad_in_menu_text) ?></a>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

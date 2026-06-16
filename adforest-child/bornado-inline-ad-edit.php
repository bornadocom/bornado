<?php
/**
 * Bornado — Inline Ad Edit.
 *
 * Turns the existing single-ad page (`style-bornad-style.php`) into an in-place
 * editor when the owner (or an administrator) opens it with `?bornado_edit=1`.
 *
 * Design contract (important):
 *  - ZERO changes to AdForest core (theme `adforest/` or `plugins/`). Everything
 *    lives in the child theme.
 *  - The real AdForest post-ad form (`ad_post_short_base_func`) is rendered in
 *    edit mode (it pre-fills every value and owns the proven save pipeline at
 *    AJAX action `sb_ad_posting`). We never re-implement the save contract.
 *  - The editor visually mirrors the single-ad layout. A small JS layer RELOCATES
 *    the real form controls into single-ad-styled slots (no value duplication,
 *    so there is no sync drift), and "Save" just triggers the real form submit.
 *  - Strictly gated: it only ever activates for the ad owner / admins on a
 *    singular `ad_post`. For everyone else nothing changes anywhere.
 *
 * @package Bornado_Child
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('BORNADO_INLINE_EDIT_PARAM')) {
    /**
     * Query argument that switches a single-ad page into edit mode.
     */
    define('BORNADO_INLINE_EDIT_PARAM', 'bornado_edit');
}

if (!function_exists('bornado_inline_edit_current_ad_id')) {
    /**
     * Resolve the ad ID for the current single-ad request, if any.
     *
     * @return int Ad post ID, or 0 when this is not a singular ad_post view.
     */
    function bornado_inline_edit_current_ad_id()
    {
        if (is_admin() || !is_singular('ad_post')) {
            return 0;
        }

        $ad_id = (int) get_queried_object_id();

        return ('ad_post' === get_post_type($ad_id)) ? $ad_id : 0;
    }
}

if (!function_exists('bornado_inline_edit_user_can_edit')) {
    /**
     * Whether the current user may edit the given ad.
     *
     * @param int $ad_id Ad post ID.
     * @return bool
     */
    function bornado_inline_edit_user_can_edit($ad_id)
    {
        $ad_id = (int) $ad_id;
        if ($ad_id < 1 || !is_user_logged_in()) {
            return false;
        }

        // Mirror the AdForest shortcode's own gate exactly (author OR super
        // admin). A wider allowance would let the shortcode redirect-and-exit
        // on render for users it does not consider owners.
        if (is_super_admin()) {
            return true;
        }

        return (int) get_post_field('post_author', $ad_id) === (int) get_current_user_id();
    }
}

if (!function_exists('bornado_inline_edit_is_active')) {
    /**
     * Whether the current request should render the inline editor.
     *
     * Result is memoized: this is consulted by both the enqueue layer and the
     * single-ad template branch within the same request.
     *
     * @return bool
     */
    function bornado_inline_edit_is_active()
    {
        static $active = null;
        if (null !== $active) {
            return $active;
        }

        $active = false;

        if (empty($_GET[BORNADO_INLINE_EDIT_PARAM]) || '1' !== (string) $_GET[BORNADO_INLINE_EDIT_PARAM]) {
            return $active;
        }

        $ad_id = bornado_inline_edit_current_ad_id();
        if ($ad_id < 1) {
            return $active;
        }

        $active = bornado_inline_edit_user_can_edit($ad_id);

        return $active;
    }
}

if (!function_exists('bornado_inline_edit_get_url')) {
    /**
     * Build the inline-edit URL for a given ad (its permalink + edit flag).
     *
     * @param int         $ad_id        Ad post ID.
     * @param string|null $fallback_url Optional fallback when the ad is invalid.
     * @return string
     */
    function bornado_inline_edit_get_url($ad_id, $fallback_url = '')
    {
        $ad_id = (int) $ad_id;
        if ($ad_id < 1 || 'ad_post' !== get_post_type($ad_id)) {
            return is_string($fallback_url) ? $fallback_url : '';
        }

        $permalink = get_permalink($ad_id);
        if (!is_string($permalink) || '' === $permalink) {
            return is_string($fallback_url) ? $fallback_url : '';
        }

        return add_query_arg(BORNADO_INLINE_EDIT_PARAM, '1', $permalink);
    }
}

if (!function_exists('bornado_inline_edit_view_url')) {
    /**
     * The plain (non-edit) permalink used by the editor's "view / cancel" action.
     *
     * @param int $ad_id Ad post ID.
     * @return string
     */
    function bornado_inline_edit_view_url($ad_id)
    {
        $permalink = get_permalink((int) $ad_id);

        return is_string($permalink) ? remove_query_arg(BORNADO_INLINE_EDIT_PARAM, $permalink) : '';
    }
}

if (!function_exists('bornado_inline_edit_rewrite_edit_links')) {
    /**
     * Route every AdForest "edit ad" link onto the inline editor.
     *
     * AdForest (and Bornado's earlier modern-page rewrite) build edit links via
     * `adforest_set_url_param(..., 'id', $ad_id)`, which runs through the
     * `adforest_page_lang_url` filter. When the `id` belongs to an `ad_post`
     * owned-edit context, send it to the single-ad page in edit mode instead.
     *
     * Priority 100 so this runs after the legacy modern-page rewrite (99) and
     * has the final say, without removing that filter.
     *
     * @param string $url Candidate URL.
     * @return string
     */
    function bornado_inline_edit_rewrite_edit_links($url)
    {
        if (!is_string($url) || '' === trim($url)) {
            return $url;
        }

        $parsed = wp_parse_url($url);
        if (!is_array($parsed) || empty($parsed['query'])) {
            return $url;
        }

        $query_args = array();
        parse_str((string) $parsed['query'], $query_args);

        if (!isset($query_args['id'])) {
            return $url;
        }

        $ad_id = absint($query_args['id']);
        if ($ad_id < 1 || 'ad_post' !== get_post_type($ad_id)) {
            return $url;
        }

        $rewritten = bornado_inline_edit_get_url($ad_id, '');
        if ('' === $rewritten) {
            return $url;
        }

        if (!empty($parsed['fragment'])) {
            $rewritten .= '#' . ltrim((string) $parsed['fragment'], '#');
        }

        return $rewritten;
    }

    add_filter('adforest_page_lang_url', 'bornado_inline_edit_rewrite_edit_links', 100);
}

if (!function_exists('bornado_inline_edit_register_query_var')) {
    /**
     * Register the edit flag as a public query var.
     *
     * Without this, WordPress treats `?bornado_edit=1` as an unknown argument
     * and `redirect_canonical()` strips it — bouncing the owner straight back to
     * the read-only permalink, which is exactly the "edit just shows the ad"
     * symptom. Registering it keeps the flag on the URL.
     *
     * @param array $vars Recognised query vars.
     * @return array
     */
    function bornado_inline_edit_register_query_var($vars)
    {
        if (is_array($vars) && !in_array(BORNADO_INLINE_EDIT_PARAM, $vars, true)) {
            $vars[] = BORNADO_INLINE_EDIT_PARAM;
        }

        return $vars;
    }

    add_filter('query_vars', 'bornado_inline_edit_register_query_var');
}

if (!function_exists('bornado_inline_edit_disable_canonical_redirect')) {
    /**
     * Never canonical-redirect away from an active inline-edit request.
     *
     * Belt-and-suspenders next to the query-var registration: while the editor
     * is active we keep the URL exactly as requested (with the edit flag) so the
     * single-ad template can switch into edit mode.
     *
     * @param string $redirect_url  Proposed canonical URL.
     * @param string $requested_url Originally requested URL.
     * @return string|false
     */
    function bornado_inline_edit_disable_canonical_redirect($redirect_url, $requested_url)
    {
        if (bornado_inline_edit_is_active()) {
            return false;
        }

        return $redirect_url;
    }

    add_filter('redirect_canonical', 'bornado_inline_edit_disable_canonical_redirect', 10, 2);
}

if (!function_exists('bornado_inline_edit_get_deepest_category_context')) {
    /**
     * Resolve the deepest selected ad category for the current ad.
     *
     * Used to prewarm AdForest's category-template AJAX on initial inline-edit
     * load so the hidden form already contains category-driven controls before
     * the slower frontend initialization catches up.
     *
     * @param int $ad_id Ad post ID.
     * @return array<string, scalar>
     */
    function bornado_inline_edit_get_deepest_category_context($ad_id)
    {
        $ad_id = (int) $ad_id;
        if ($ad_id < 1) {
            return array('id' => 0, 'name' => '');
        }

        $terms = wp_get_post_terms($ad_id, 'ad_cats');
        if (is_wp_error($terms) || empty($terms) || !is_array($terms)) {
            return array('id' => 0, 'name' => '');
        }

        usort($terms, static function ($left, $right) {
            $left_depth = count(get_ancestors((int) $left->term_id, 'ad_cats', 'taxonomy'));
            $right_depth = count(get_ancestors((int) $right->term_id, 'ad_cats', 'taxonomy'));
            if ($left_depth === $right_depth) {
                return 0;
            }
            return ($left_depth < $right_depth) ? 1 : -1;
        });

        $deepest = $terms[0];
        return array(
            'id'   => isset($deepest->term_id) ? (int) $deepest->term_id : 0,
            'name' => isset($deepest->name) ? (string) $deepest->name : '',
        );
    }
}

if (!function_exists('bornado_inline_edit_suspend_hard_canonical_redirects')) {
    /**
     * Keep the edit flag on the URL by suspending the site's hard 301 canonical
     * redirects for an active inline-edit request only.
     *
     * Bornado's ad-permalinks (and routing) plugins enforce a clean canonical
     * single-ad URL via `wp_safe_redirect(); exit;` on `template_redirect`.
     * Because that is a hard redirect (not the `redirect_canonical` filter), the
     * `?bornado_edit=1` flag is stripped before our template ever runs — which
     * is exactly the "edit just bounces back to the ad" symptom. We run earlier
     * (negative priority) and, ONLY for an authorised edit request, unhook those
     * specific redirects. Nothing is changed for any other visitor or request.
     *
     * @return void
     */
    function bornado_inline_edit_suspend_hard_canonical_redirects()
    {
        if (!bornado_inline_edit_is_active()) {
            return;
        }

        if (class_exists('Bornado_Ad_Permalinks')) {
            remove_action(
                'template_redirect',
                array('Bornado_Ad_Permalinks', 'maybe_redirect_to_canonical'),
                0
            );
        }

        if (class_exists('Bornado_SEO_Routing')) {
            remove_action(
                'template_redirect',
                array('Bornado_SEO_Routing', 'maybe_redirect_noncanonical_request'),
                1
            );
        }
    }

    // Priority -100 so this runs before the plugins' own template_redirect
    // hooks (which sit at 0 and 1).
    add_action('template_redirect', 'bornado_inline_edit_suspend_hard_canonical_redirects', -100);
}

if (!function_exists('bornado_inline_edit_enqueue_assets')) {
    /**
     * Load the editor stylesheet + script only on an active inline-edit request.
     *
     * The heavy lifting (form rendering, validation, image upload, category
     * cascades, maps) is handled by AdForest's own globally-enqueued front-end
     * scripts plus the shortcode's render-time enqueues. We only add the thin
     * relocation/UX layer here.
     */
    function bornado_inline_edit_enqueue_assets()
    {
        if (!bornado_inline_edit_is_active()) {
            return;
        }

        $ad_id   = bornado_inline_edit_current_ad_id();
        $debug_build = 'inline-edit-build-20260614-1058';
        $css_rel = '/assets/css/bornado-inline-ad-edit.css';
        $mobile_choice_css_rel = '/assets/css/bornado-mobile-choice-ui.css';
        $js_rel  = '/assets/js/bornado-inline-ad-edit.js';
        $probe_js_rel = '/assets/js/bornado-inline-ad-edit-probe.js';
        $css_abs = get_stylesheet_directory() . $css_rel;
        $mobile_choice_css_abs = get_stylesheet_directory() . $mobile_choice_css_rel;
        $js_abs  = get_stylesheet_directory() . $js_rel;
        $probe_js_abs = get_stylesheet_directory() . $probe_js_rel;

        if (file_exists($mobile_choice_css_abs)) {
            wp_enqueue_style(
                'bornado-mobile-choice-ui',
                get_stylesheet_directory_uri() . $mobile_choice_css_rel,
                array(),
                (string) filemtime($mobile_choice_css_abs)
            );
        }

        if (file_exists($css_abs)) {
            wp_enqueue_style(
                'bornado-inline-ad-edit',
                get_stylesheet_directory_uri() . $css_rel,
                file_exists($mobile_choice_css_abs) ? array('bornado-mobile-choice-ui') : array(),
                (string) filemtime($css_abs)
            );
        }

        if (function_exists('bornado_wheel_picker_enqueue_assets')) {
            bornado_wheel_picker_enqueue_assets();
        }

        if (file_exists($js_abs)) {
            $prewarm_category = bornado_inline_edit_get_deepest_category_context($ad_id);
            wp_enqueue_script(
                'bornado-inline-ad-edit',
                get_stylesheet_directory_uri() . $js_rel,
                array('jquery'),
                (string) filemtime($js_abs),
                true
            );

            wp_localize_script(
                'bornado-inline-ad-edit',
                'bornadoInlineEdit',
                array(
                    'adId'            => $ad_id,
                    'ajaxUrl'         => admin_url('admin-ajax.php'),
                    'viewUrl'         => bornado_inline_edit_view_url($ad_id),
                    'debugBuild'      => $debug_build,
                    'syncImagesNonce' => wp_create_nonce('bornado_sync_ad_images'),
                    'prewarmCategory' => $prewarm_category,
                    'i18n'    => array(
                        'save'         => __('ذخیره تغییرات', 'adforest'),
                        'saving'       => __('در حال ذخیره…', 'adforest'),
                        'cancel'       => __('انصراف', 'adforest'),
                        'editing'      => __('حالت ویرایش', 'adforest'),
                        'edit'         => __('ویرایش', 'adforest'),
                        'done'         => __('تمام', 'adforest'),
                        'addField'     => __('افزودن', 'adforest'),
                        'addPrice'     => __('افزودن قیمت', 'adforest'),
                        'addTagline'   => __('افزودن زیرعنوان', 'adforest'),
                        'addDetails'   => __('افزودن مشخصات', 'adforest'),
                        'selectDate'   => __('انتخاب تاریخ', 'adforest'),
                        'pricePlaceholder' => __('قیمت را وارد کنید', 'adforest'),
                        'priceFromPlaceholder' => __('از', 'adforest'),
                        'priceToPlaceholder' => __('تا', 'adforest'),
                        'emptyValue'   => __('— تکمیل نشده —', 'adforest'),
                        'unsavedLeave' => __('تغییرات ذخیره‌نشده دارید. از این صفحه خارج می‌شوید؟', 'adforest'),
                        'selectOption' => __('Select Option', 'adforest'),
                        'selectCountryFirst' => __('ابتدا کشور را انتخاب کنید', 'adforest'),
                        'loadingOptions' => __('در حال بارگذاری…', 'adforest'),
                        'noCityOptions'  => __('شهری برای این کشور یافت نشد', 'adforest'),
                    ),
                )
            );

            wp_add_inline_script(
                'bornado-inline-ad-edit',
                '(function(){' .
                    'if(typeof window==="undefined"||typeof document==="undefined"){return;}' .
                    'var run=function(){' .
                        'if(typeof bornadoInlineEdit==="undefined"||!bornadoInlineEdit.ajaxUrl||!window.fetch){return;}' .
                        'var fd=new FormData();' .
                        'fd.append("action","bornado_inline_edit_debug_ping");' .
                        'fd.append("ad_id",String(bornadoInlineEdit.adId||0));' .
                        'fd.append("event_name","inline_php_boot_js");' .
                        'fd.append("payload",JSON.stringify({' .
                            'build:bornadoInlineEdit.debugBuild||"",' .
                            'href:window.location.href,' .
                            'readyState:document.readyState' .
                        '}));' .
                        'fetch(bornadoInlineEdit.ajaxUrl,{method:"POST",body:fd,credentials:"same-origin"}).catch(function(){});' .
                    '};' .
                    'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",run);}else{run();}' .
                '})();',
                'after'
            );

            wp_add_inline_script(
                'bornado-inline-ad-edit',
                '(function(){' .
                    'if(typeof window==="undefined"||typeof document==="undefined"){return;}' .
                    'var run=function(){' .
                        'if(typeof bornadoInlineEdit==="undefined"||!bornadoInlineEdit.ajaxUrl||!window.fetch){return;}' .
                        'var cat=bornadoInlineEdit.prewarmCategory||{};' .
                        'if(!cat.id||!cat.name){return;}' .
                        'var nonceEl=document.getElementById("save_selected_category_nonce");' .
                        'var catTpl=document.getElementById("cat_template_html");' .
                        'var customWrap=document.getElementById("custom_field_container");' .
                        'if(!nonceEl||(!catTpl&&!customWrap)){return;}' .
                        'if(catTpl&&catTpl.children.length){return;}' .
                        'var fd=new FormData();' .
                        'fd.append("action","save_selected_category");' .
                        'fd.append("category_id",String(cat.id));' .
                        'fd.append("category_name",String(cat.name));' .
                        'fd.append("post_id",String(bornadoInlineEdit.adId||0));' .
                        'fd.append("security",String(nonceEl.value||""));' .
                        'fetch(bornadoInlineEdit.ajaxUrl,{method:"POST",body:fd,credentials:"same-origin"})' .
                        '.then(function(r){return r.json();})' .
                        '.then(function(response){' .
                            'if(!response||response.success!==true||!response.data){return;}' .
                            'var data=response.data;' .
                            'var setHtml=function(id,html){var el=document.getElementById(id);if(el&&typeof html==="string"){el.innerHTML=html;}};' .
                            'setHtml("cat_template_html",data.category_template_html||"");' .
                            'setHtml("ad_condition_and_warranty_box",data.condition_and_value_fields||"");' .
                            'setHtml("tags_and_video_link_box",data.tags_and_video_fields||"");' .
                            'if(typeof data.custom_fields_html==="string"){var c=document.getElementById("custom_field_container");if(c){c.innerHTML=data.custom_fields_html?("<h3>"+((window.sb_options&&sb_options.additional_fields_text)?sb_options.additional_fields_text:"Additional Fields")+"</h3>"+data.custom_fields_html):"";}}' .
                            'if(data.ai_intent_in_category){setHtml("ad_intent_type_container",data.intent_ad_type_html||"");setHtml("ad_intent_condition_warranty_container",data.intent_condition_warranty_html||"");var ai=document.getElementById("ai-intent-fields-container");if(ai){ai.style.display=((data.intent_ad_type_html||data.intent_condition_warranty_html)?"block":"none");}}' .
                            'document.dispatchEvent(new CustomEvent("adforestCategoryTemplateLoaded",{detail:{source:"bornado-prewarm",categoryId:cat.id}}));' .
                        '})' .
                        '.catch(function(){});' .
                    '};' .
                    'if(document.readyState==="loading"){document.addEventListener("DOMContentLoaded",run,{once:true});}else{run();}' .
                '})();',
                'after'
            );
        }

        if (file_exists($probe_js_abs)) {
            wp_enqueue_script(
                'bornado-inline-ad-edit-probe',
                get_stylesheet_directory_uri() . $probe_js_rel,
                array(),
                (string) filemtime($probe_js_abs),
                true
            );
        }

        if (function_exists('bornado_inline_edit_debug_log')) {
            bornado_inline_edit_debug_log(
                'php_enqueue_assets',
                array(
                    'ad_id'       => $ad_id,
                    'debug_build' => $debug_build,
                    'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string) wp_unslash($_SERVER['REQUEST_URI']) : '',
                )
            );
        }
    }

    add_action('wp_enqueue_scripts', 'bornado_inline_edit_enqueue_assets', 210);
}

if (!function_exists('bornado_inline_edit_sync_ad_images')) {
    /**
     * Persist the final image set for inline-edit saves in one atomic step.
     *
     * The inline editor lets owners queue image removals/additions locally and
     * only commit them on "Save changes". AdForest's native image endpoints act
     * immediately, so this child-theme endpoint becomes the single source of
     * truth for image persistence at save-time: delete attachments that are no
     * longer kept, then store the exact final order in `_sb_photo_arrangement_`.
     *
     * @return void
     */
    function bornado_inline_edit_sync_ad_images()
    {
        check_ajax_referer('bornado_sync_ad_images', 'security');

        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('برای این عملیات باید وارد شوید.', 'adforest')), 403);
        }

        $ad_id = isset($_POST['ad_id']) ? absint(wp_unslash($_POST['ad_id'])) : 0;
        if ($ad_id < 1 || 'ad_post' !== get_post_type($ad_id)) {
            wp_send_json_error(array('message' => __('آگهی نامعتبر است.', 'adforest')), 400);
        }

        if (!bornado_inline_edit_user_can_edit($ad_id)) {
            wp_send_json_error(array('message' => __('دسترسی لازم برای ویرایش این آگهی را ندارید.', 'adforest')), 403);
        }

        $keep_ids_raw              = isset($_POST['keep_ids']) ? (string) wp_unslash($_POST['keep_ids']) : '';
        $keep_existing_indexes_raw = isset($_POST['keep_existing_indexes']) ? (string) wp_unslash($_POST['keep_existing_indexes']) : '';
        $client_state_raw          = isset($_POST['client_state']) ? (string) wp_unslash($_POST['client_state']) : '';
        $keep_ids                  = array_values(array_filter(array_map('absint', array_filter(array_map('trim', explode(',', $keep_ids_raw))))));
        $keep_existing_indexes     = array();

        foreach (array_map('trim', explode(',', $keep_existing_indexes_raw)) as $raw_index) {
            if ($raw_index === '' || !is_numeric($raw_index)) {
                continue;
            }
            $keep_existing_indexes[] = (int) $raw_index;
        }

        // Existing images are resolved against the LIVE gallery source instead of
        // the cached inline snapshot. This makes the final save deterministic even
        // after uploads/deletes changed `_sb_photo_arrangement_` during the edit.
        $live_gallery_ids = function_exists('bornado_inline_edit_get_live_gallery_ids')
            ? bornado_inline_edit_get_live_gallery_ids($ad_id)
            : array();

        foreach ($keep_existing_indexes as $index) {
            if (isset($live_gallery_ids[$index]) && (int) $live_gallery_ids[$index] > 0) {
                $keep_ids[] = (int) $live_gallery_ids[$index];
            }
        }

        $keep_ids    = array_values(array_unique(array_filter(array_map('absint', $keep_ids))));
        $keep_lookup = array_fill_keys($keep_ids, true);

        $attached_before = array_values(array_map('intval', array_keys((array) get_attached_media('image', $ad_id))));
        $deleted_ids     = array();
        $client_state    = json_decode($client_state_raw, true);

        if (!is_array($client_state)) {
            $client_state = $client_state_raw !== '' ? array('raw' => $client_state_raw) : array();
        }

        if (function_exists('bornado_inline_edit_debug_log')) {
            bornado_inline_edit_debug_log(
                'sync_before_delete',
                array(
                    'ad_id'                  => $ad_id,
                    'incoming_keep_ids'      => $keep_ids,
                    'incoming_keep_indexes'  => $keep_existing_indexes,
                    'live_gallery_ids'       => $live_gallery_ids,
                    'attached_before'        => $attached_before,
                    'arrangement_before'     => (string) get_post_meta($ad_id, '_sb_photo_arrangement_', true),
                    'inline_cached_before'   => (string) get_post_meta($ad_id, '_bornado_inline_gallery_ids', true),
                    'explicit_empty_before'  => (string) get_post_meta($ad_id, '_bornado_gallery_explicitly_empty', true),
                    'client_state'           => $client_state,
                )
            );
        }

        $attached = get_attached_media('image', $ad_id);
        if (is_array($attached)) {
            foreach ($attached as $attachment) {
                $attachment_id = 0;
                if ($attachment instanceof WP_Post) {
                    $attachment_id = (int) $attachment->ID;
                } elseif (is_object($attachment) && isset($attachment->ID)) {
                    $attachment_id = (int) $attachment->ID;
                } else {
                    $attachment_id = (int) $attachment;
                }

                if ($attachment_id > 0 && !isset($keep_lookup[$attachment_id])) {
                    $deleted_ids[] = $attachment_id;
                    wp_delete_attachment($attachment_id, true);
                }
            }
        }

        $final_ids_csv = implode(',', $keep_ids);
        update_post_meta($ad_id, '_sb_photo_arrangement_', $final_ids_csv);
        update_post_meta($ad_id, '_bornado_inline_gallery_ids', $final_ids_csv);
        update_post_meta($ad_id, '_bornado_gallery_explicitly_empty', empty($keep_ids) ? '1' : '0');

        if (function_exists('bornado_inline_edit_debug_log')) {
            bornado_inline_edit_debug_log(
                'sync_after_delete',
                array(
                    'ad_id'                 => $ad_id,
                    'deleted_ids'           => $deleted_ids,
                    'final_keep_ids'        => $keep_ids,
                    'attached_after'        => array_values(array_map('intval', array_keys((array) get_attached_media('image', $ad_id)))),
                    'arrangement_after'     => (string) get_post_meta($ad_id, '_sb_photo_arrangement_', true),
                    'inline_cached_after'   => (string) get_post_meta($ad_id, '_bornado_inline_gallery_ids', true),
                    'explicit_empty_after'  => (string) get_post_meta($ad_id, '_bornado_gallery_explicitly_empty', true),
                )
            );
        }

        wp_send_json_success(
            array(
                'kept_ids' => $keep_ids,
            )
        );
    }

    add_action('wp_ajax_bornado_sync_ad_images', 'bornado_inline_edit_sync_ad_images');
}

if (!function_exists('bornado_inline_edit_debug_ping')) {
    /**
     * Receive client-side debug breadcrumbs from the inline image editor.
     *
     * @return void
     */
    function bornado_inline_edit_debug_ping()
    {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('برای این عملیات باید وارد شوید.', 'adforest')), 403);
        }

        $ad_id = isset($_POST['ad_id']) ? absint(wp_unslash($_POST['ad_id'])) : 0;
        if ($ad_id < 1 || 'ad_post' !== get_post_type($ad_id)) {
            wp_send_json_error(array('message' => __('آگهی نامعتبر است.', 'adforest')), 400);
        }

        if (!bornado_inline_edit_user_can_edit($ad_id)) {
            wp_send_json_error(array('message' => __('دسترسی لازم برای ویرایش این آگهی را ندارید.', 'adforest')), 403);
        }

        $event   = isset($_POST['event_name']) ? sanitize_key(wp_unslash($_POST['event_name'])) : 'client_event';
        $payload = array();

        if (isset($_POST['payload'])) {
            $decoded = json_decode((string) wp_unslash($_POST['payload']), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            } else {
                $payload = array('raw' => (string) wp_unslash($_POST['payload']));
            }
        }

        if (function_exists('bornado_inline_edit_debug_log')) {
            bornado_inline_edit_debug_log(
                'client_' . $event,
                array(
                    'ad_id'   => $ad_id,
                    'payload' => $payload,
                )
            );
        }

        wp_send_json_success(array('logged' => true));
    }

    add_action('wp_ajax_bornado_inline_edit_debug_ping', 'bornado_inline_edit_debug_ping');
}

if (!function_exists('bornado_inline_edit_render_editor_shell')) {
    /**
     * Inject the (hidden) genuine AdForest post-ad form + the sticky save bar.
     *
     * The single-ad template renders the EXACT public view (so the page is
     * pixel-identical). This footer shell adds the real, pre-filled AdForest
     * form — kept visually hidden — whose controls the front-end script moves
     * in-place when the owner taps an element. Saving runs AdForest's own
     * untouched submit pipeline, so we never re-implement validation/upload/save.
     *
     * `ad_post_short_base_func()` echoes directly and keys edit mode off
     * `$_GET['id']`; we buffer it and set the id just for this call.
     *
     * @return void
     */
    function bornado_inline_edit_render_editor_shell()
    {
        if (!bornado_inline_edit_is_active() || !function_exists('ad_post_short_base_func')) {
            return;
        }

        $ad_id = bornado_inline_edit_current_ad_id();
        if ($ad_id < 1) {
            return;
        }

        $prev_get_id     = array_key_exists('id', $_GET) ? $_GET['id'] : null;
        $prev_request_id = array_key_exists('id', $_REQUEST) ? $_REQUEST['id'] : null;

        $_GET['id']     = $ad_id;
        $_REQUEST['id'] = $ad_id;

        ob_start();
        ad_post_short_base_func(array('form_title' => ''));
        $form_html = ob_get_clean();

        if (null === $prev_get_id) {
            unset($_GET['id']);
        } else {
            $_GET['id'] = $prev_get_id;
        }
        if (null === $prev_request_id) {
            unset($_REQUEST['id']);
        } else {
            $_REQUEST['id'] = $prev_request_id;
        }

        $view_url = bornado_inline_edit_view_url($ad_id);
        ?>
        <div id="bornado-edit-shell" class="bornado-edit-shell" aria-hidden="false">
            <div class="bornado-edit-formhost"><?php echo $form_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped — trusted AdForest shortcode markup. ?></div>
        </div>
        <?php
        if (function_exists('bornado_render_wheel_picker')) {
            echo bornado_render_wheel_picker(
                array(
                    'id' => 'bornado-inline-date-wheel-picker',
                    'class_name' => 'bornado-inline-date-wheel-picker',
                    'type' => 'date',
                    'variant' => 'date-modal',
                    'hidden' => true,
                    'title' => __('انتخاب تاریخ', 'adforest'),
                    'eyebrow' => __('Inline Edit', 'adforest'),
                    'confirm_text' => __('تایید تاریخ', 'adforest'),
                    'cancel_text' => __('انصراف', 'adforest'),
                    'show_output' => false,
                    'preview_format' => 'YYYY-MM-DD',
                    'output_format' => 'YYYY-MM-DD',
                    'column_order' => array('year', 'month', 'day'),
                )
            ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }
        ?>

        <div class="bornado-edit-bar" id="bornado-edit-bar" role="region" aria-label="<?php echo esc_attr__('ذخیرهٔ تغییرات آگهی', 'adforest'); ?>">
            <div class="bornado-edit-bar__inner">
                <span class="bornado-edit-bar__hint">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                    <span>حالت ویرایش — روی هر بخش بزنید تا ویرایش شود</span>
                </span>
                <span class="bornado-edit-bar__actions">
                    <a class="bornado-edit-bar__cancel" href="<?php echo esc_url($view_url); ?>">انصراف</a>
                    <button type="button" class="bornado-edit-bar__save" id="bornado-edit-save">
                        <i class="fas fa-check" aria-hidden="true"></i>
                        <span class="bornado-edit-bar__save-text">ذخیره تغییرات</span>
                    </button>
                </span>
            </div>
        </div>
        <?php
    }
}

if (!function_exists('bornado_inline_edit_body_class')) {
    /**
     * Flag the document so CSS can scope safely to active edit sessions only.
     *
     * @param array $classes Body classes.
     * @return array
     */
    function bornado_inline_edit_body_class($classes)
    {
        if (bornado_inline_edit_is_active()) {
            $classes[] = 'bornado-inline-edit-active';
        }

        return $classes;
    }

    add_filter('body_class', 'bornado_inline_edit_body_class');
}

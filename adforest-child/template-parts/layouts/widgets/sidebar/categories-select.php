<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : __('Categories', 'adforest');
$placeholder = !empty($instance['placeholder']) ? $instance['placeholder'] : __('Select a category', 'adforest');
$show_counts = !empty($instance['show_counts']) ? (bool) $instance['show_counts'] : false;
$enable_cascading = !empty($instance['enable_cascading']) ? (bool) $instance['enable_cascading'] : false;

$expand = '';
if (isset($_GET['cat_id'])) {
    $toggle = "";
    $expand = 'show';
}

$collapsed = 'collapsed';
if (isset($instance['open_widget']) && $instance['open_widget'] == '1') {
    $expand = 'show';
    $collapsed = '';
}

$current_cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
$selected_chain = array();
if ($current_cat_id > 0) {
    $ancestors = get_ancestors($current_cat_id, 'ad_cats');
    $selected_chain = array_reverse($ancestors);
    $selected_chain[] = $current_cat_id;
}

// Fetch categories for select
$ad_categories = adforest_get_ad_taxonomy_callback('ad_cats');
$contextual_counts = function_exists('bornado_category_widget_get_contextual_counts')
    ? bornado_category_widget_get_contextual_counts($ad_categories)
    : array();

// Optionally filter categories with zero ads
if (isset($adforest_theme['search_popup_cat_disable']) && $adforest_theme['search_popup_cat_disable'] == true) {
    $ad_categories = array_filter($ad_categories, function ($category) use ($contextual_counts) {
        $details = function_exists('bornado_category_widget_get_term_details')
            ? bornado_category_widget_get_term_details($category, $contextual_counts)
            : get_taxonomy_details($category);

        return isset($details['ad_count']) && (int) $details['ad_count'] > 0;
    });
}

$adforest_search_page = apply_filters('adforest_language_page_id', $adforest_theme['sb_search_page']);
$adforest_search_page = isset($adforest_search_page) && $adforest_search_page != '' ? get_the_permalink($adforest_search_page) : 'javascript:void(0)';
$adforest_search_page = apply_filters('adforest_category_widget_form_action', $adforest_search_page, 'cat_page');
?>
<div class="accordion-item">
    <h2 class="accordion-header">
        <button class="accordion-button adt-category <?php echo esc_attr($collapsed); ?>" type="button"
                data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseSelectCats"
                aria-expanded="false" aria-controls="panelsStayOpen-collapseSelectCats">
            <?php echo esc_html($title); ?>
        </button>
    </h2>
    <div id="panelsStayOpen-collapseSelectCats" class="accordion-collapse collapse <?php echo esc_attr($expand); ?>">
        <div class="accordion-body categories_search">
            <form method="get" action="<?php echo adforest_return_echo($adforest_search_page); ?>">
                <div class="form-group">
                    <select id="adforest-category-select" class="form-control adforest-select2" data-placeholder="<?php echo esc_attr($placeholder); ?>" data-cascading="<?php echo $enable_cascading ? '1' : '0'; ?>" data-selected-chain="<?php echo esc_attr(implode(',', $selected_chain)); ?>">
                        <option value=""><?php echo esc_html($placeholder); ?></option>
                        <?php foreach ($ad_categories as $category) {
                            $details = function_exists('bornado_category_widget_get_term_details')
                                ? bornado_category_widget_get_term_details($category, $contextual_counts)
                                : get_taxonomy_details($category);
                            $name = $details['name'] ?? '';
                            $ad_count = $details['ad_count'] ?? 0;
                            $selected = selected($current_cat_id, $category->term_id, false);
                            $label = $name;
                            if ($show_counts) {
                                $label .= ' (' . intval($ad_count) . ')';
                            }
                            ?>
                            <option value="<?php echo esc_attr($category->term_id); ?>" <?php echo $selected; ?>><?php echo esc_html($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <?php if ($enable_cascading) { ?>
                <div id="adforest-category-children" class="form-group" style="margin-top:10px;"></div>
                <?php } ?>
                <?php echo adforest_search_params('cat_id'); ?>
                <input type="hidden" name="cat_id" id="adforest-category-selected" value="<?php echo esc_attr($current_cat_id); ?>">
                <?php apply_filters('adforest_form_lang_field', true); ?>
                <div class="mt-2">
                    <button type="submit" class="adt-button-dark"><?php esc_html_e('Apply', 'adforest'); ?></button>
                    <?php if ($current_cat_id) { ?>
                        <a class="btn btn-sm btn-outline-secondary" href="<?php echo esc_url(remove_query_arg('cat_id')); ?>"><?php esc_html_e('Clear', 'adforest'); ?></a>
                    <?php } ?>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    if (window.jQuery && jQuery.fn.select2) {
        var $ = jQuery;
        var $root = $('#adforest-category-select');
        $root.select2({ width: '100%' });
        const loading = $("#sb_loading");
        if ($root.data('cascading') == '1') {
            var $childrenWrap = $('#adforest-category-children');
            function fetchChildren(parentId, level, chainArr) {
                if (!parentId) { $childrenWrap.empty(); return; }
                loading.show();
                $.get('<?php echo admin_url('admin-ajax.php'); ?>', {
                    action: 'adforest_get_child_categories',
                    parent_id: parentId,
                    taxonomy: 'ad_cats',
                    security: $('#adforest_get_child_categories_nonce').val(),
                }).done(function(resp){
                    loading.hide();
                    $childrenWrap.find('[data-level]').each(function(){
                        if (parseInt($(this).data('level')) >= level) { $(this).remove(); }
                    });
                    if (resp && resp.success && resp.data && resp.data.length) {
                        var selectId = 'adforest-category-select-child-' + level;
                        var $sel = $('<select/>', { id: selectId, class: 'form-control adforest-select2', 'data-level': level }).append($('<option/>', { value: '', text: '<?php echo esc_js(__('Select sub category', 'adforest')); ?>'}));
                        resp.data.forEach(function(item){
                            $sel.append($('<option/>', { value: item.id, text: item.text }));
                        });
                        $childrenWrap.append($sel);
                        $sel.select2({ width: '100%' }).on('change', function(){
                            var val = $(this).val();
                            if (val) { $('#adforest-category-selected').val(val); fetchChildren(val, level + 1, chainArr); }
                        });

                        if (chainArr && chainArr[level] !== undefined) {
                            var targetVal = String(chainArr[level]);
                            if ($sel.find('option[value="' + targetVal + '"]').length) {
                                $sel.val(targetVal).trigger('change');
                            }
                        }
                    }
                });
            }
            $root.on('change', function(){
                var val = $(this).val();
                $('#adforest-category-selected').val(val);
                fetchChildren(val, 1, null);
            });
            var initial = $('#adforest-category-selected').val();
            var chainStr = $root.data('selected-chain') || '';
            var chainArr = chainStr ? chainStr.split(',') : null;
            if (chainArr && chainArr.length) {
                var rootTarget = String(chainArr[0]);
                if ($root.find('option[value="' + rootTarget + '"]').length) {
                    $root.val(rootTarget).trigger('change.select2');
                    $('#adforest-category-selected').val(rootTarget);
                    fetchChildren(rootTarget, 1, chainArr);
                }
            } else if (initial) {
                fetchChildren(initial, 1, null);
            }
        }
    }
});
</script>

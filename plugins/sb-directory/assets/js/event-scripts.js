jQuery(document).ready(function($) {
    console.log("In this file");
    var allCategories = [];
    var mainCategories = [];
    var childCategories = {};

    function fetchCategories() {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'get_event_categories',
                security: sb_event_vars.security
            },
            success: function(response) {
                if (response.success) {
                    allCategories = response.data;
                    processCategories();
                    populateMainCategoryDropdown();

                    if ($('#is_update').val() !== '') {
                        preselectCategories();
                    }
                }
            }
        });
    }

    function processCategories() {
        allCategories.forEach(function(category) {
            if (category.parent === 0) {
                mainCategories.push(category);
            } else {
                if (!childCategories[category.parent]) {
                    childCategories[category.parent] = [];
                }
                childCategories[category.parent].push(category);
            }
        });
    }

    function populateMainCategoryDropdown() {
        var $mainCategorySelect = $('#main_event_category');
        $mainCategorySelect.empty();
        $mainCategorySelect.append('<option value="">' + sb_event_vars.select_category + '</option>');

        mainCategories.forEach(function(category) {
            $mainCategorySelect.append('<option value="' + category.term_id + '">' + category.name + '</option>');
        });
    }

    $('#main_event_category').on('change', function() {
        console.log("Changes");
        var mainCategoryId = $(this).val();
        var $childCategorySelect = $('#child_event_category');

        $childCategorySelect.empty().append('<option value="">' + sb_event_vars.select_subcategory + '</option>');
        $('#child_category_container').hide();

        $('#sb_event_cat').val(mainCategoryId);

        if (mainCategoryId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_event_child_categories',
                    parent_id: mainCategoryId,
                    security: sb_event_vars.security
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        $.each(response.data, function(index, category) {
                            $childCategorySelect.append('<option value="' + category.term_id + '">' + category.name + '</option>');
                        });
                        $('#child_category_container').show();
                    }
                }
            });
        }
    });

    $('#child_event_category').on('change', function() {
        var childCategoryId = $(this).val();
        if (childCategoryId) {
            $('#sb_event_cat').val(childCategoryId);
        } else {
            $('#sb_event_cat').val($('#main_event_category').val());
        }
    });

    if ($('#main_event_category').val()) {
        $('#main_event_category').trigger('change');
    }

    function preselectCategories() {
        var selectedCategoryId = $('#sb_event_cat').val();

        if (selectedCategoryId) {
            var category = allCategories.find(function(cat) {
                return cat.term_id == selectedCategoryId;
            });

            if (category) {
                if (category.parent === 0) {
                    $('#main_event_category').val(category.term_id).trigger('change');
                } else {
                    $('#main_event_category').val(category.parent).trigger('change');
                    $('#child_event_category').val(category.term_id).trigger('change');
                }
            }
        }
    }

    fetchCategories();
});
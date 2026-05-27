<?php
global $adforest_theme;
$title = !empty($instance['title']) ? $instance['title'] : esc_html__('Categories', 'adforest');

$title_val = isset($_GET['title']) ? $_GET['title'] : "";
$enable_show_more_cats = !empty($instance['show_more_cate']) ? $instance['show_more_cate'] : 0;
$no_of_cats_before_show_more = !empty($instance['no_of_cats']) ? $instance['no_of_cats'] : 0;

// Get current query parameters
$current_query = $_GET;

$product_categories = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
    'parent' => 0,
));

if (!empty($product_categories) && !is_wp_error($product_categories)) {
    if ($enable_show_more_cats && count($product_categories) > $no_of_cats_before_show_more) {
        $first_categories = array_slice($product_categories, 0, $no_of_cats_before_show_more);
        $rest_categories = array_slice($product_categories, $no_of_cats_before_show_more);
    } else {
        $first_categories = $product_categories;
        $rest_categories = [];
    }
}
?>
<div class="adt-search-list-box">
    <h3><?php echo esc_html($title); ?></h3>
    <ul class="categories-list">
        <?php if (!empty($first_categories)) : ?>
            <?php foreach ($first_categories as $category) :
                $has_children = !empty(get_term_children($category->term_id, 'product_cat'));
                ?>
                <li>
                    <a href="javascript:void(0);"
                       class="category-link-woo"
                       data-category-id="<?php echo esc_attr($category->term_id); ?>"
                       data-has-children="<?php echo !empty($has_children) ? 'true' : 'false'; ?>">
                        <i class="fas fa-folder"></i>
                        <?php echo esc_html($category->name); ?> (<?php echo esc_html($category->count); ?>)
                    </a>
                </li>
            <?php endforeach; ?>

            <?php foreach ($rest_categories as $category) :
                $has_children = !empty(get_term_children($category->term_id, 'product_cat'));
                ?>
                <li class="hidden-category" style="display: none;">
                    <a href="javascript:void(0);"
                       class="category-link-woo"
                       data-category-id="<?php echo esc_attr($category->term_id); ?>"
                       data-has-children="<?php echo !empty($has_children) ? 'true' : 'false'; ?>">
                        <i class="fas fa-folder"></i>
                        <?php echo esc_html($category->name); ?> (<?php echo esc_html($category->count); ?>)
                    </a>
                </li>
            <?php endforeach; ?>

            <?php if (!empty($rest_categories)) : ?>
                <li class="toggle-wrapper">
                    <a href="javascript:void(0);" id="showMoreCategories"><?php esc_html_e('Show More', 'adforest'); ?></a>
                    <a href="javascript:void(0);" id="showLessCategories"
                       style="display: none;"><?php esc_html_e('Show Less', 'adforest'); ?></a>
                </li>
            <?php endif; ?>

        <?php else : ?>
            <li><?php echo esc_html__('No categories found.', 'adforest'); ?></li>
        <?php endif; ?>
    </ul>
</div>

<div id="categoryModal" class="category-modal">
    <div class="modal-content">
        <span class="close-modal">&times;</span>
        <div class="modal-header">
            <h4><?php esc_html_e('Select Subcategory', 'adforest'); ?></h4>
        </div>
        <div class="modal-body"></div>
    </div>
</div>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        // Show/Hide More Categories
        const showMoreBtn = document.getElementById('showMoreCategories');
        const showLessBtn = document.getElementById('showLessCategories');
        const hiddenItems = document.querySelectorAll('.hidden-category');

        if (showMoreBtn && showLessBtn && hiddenItems.length > 0) {
            showMoreBtn.addEventListener('click', function () {
                hiddenItems.forEach(item => item.style.display = 'list-item');
                showMoreBtn.style.display = 'none';
                showLessBtn.style.display = 'inline';
            });

            showLessBtn.addEventListener('click', function () {
                hiddenItems.forEach(item => item.style.display = 'none');
                showLessBtn.style.display = 'none';
                showMoreBtn.style.display = 'inline';
            });
        }

        // Category Modal Handling
        const modal = document.getElementById('categoryModal');
        const modalBody = modal.querySelector('.modal-body');
        const closeModal = modal.querySelector('.close-modal');

        document.querySelectorAll('.category-link-woo').forEach(link => {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                const categoryId = this.dataset.categoryId;
                const hasChildren = this.dataset.hasChildren === 'true';

                if (hasChildren) {
                    fetchChildCategories(categoryId);
                } else {
                    setCategoryAndRedirect(categoryId);
                }
            });
        });

        closeModal.addEventListener('click', function () {
            modal.style.display = 'none';
        });

        window.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        function fetchChildCategories(parentId) {
            $("#sb_loading").show();
            const nonce = document.getElementById('get_child_categories_woo_nonce') ? document.getElementById('get_child_categories_woo_nonce').value : '';
            fetch(`<?php echo admin_url('admin-ajax.php'); ?>?action=get_child_categories_woo&parent_id=${parentId}&security=${nonce}`)
                .then(response => {
                    $("#sb_loading").hide();
                    return response.json();
                })
                .then(data => {
                    $("#sb_loading").hide();
                    console.log('Received data:', data);
                    if (data.success && data.data.length > 0) {
                        showCategoryModal(data.data, parentId);
                    } else {
                        console.log('No children found, redirecting');
                        setCategoryAndRedirect(parentId);
                    }
                })
                .catch(error => {
                    $("#sb_loading").hide();
                    console.error('Fetch error:', error);
                    setCategoryAndRedirect(parentId);
                });
        }

        function showCategoryModal(categories, parentId) {
            modalBody.innerHTML = '';

            const skipButton = document.createElement('div');
            skipButton.innerHTML = `
                                    <button class="adt-button-dark-1 skip-parent-category" data-category-id="${parentId}">
                                        <?php echo esc_js(esc_html__('Search with this category', 'adforest')); ?>
                                    </button>
                                    `;
            modalBody.appendChild(skipButton);

            const list = document.createElement('ul');
            list.className = 'modal-categories-list';


            categories.forEach(category => {
                // Use the has_children property from AJAX response
                const hasChildren = category.has_children;
                const li = document.createElement('li');
                li.innerHTML = `
            <a href="javascript:void(0);"
               class="modal-category-link-woo"
               data-category-id="${category.term_id}"
               data-has-children="${hasChildren ? 'true' : 'false'}">
                ${category.name} (${category.count})
            </a>
        `;
                list.appendChild(li);
            });

            modalBody.appendChild(list);
            modalBody.querySelector('.skip-parent-category').addEventListener('click', function () {
                const categoryId = this.dataset.categoryId;
                setCategoryAndRedirect(categoryId);
            });

            modal.style.display = 'block';

            // Add click handlers for new modal links
            modalBody.querySelectorAll('.modal-category-link-woo').forEach(link => {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    const categoryId = this.dataset.categoryId;
                    const hasChildren = this.dataset.hasChildren === 'true';

                    if (hasChildren) {
                        fetchChildCategories(categoryId);
                    } else {
                        setCategoryAndRedirect(categoryId);
                    }
                });
            });
        }

        function setCategoryAndRedirect(categoryId) {
            let currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('category_id', categoryId);
            window.location.href = currentUrl.href;
        }
    });
</script>

<style>
    .category-modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
        background-color: #fefefe;
        margin: 15% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 500px;
        position: relative;
    }

    .close-modal {
        position: absolute;
        right: 20px;
        top: 10px;
        font-size: 28px;
        cursor: pointer;
    }

    .modal-categories-list {
        list-style: none;
        padding: 0;
    }

    .modal-categories-list li {
        padding: 10px;
        border-bottom: 1px solid #eee;
    }

    .modal-categories-list a {
        color: #333;
        text-decoration: none;
    }

    .modal-categories-list a:hover {
        color: #007bff;
    }
</style>
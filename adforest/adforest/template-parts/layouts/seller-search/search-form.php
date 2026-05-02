<?php
$title = isset($_GET["title"]) ? sanitize_text_field($_GET["title"]) : "";
$rating = isset($_GET["rating"]) ? $_GET["rating"] : "0";
?>
<div class="accordion" id="accordionPanelsStayOpenExample">
    <div class="accordion-item">
        <h2 class="accordion-header">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                <?php echo esc_html__("Search Filter", 'adforest'); ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse show">
            <div class="accordion-body">
                <div class="adt-search-list-box">
                    <form method="get">
                        <div class="form-field">
                            <label for="search" class="form-label"><?php echo esc_html__("Search", 'adforest'); ?></label>
                            <input type="text" class="form-control" id="search" name="title" placeholder=<?php echo esc_attr__('Search by Name', 'adforest'); ?> value="<?php echo esc_attr($title); ?>">
                            <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                        </div>
                        <div class="col-md-12 col-sm-12 no-padding">
                            <div class="form-group">
                                <div dir="ltr">
                                    <input id="input-21b" name="rating" value="<?php echo esc_attr($rating); ?>" type="text"
                                           data-show-clear="false" <?php if (is_rtl()) { ?> dir="rtl"
                                           <?php } ?>class="rating" data-min="0" data-max="5" data-step="1"
                                           data-size="xs" >
                                </div>
                            </div>
                            <div class="clearfix"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
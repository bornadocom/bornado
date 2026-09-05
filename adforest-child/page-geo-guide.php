<?php
/* Template Name: Bornado - Geo Guide */

if (!defined('ABSPATH')) {
    exit;
}

get_header();

$post_id   = get_queried_object_id();
$settings  = function_exists('bornado_geo_guide_get_settings') ? bornado_geo_guide_get_settings($post_id) : array();
$post      = !empty($settings['post']) && $settings['post'] instanceof WP_Post ? $settings['post'] : get_post($post_id);
$location  = !empty($settings['location']) ? (string) $settings['location'] : get_the_title($post_id);
$featured  = !empty($settings['featured_categories']) && is_array($settings['featured_categories']) ? $settings['featured_categories'] : array();
$faq_items = !empty($settings['faq_items']) && is_array($settings['faq_items']) ? $settings['faq_items'] : array();
$areas     = !empty($settings['local_areas']) && is_array($settings['local_areas']) ? $settings['local_areas'] : array();
$steps     = !empty($settings['how_to_steps']) && is_array($settings['how_to_steps']) ? $settings['how_to_steps'] : array();
$proofs    = !empty($settings['proof_points']) && is_array($settings['proof_points']) ? $settings['proof_points'] : array();
$trust_links = !empty($settings['trust_links']) && is_array($settings['trust_links']) ? $settings['trust_links'] : array();
$page_url  = get_permalink($post_id);

$quick_actions = array();
if (!empty($settings['primary_cta_url']) && !empty($settings['primary_cta_label'])) {
    $quick_actions[] = array(
        'label' => (string) $settings['primary_cta_label'],
        'url'   => (string) $settings['primary_cta_url'],
    );
}
if (!empty($settings['secondary_cta_url']) && !empty($settings['secondary_cta_label'])) {
    $quick_actions[] = array(
        'label' => (string) $settings['secondary_cta_label'],
        'url'   => (string) $settings['secondary_cta_url'],
    );
}
foreach (array_slice($featured, 0, 3) as $item) {
    if (!empty($item['url']) && !empty($item['name'])) {
        $quick_actions[] = array(
            'label' => (string) $item['name'],
            'url'   => (string) $item['url'],
        );
    }
}
if (!empty($faq_items)) {
    $quick_actions[] = array(
        'label' => __('سوالات رایج', 'adforest'),
        'url'   => '#bornado-guide-faq',
    );
}
?>

<section class="bornado-geo-guide">
    <div class="container">
        <?php if (function_exists('adforest_custom_breadcrumbs')) : ?>
            <div class="bornado-geo-guide__breadcrumbs">
                <?php adforest_custom_breadcrumbs(); ?>
            </div>
        <?php endif; ?>

        <header class="bornado-geo-guide__hero">
            <div class="bornado-geo-guide__hero-copy">
                <div class="bornado-geo-guide__eyebrow"><?php echo esc_html__('Bornado Geo Guide', 'adforest'); ?></div>
                <h1 class="bornado-geo-guide__title"><?php echo esc_html(get_the_title($post_id)); ?></h1>
                <?php if (!empty($settings['hero_intro'])) : ?>
                    <div class="bornado-geo-guide__intro">
                        <?php echo wp_kses_post(wpautop((string) $settings['hero_intro'])); ?>
                    </div>
                <?php endif; ?>
                <div class="bornado-geo-guide__intent-note">
                    <?php echo esc_html__('این صفحه یک راهنمای محتوایی برای این بازار است. برای دیدن آگهی های فعال و فیلترها، از مسیرهای سریع همین صفحه وارد listingها شوید.', 'adforest'); ?>
                </div>
                <div class="bornado-geo-guide__meta">
                    <span><?php echo esc_html($location); ?></span>
                    <?php if ($post instanceof WP_Post) : ?>
                        <span><?php echo esc_html__('آخرین بروزرسانی:', 'adforest') . ' ' . esc_html(get_the_modified_date('', $post)); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($settings['total_count'])) : ?>
                        <span><?php echo esc_html(number_format_i18n((int) $settings['total_count']) . ' آگهی فعال'); ?></span>
                    <?php endif; ?>
                </div>
                <div class="bornado-geo-guide__cta-row">
                    <?php if (!empty($settings['primary_cta_url']) && !empty($settings['primary_cta_label'])) : ?>
                        <a class="bornado-geo-guide__button bornado-geo-guide__button--primary" href="<?php echo esc_url((string) $settings['primary_cta_url']); ?>">
                            <?php echo esc_html((string) $settings['primary_cta_label']); ?>
                        </a>
                    <?php endif; ?>
                    <?php if (!empty($settings['secondary_cta_url']) && !empty($settings['secondary_cta_label'])) : ?>
                        <a class="bornado-geo-guide__button bornado-geo-guide__button--secondary" href="<?php echo esc_url((string) $settings['secondary_cta_url']); ?>">
                            <?php echo esc_html((string) $settings['secondary_cta_label']); ?>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </header>

        <?php if (!empty($quick_actions)) : ?>
            <nav class="bornado-geo-guide__quick-actions" aria-label="<?php echo esc_attr__('Quick actions', 'adforest'); ?>">
                <div class="bornado-geo-guide__quick-actions-scroll">
                    <?php foreach ($quick_actions as $action) : ?>
                        <a class="bornado-geo-guide__chip" href="<?php echo esc_url((string) $action['url']); ?>">
                            <?php echo esc_html((string) $action['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </nav>
        <?php endif; ?>

        <section class="bornado-geo-guide__section">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <article class="bornado-geo-guide__stat">
                        <strong><?php echo esc_html(number_format_i18n((int) $settings['total_count'])); ?></strong>
                        <span><?php echo esc_html__('آگهی فعال', 'adforest'); ?></span>
                    </article>
                </div>
                <?php foreach (array_slice($featured, 0, 3) as $item) : ?>
                    <div class="col-6 col-lg-3">
                        <article class="bornado-geo-guide__stat">
                            <strong><?php echo esc_html(number_format_i18n(!empty($item['count']) ? (int) $item['count'] : 0)); ?></strong>
                            <span><?php echo esc_html((string) $item['name']); ?></span>
                        </article>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="bornado-geo-guide__section">
            <div class="bornado-geo-guide__section-heading">
                <div class="bornado-geo-guide__card-label"><?php echo esc_html__('مسیرهای اصلی این صفحه', 'adforest'); ?></div>
                <h2 class="bornado-geo-guide__section-title"><?php echo esc_html__('از اینجا کجا بروید؟', 'adforest'); ?></h2>
            </div>
            <div class="row g-3">
                <?php if (!empty($settings['city_listing_url'])) : ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="bornado-geo-guide__route-card">
                            <strong><?php echo esc_html__('آگهی های فعال همین شهر', 'adforest'); ?></strong>
                            <p><?php echo esc_html__('برای دیدن آگهی های تازه، فیلتر کردن نتایج و بررسی گزینه های فعال در همین شهر.', 'adforest'); ?></p>
                            <a href="<?php echo esc_url((string) $settings['city_listing_url']); ?>"><?php echo esc_html__('باز کردن صفحه آگهی ها', 'adforest'); ?></a>
                        </article>
                    </div>
                <?php endif; ?>
                <?php if (!empty($settings['country_listing_url'])) : ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="bornado-geo-guide__route-card">
                            <strong><?php echo esc_html__('بازار کشور', 'adforest'); ?></strong>
                            <p><?php echo esc_html__('برای دیدن شهرهای دیگر بریتانیا و مرور کلی تر بازار این کشور.', 'adforest'); ?></p>
                            <a href="<?php echo esc_url((string) $settings['country_listing_url']); ?>"><?php echo esc_html__('باز کردن هاب کشور', 'adforest'); ?></a>
                        </article>
                    </div>
                <?php endif; ?>
                <?php if (!empty($featured[0]['url']) && !empty($featured[0]['name'])) : ?>
                    <div class="col-12 col-md-6 col-xl-4">
                        <article class="bornado-geo-guide__route-card">
                            <strong><?php echo esc_html(sprintf('پرتقاضاترین دسته لندن: %s', (string) $featured[0]['name'])); ?></strong>
                            <p><?php echo esc_html__('اگر نیاز شما به این بخش نزدیک تر است، از این مسیر سریع تر به آگهی های مرتبط می رسید.', 'adforest'); ?></p>
                            <a href="<?php echo esc_url((string) $featured[0]['url']); ?>"><?php echo esc_html__('مشاهده آگهی های این دسته', 'adforest'); ?></a>
                        </article>
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($featured)) : ?>
            <section class="bornado-geo-guide__section bornado-geo-guide__section--category-sliders">
                <div class="bornado-geo-guide__section-heading">
                    <div class="bornado-geo-guide__card-label"><?php echo esc_html__('6 دسته اصلی برنادو', 'adforest'); ?></div>
                    <h2 class="bornado-geo-guide__section-title"><?php echo esc_html(sprintf('آخرین آگهی های هر دسته در %s', $location)); ?></h2>
                </div>

                <div class="bornado-geo-guide__category-groups">
                    <?php foreach ($featured as $item) : ?>
                        <?php
                        $category_term_id = !empty($item['term_id']) ? (int) $item['term_id'] : 0;
                        $recent_ads       = $category_term_id > 0 && function_exists('bornado_geo_guide_get_recent_ads_for_category')
                            ? bornado_geo_guide_get_recent_ads_for_category($post_id, $category_term_id, 10)
                            : array();
                        ?>
                        <article class="bornado-geo-guide__category-group">
                            <div class="bornado-geo-guide__category-group-head">
                                <div class="bornado-geo-guide__category-copy">
                                    <div class="bornado-geo-guide__card-label"><?php echo esc_html__('دسته بندی', 'adforest'); ?></div>
                                    <h3 class="bornado-geo-guide__category-heading"><?php echo esc_html((string) $item['name']); ?></h3>
                                    <p class="bornado-geo-guide__category-description"><?php echo esc_html((string) $item['description']); ?></p>
                                </div>
                                <div class="bornado-geo-guide__category-meta">
                                    <span class="bornado-geo-guide__category-count">
                                        <?php echo esc_html(number_format_i18n(!empty($item['count']) ? (int) $item['count'] : 0) . ' آگهی'); ?>
                                    </span>
                                    <?php if (!empty($item['url'])) : ?>
                                        <a class="bornado-geo-guide__button bornado-geo-guide__button--ghost" href="<?php echo esc_url((string) $item['url']); ?>">
                                            <?php echo esc_html(sprintf('مشاهده همه %s', (string) $item['name'])); ?>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <?php if (!empty($recent_ads)) : ?>
                                <div class="bornado-geo-guide__slider" data-bornado-guide-slider>
                                    <div class="bornado-geo-guide__slider-viewport">
                                        <div class="bornado-geo-guide__slider-track" data-bornado-guide-slider-track>
                                            <?php foreach ($recent_ads as $ad_id) : ?>
                                                <?php $card_html = function_exists('bornado_geo_guide_render_ad_card') ? bornado_geo_guide_render_ad_card((int) $ad_id) : ''; ?>
                                                <?php if ($card_html === '') { continue; } ?>
                                                <div class="bornado-geo-guide__ad-slide">
                                                    <?php echo $card_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    <div class="bornado-geo-guide__slider-nav">
                                        <button type="button" class="bornado-geo-guide__slider-btn bornado-geo-guide__slider-btn--prev" data-bornado-guide-slider-prev aria-label="<?php echo esc_attr__('Previous ads', 'adforest'); ?>">
                                            <span aria-hidden="true">&#10094;</span>
                                        </button>
                                        <button type="button" class="bornado-geo-guide__slider-btn bornado-geo-guide__slider-btn--next" data-bornado-guide-slider-next aria-label="<?php echo esc_attr__('Next ads', 'adforest'); ?>">
                                            <span aria-hidden="true">&#10095;</span>
                                        </button>
                                    </div>
                                </div>
                            <?php else : ?>
                                <div class="bornado-geo-guide__empty-state">
                                    <p><?php echo esc_html__('در حال حاضر آگهی فعالی برای نمایش در این دسته پیدا نشد. برای دیدن صفحه کامل این دسته از لینک بالا استفاده کنید.', 'adforest'); ?></p>
                                </div>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="bornado-geo-guide__section">
            <article class="bornado-geo-guide__card bornado-geo-guide__card--content">
                <div class="bornado-geo-guide__card-label"><?php echo esc_html__('خلاصه بازار', 'adforest'); ?></div>
                <h2 class="bornado-geo-guide__section-title"><?php echo esc_html(sprintf('راهنمای سریع %s', $location)); ?></h2>
                <div class="bornado-geo-guide__richtext">
                    <?php echo wp_kses_post(wpautop((string) $settings['market_summary'])); ?>
                </div>
                <?php if ($post instanceof WP_Post && trim((string) $post->post_content) !== '') : ?>
                    <div class="bornado-geo-guide__richtext bornado-geo-guide__richtext--extended">
                        <?php echo apply_filters('the_content', $post->post_content); ?>
                    </div>
                <?php endif; ?>
            </article>
        </section>

        <?php if (!empty($steps)) : ?>
            <section class="bornado-geo-guide__section" id="bornado-guide-how-to">
                <div class="bornado-geo-guide__section-heading">
                    <div class="bornado-geo-guide__card-label"><?php echo esc_html__('چطور استفاده کنیم', 'adforest'); ?></div>
                    <h2 class="bornado-geo-guide__section-title"><?php echo esc_html(sprintf('چطور از Bornado در %s استفاده کنیم', $location)); ?></h2>
                </div>
                <div class="row g-3">
                    <?php foreach ($steps as $index => $step) : ?>
                        <div class="col-12 col-md-6 col-xl-4">
                            <article class="bornado-geo-guide__step-card">
                                <span class="bornado-geo-guide__step-index"><?php echo esc_html((string) ($index + 1)); ?></span>
                                <p><?php echo esc_html((string) $step); ?></p>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($proofs)) : ?>
            <section class="bornado-geo-guide__section" id="bornado-guide-proof">
                <div class="bornado-geo-guide__section-heading">
                    <div class="bornado-geo-guide__card-label"><?php echo esc_html__('نشانه های واقعی بازار', 'adforest'); ?></div>
                    <h2 class="bornado-geo-guide__section-title"><?php echo esc_html__('چرا این صفحه برای این بازار معنا دارد', 'adforest'); ?></h2>
                </div>
                <div class="row g-3">
                    <?php foreach ($proofs as $proof) : ?>
                        <div class="col-12 col-md-6">
                            <article class="bornado-geo-guide__proof-card">
                                <p><?php echo esc_html((string) $proof); ?></p>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($areas)) : ?>
            <section class="bornado-geo-guide__section">
                <div class="bornado-geo-guide__section-heading">
                    <div class="bornado-geo-guide__card-label"><?php echo esc_html__('مناطق و مسیرهای مرتبط', 'adforest'); ?></div>
                    <h2 class="bornado-geo-guide__section-title"><?php echo esc_html__('مسیرهای مرتبط برای ادامه جست وجو', 'adforest'); ?></h2>
                </div>
                <div class="bornado-geo-guide__pill-list">
                    <?php foreach ($areas as $area) : ?>
                        <?php if (!empty($area['url'])) : ?>
                            <a class="bornado-geo-guide__pill" href="<?php echo esc_url((string) $area['url']); ?>">
                                <?php echo esc_html((string) $area['label']); ?>
                            </a>
                        <?php else : ?>
                            <span class="bornado-geo-guide__pill"><?php echo esc_html((string) $area['label']); ?></span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <?php if (!empty($faq_items)) : ?>
            <section class="bornado-geo-guide__section" id="bornado-guide-faq">
                <div class="bornado-geo-guide__section-heading">
                    <div class="bornado-geo-guide__card-label"><?php echo esc_html__('سوالات رایج', 'adforest'); ?></div>
                    <h2 class="bornado-geo-guide__section-title"><?php echo esc_html__('پاسخ به سوال های مهم کاربران', 'adforest'); ?></h2>
                </div>
                <div class="bornado-geo-guide__faq-list">
                    <?php foreach ($faq_items as $item) : ?>
                        <details class="bornado-geo-guide__faq-item">
                            <summary><?php echo esc_html((string) $item['question']); ?></summary>
                            <div class="bornado-geo-guide__faq-answer">
                                <p><?php echo esc_html((string) $item['answer']); ?></p>
                            </div>
                        </details>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <section class="bornado-geo-guide__section" id="bornado-guide-trust">
            <article class="bornado-geo-guide__trust-card">
                <div class="bornado-geo-guide__card-label"><?php echo esc_html__('اعتماد و ایمنی', 'adforest'); ?></div>
                <h2 class="bornado-geo-guide__section-title"><?php echo esc_html__('راهنمای کوتاه برای استفاده مطمئن تر', 'adforest'); ?></h2>
                <div class="bornado-geo-guide__richtext">
                    <?php echo wp_kses_post(wpautop((string) $settings['trust_text'])); ?>
                </div>
                <div class="bornado-geo-guide__trust-links">
                    <?php foreach ($trust_links as $link) : ?>
                        <a href="<?php echo esc_url((string) $link['url']); ?>"><?php echo esc_html((string) $link['label']); ?></a>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <section class="bornado-geo-guide__section bornado-geo-guide__section--final-cta">
            <div class="row g-3">
                <?php if (!empty($settings['primary_cta_url']) && !empty($settings['primary_cta_label'])) : ?>
                    <div class="col-12 col-lg-4">
                        <article class="bornado-geo-guide__cta-card">
                            <h3><?php echo esc_html((string) $settings['primary_cta_label']); ?></h3>
                            <p><?php echo esc_html__('برای دیدن تازه ترین آگهی های لندن، مقایسه گزینه ها و فیلتر کردن نتایج.', 'adforest'); ?></p>
                            <a class="bornado-geo-guide__button bornado-geo-guide__button--primary" href="<?php echo esc_url((string) $settings['primary_cta_url']); ?>">
                                <?php echo esc_html((string) $settings['primary_cta_label']); ?>
                            </a>
                        </article>
                    </div>
                <?php endif; ?>
                <?php if (!empty($settings['secondary_cta_url']) && !empty($settings['secondary_cta_label'])) : ?>
                    <div class="col-12 col-lg-4">
                        <article class="bornado-geo-guide__cta-card">
                            <h3><?php echo esc_html((string) $settings['secondary_cta_label']); ?></h3>
                            <p><?php echo esc_html__('برای ثبت آگهی جدید و دیده شدن بهتر در بازار لندن با عنوان و دسته بندی دقیق.', 'adforest'); ?></p>
                            <a class="bornado-geo-guide__button bornado-geo-guide__button--secondary" href="<?php echo esc_url((string) $settings['secondary_cta_url']); ?>">
                                <?php echo esc_html((string) $settings['secondary_cta_label']); ?>
                            </a>
                        </article>
                    </div>
                <?php endif; ?>
                <?php if (!empty($settings['tertiary_cta_url']) && !empty($settings['tertiary_cta_label'])) : ?>
                    <div class="col-12 col-lg-4">
                        <article class="bornado-geo-guide__cta-card">
                            <h3><?php echo esc_html((string) $settings['tertiary_cta_label']); ?></h3>
                            <p><?php echo esc_html__('برای رفتن مستقیم به بخشی که تقاضای بیشتری در این شهر دارد و سریع تر به نتیجه می رساند.', 'adforest'); ?></p>
                            <a class="bornado-geo-guide__button bornado-geo-guide__button--ghost" href="<?php echo esc_url((string) $settings['tertiary_cta_url']); ?>">
                                <?php echo esc_html((string) $settings['tertiary_cta_label']); ?>
                            </a>
                        </article>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</section>

<?php
get_footer();

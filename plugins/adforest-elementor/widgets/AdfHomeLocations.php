<?php
namespace ElementorAdforest\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Repeater;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modern Home — Top Locations.
 *
 * Renders `ad_country` terms as photographic cards. Two source modes:
 *   - Auto: top N terms by ad count
 *   - Manual: admin picks specific terms via a Repeater, with optional
 *             per-item image override and display-name override.
 *
 * Term images come from AdForest's `adforest_taxonomy_image_url()` (set
 * in WP Admin → Locations → edit term → Image). A blank fallback to the
 * theme placeholder is used when neither an override nor a term image
 * is available.
 */
class AdfHomeLocations extends Widget_Base
{
    public function get_name() { return 'adf_home_locations'; }
    public function get_title() { return __('Modern Home — Top Locations', 'adforest-elementor'); }
    public function get_icon() { return 'eicon-map-pin'; }
    public function get_categories() { return ['adforest_widgets']; }
    public function get_keywords() { return ['adforest', 'home', 'locations', 'cities', 'modern']; }

    /**
     * Build the option list of ad_country terms for the Repeater select.
     */
    private function get_location_options()
    {
        $options = ['' => __('— Select location —', 'adforest-elementor')];
        $terms = get_terms([
            'taxonomy'   => 'ad_country',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ]);
        if (!is_wp_error($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                $options[(string) $term->term_id] = $term->name;
            }
        }
        return $options;
    }

    protected function register_controls()
    {
        $this->start_controls_section('content', [
            'label' => __('Content', 'adforest-elementor'),
        ]);

        $this->add_control('heading', [
            'label' => __('Heading', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => __('Top Locations', 'adforest-elementor'),
        ]);
        $this->add_control('subtitle', [
            'label' => __('Subtitle', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXTAREA,
            'rows'  => 2,
            'default' => __('Find ads by top locations.', 'adforest-elementor'),
        ]);

        $this->add_control('source_mode', [
            'label' => __('Source', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => 'auto',
            'options' => [
                'auto'   => __('Auto — top locations by ad count', 'adforest-elementor'),
                'manual' => __('Manual — choose specific locations', 'adforest-elementor'),
            ],
        ]);

        $this->add_control('limit', [
            'label' => __('Locations to show', 'adforest-elementor'),
            'type'  => Controls_Manager::NUMBER,
            'default' => 4,
            'min' => 1,
            'max' => 12,
            'condition' => ['source_mode' => 'auto'],
        ]);

        // Manual selection — repeater with term picker + optional image/label overrides.
        $repeater = new Repeater();
        $repeater->add_control('term_id', [
            'label' => __('Location', 'adforest-elementor'),
            'type'  => Controls_Manager::SELECT,
            'default' => '',
            'options' => $this->get_location_options(),
        ]);
        $repeater->add_control('image', [
            'label' => __('Image (override)', 'adforest-elementor'),
            'type'  => Controls_Manager::MEDIA,
            'default' => ['url' => ''],
            'description' => __('Optional. Falls back to the image set in WP Admin → Locations → edit term → Image, then to the theme placeholder.', 'adforest-elementor'),
        ]);
        $repeater->add_control('label', [
            'label' => __('Display name (override)', 'adforest-elementor'),
            'type'  => Controls_Manager::TEXT,
            'default' => '',
            'description' => __('Optional. Defaults to the term name.', 'adforest-elementor'),
        ]);

        $this->add_control('manual_items', [
            'label' => __('Locations', 'adforest-elementor'),
            'type'  => Controls_Manager::REPEATER,
            'fields' => $repeater->get_controls(),
            'condition' => ['source_mode' => 'manual'],
            'title_field' => '{{{ label }}}',
        ]);

        $this->end_controls_section();
    }

    protected function render()
    {
        global $adforest_theme;
        $atts = $this->get_settings_for_display();

        $theme_btn_color = !empty($adforest_theme['opt-theme-btn-color']['regular']) ? $adforest_theme['opt-theme-btn-color']['regular'] : '#ff002e';
        $theme_btn_text  = !empty($adforest_theme['opt-theme-btn-text-color']['regular']) ? $adforest_theme['opt-theme-btn-text-color']['regular'] : '#ffffff';
        $_rgb            = sscanf(ltrim($theme_btn_color, '#'), '%2x%2x%2x');
        $theme_btn_rgb   = (is_array($_rgb) && count($_rgb) === 3 && $_rgb[0] !== null) ? implode(',', $_rgb) : '255,0,46';

        $limit       = !empty($atts['limit']) ? (int) $atts['limit'] : 4;
        $heading     = $atts['heading']     ?? '';
        $subtitle    = $atts['subtitle']    ?? '';
        $source_mode = ($atts['source_mode'] ?? 'auto') === 'manual' ? 'manual' : 'auto';

        // Build a normalized $locations array of items shaped as:
        //   ['term' => WP_Term, 'image_override' => '', 'label_override' => '']
        $locations = [];

        if ($source_mode === 'manual') {
            $items = !empty($atts['manual_items']) && is_array($atts['manual_items']) ? $atts['manual_items'] : [];
            foreach ($items as $item) {
                $tid = isset($item['term_id']) ? (int) $item['term_id'] : 0;
                if ($tid <= 0) { continue; }
                $term = get_term($tid, 'ad_country');
                if (is_wp_error($term) || !$term) { continue; }
                $locations[] = [
                    'term'           => $term,
                    'image_override' => isset($item['image']['url']) ? (string) $item['image']['url'] : '',
                    'label_override' => isset($item['label']) ? (string) $item['label'] : '',
                ];
            }
        } else {
            $terms = get_terms([
                'taxonomy'   => 'ad_country',
                'hide_empty' => false,
                'number'     => $limit,
                'orderby'    => 'count',
                'order'      => 'DESC',
            ]);
            if (!is_wp_error($terms) && is_array($terms)) {
                foreach ($terms as $term) {
                    $locations[] = [
                        'term'           => $term,
                        'image_override' => '',
                        'label_override' => '',
                    ];
                }
            }
        }

        if (empty($locations)) {
            return;
        }
        $loc_count = count($locations);
        ?>
        <style>
        .adf-hmw-locs{--hm-brand:<?php echo esc_attr($theme_btn_color); ?>;--hm-brand-text:<?php echo esc_attr($theme_btn_text); ?>;--hm-brand-rgb:<?php echo esc_attr($theme_btn_rgb); ?>;padding:36px 0 48px;background:#fff;box-sizing:border-box;}
        .adf-hmw-locs *{box-sizing:border-box;}
        .adf-hmw-locs__wrap{max-width:1200px;margin:0 auto;padding:0 24px;}
        .adf-hmw-locs__head{text-align:center;margin:0 0 30px;}
        .adf-hmw-locs__head h2{font-size:28px;font-weight:600;color:#0f172a;margin:0 0 20px;letter-spacing:-.02em;display:inline-flex;align-items:center;gap:8px;}
        .adf-hmw-locs__head h2 i{color:var(--hm-brand);font-size:22px;}
        .adf-hmw-locs__head p{margin:0 auto;max-width:520px;color:#64748b;font-size:14px;line-height:1.55;}
        .adf-hmw-locs__grid{display:grid;grid-template-columns:repeat(<?php echo (int) min($loc_count, 4); ?>,minmax(0,1fr));gap:18px;}
        .adf-hmw-loc{position:relative;display:block;border-radius:14px;overflow:hidden;text-decoration:none;color:#fff;aspect-ratio:5/4;background:#1f2937;transition:transform .18s ease,box-shadow .18s ease;}
        .adf-hmw-loc:hover{transform:translateY(-3px);box-shadow:0 0 12px rgba(15,23,42,.06);color:#fff;}
        .adf-hmw-loc img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;display:block;transition:transform .4s ease;}
        .adf-hmw-loc:hover img{transform:scale(1.05);}
        .adf-hmw-loc::after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(15,23,42,0) 40%,rgba(15,23,42,.75) 100%);}
        .adf-hmw-loc__pin{position:absolute;top:12px;left:12px;width:30px;height:30px;border-radius:50%;background:var(--hm-brand);color:var(--hm-brand-text);display:inline-flex;align-items:center;justify-content:center;font-size:13px;z-index:2;}
        .adf-hmw-loc__meta{position:absolute;left:14px;right:14px;bottom:14px;display:flex;align-items:center;justify-content:space-between;gap:8px;z-index:2;}
        .adf-hmw-loc__name{font-weight:600;font-size:16px;letter-spacing:-.005em;}
        .adf-hmw-loc__count{background:rgba(255,255,255,.18);backdrop-filter:blur(6px);border-radius:6px;padding:3px 10px;font-size:12px;font-weight:600;}
        @media (max-width:1099px){.adf-hmw-locs__grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
        @media (max-width:600px){.adf-hmw-locs__grid{grid-template-columns:1fr;}}
        </style>
        <section class="adf-hmw-locs">
            <div class="adf-hmw-locs__wrap">
                <?php if ($heading || $subtitle) : ?>
                    <div class="adf-hmw-locs__head">
                        <?php if ($heading) : ?>
                            <h2><i class="fa fa-map-marker-alt"></i> <?php echo esc_html($heading); ?></h2>
                        <?php endif; ?>
                        <?php if ($subtitle) : ?>
                            <p><?php echo esc_html($subtitle); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="adf-hmw-locs__grid">
                    <?php foreach ($locations as $loc_data) :
                        $loc   = $loc_data['term'];
                        $link  = get_term_link($loc);
                        if (is_wp_error($link)) { $link = '#'; }

                        // Image source — admin override wins, then AdForest's term image
                        // (set in WP Admin → Locations → edit term → Image), then the
                        // theme placeholder. Same helper AdForest uses everywhere.
                        $img = $loc_data['image_override'];
                        if (!$img && function_exists('adforest_taxonomy_image_url')) {
                            $img = adforest_taxonomy_image_url($loc->term_id, 'medium_large', false);
                        }
                        if (!$img) {
                            $img = get_template_directory_uri() . '/images/Photo-Not-Available.png';
                        }

                        $label = $loc_data['label_override'] !== '' ? $loc_data['label_override'] : $loc->name;
                        $count = (int) $loc->count;
                        ?>
                        <a href="<?php echo esc_url($link); ?>" class="adf-hmw-loc">
                            <img src="<?php echo esc_url($img); ?>" alt="<?php echo esc_attr($label); ?>" loading="lazy">
                            <span class="adf-hmw-loc__pin"><i class="fa fa-map-marker-alt"></i></span>
                            <div class="adf-hmw-loc__meta">
                                <span class="adf-hmw-loc__name"><?php echo esc_html($label); ?></span>
                                <span class="adf-hmw-loc__count"><?php
                                    printf(
                                        esc_html(_n('%s Ad', '%s Ads', $count, 'adforest-elementor')),
                                        esc_html(number_format_i18n($count))
                                    );
                                ?></span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php
    }
}

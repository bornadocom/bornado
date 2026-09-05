<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('bornado_geo_guide_template_slug')) {
    /**
     * Return the reusable page-template slug for Bornado geo guides.
     *
     * @return string
     */
    function bornado_geo_guide_template_slug()
    {
        return 'page-geo-guide.php';
    }
}

if (!function_exists('bornado_geo_guide_is_template')) {
    /**
     * Whether a post/request uses the Bornado geo guide template.
     *
     * @param int $post_id Optional post ID.
     * @return bool
     */
    function bornado_geo_guide_is_template($post_id = 0)
    {
        $post_type = function_exists('bornado_geo_guide_post_type') ? bornado_geo_guide_post_type() : '';

        if ($post_id > 0) {
            $post = get_post((int) $post_id);
            if ($post instanceof WP_Post && $post_type !== '' && $post->post_type === $post_type) {
                return true;
            }

            return get_page_template_slug((int) $post_id) === bornado_geo_guide_template_slug();
        }

        if ($post_type !== '' && is_singular($post_type)) {
            return true;
        }

        return is_page_template(bornado_geo_guide_template_slug());
    }
}

if (!function_exists('bornado_geo_guide_meta_keys')) {
    /**
     * Central registry for all guide meta keys.
     *
     * @return array<string,string>
     */
    function bornado_geo_guide_meta_keys()
    {
        return array(
            'country_term_id'  => '_bornado_geo_guide_country_term_id',
            'city_term_id'     => '_bornado_geo_guide_city_term_id',
            'hero_intro'       => '_bornado_geo_guide_hero_intro',
            'market_summary'   => '_bornado_geo_guide_market_summary',
            'how_to_steps'     => '_bornado_geo_guide_how_to_steps',
            'proof_points'     => '_bornado_geo_guide_proof_points',
            'faq_items'        => '_bornado_geo_guide_faq_items',
            'local_areas'      => '_bornado_geo_guide_local_areas',
            'trust_text'       => '_bornado_geo_guide_trust_text',
            'cta_primary_label'   => '_bornado_geo_guide_cta_primary_label',
            'cta_primary_url'     => '_bornado_geo_guide_cta_primary_url',
            'cta_secondary_label' => '_bornado_geo_guide_cta_secondary_label',
            'cta_secondary_url'   => '_bornado_geo_guide_cta_secondary_url',
            'cta_tertiary_label'  => '_bornado_geo_guide_cta_tertiary_label',
            'cta_tertiary_url'    => '_bornado_geo_guide_cta_tertiary_url',
        );
    }
}

if (!function_exists('bornado_geo_guide_get_london_admin_example')) {
    /**
     * Professional reference example shown to admins while creating a guide page.
     *
     * @return array<string,mixed>
     */
    function bornado_geo_guide_get_london_admin_example()
    {
        $country_term_id = 0;
        $city_term_id    = 0;

        $country_term = get_term_by('slug', 'uk', 'ad_country');
        if ($country_term instanceof WP_Term) {
            $country_term_id = (int) $country_term->term_id;
        }

        $city_term = get_term_by('slug', 'london', 'ad_country');
        if ($city_term instanceof WP_Term) {
            $city_term_id = (int) $city_term->term_id;
        }

        return array(
            'country_term_id'      => $country_term_id,
            'city_term_id'         => $city_term_id,
            'title'                => 'نیازمندی ایرانیان لندن | راهنمای استفاده از Bornado در لندن',
            'excerpt'              => 'اگر به دنبال نیازمندی ایرانیان در لندن هستید، این صفحه کمک می کند سریع تر بین 6 دسته اصلی برنادو در لندن حرکت کنید، از استخدام و املاک تا خدمات، کالا و لوازم، اجتماعی و وسایل نقلیه؛ و بدون اتلاف وقت به آگهی های فعال همین شهر برسید.',
            'content'              => "لندن برای خیلی از ایرانیان خارج از کشور فقط یک شهر بزرگ و شلوغ نیست؛ شهری است که معمولا چند نیاز مهم را همزمان جلوی کاربر می گذارد. بعضی ها به دنبال کار هستند، بعضی ها دنبال اتاق یا اجاره، بعضی ها می خواهند خدمات محلی فارسی زبان پیدا کنند و بعضی دیگر می خواهند کالایی را بخرند، بفروشند یا حتی برای یک نیاز اجتماعی و روزمره از جامعه ایرانیان کمک بگیرند. به همین دلیل، نیازمندی های لندن زمانی واقعا مفید می شود که کاربر خیلی سریع بفهمد از کدام مسیر باید وارد شود.\n\nدر وضعیت فعلی برنادو، لندن یکی از فعال ترین بازارهای بریتانیا است و هر 6 دسته اصلی سایت در این شهر حضور دارند: اجتماعی، استخدام و کاریابی، املاک، خدمات، کالا و لوازم و وسایل نقلیه. با این حال، وزن این دسته ها یکسان نیست. در صفحه لندن، استخدام و کاریابی پررنگ ترین بخش بازار است، بعد از آن خدمات و املاک سهم مهمی از نیاز واقعی کاربران را پوشش می دهند، و در کنار این ها، کالا و لوازم، اجتماعی و وسایل نقلیه هم برای جست وجوهای مشخص تر و روزمره کاربرد دارند.\n\nاز نظر محتوای واقعی آگهی ها هم این الگو دیده می شود. در استخدام، آگهی های مربوط به سالن های زیبایی، رستوران، کارهای خدماتی و برخی موقعیت های اجرایی بیشتر جلب توجه می کنند. در املاک، موضوع اتاق، اجاره و خانه مشترک برای بسیاری از کاربران اهمیت بالایی دارد. در خدمات، آموزش، زیبایی، نظافت و سرویس های تخصصی محلی از بخش های زنده بازار هستند. در کالا و لوازم، خرید و فروش وسایل کاربردی خانه یا کالاهای شخصی دیده می شود. در اجتماعی، درخواست های روزمره یا ارتباط های محلی مطرح می شود و در وسایل نقلیه نیز با وجود تعداد کمتر، کاربرانی هستند که دقیقا به همین بخش نیاز دارند.\n\nلندن از نظر محلی هم بازاری یکدست نیست. برای بخشی از کاربران فارسی زبان، محدوده هایی مثل Kilburn، Hendon، Finchley یا West London فقط نام منطقه نیست؛ این ها معمولا نشانه ای از دسترسی بهتر، جامعه آشناتر یا مسیر راحت تر برای رفت و آمد روزانه هستند. وقتی یک آگهی محدوده خود را روشن می نویسد، کاربر هم سریع تر می فهمد آیا آن گزینه برایش مناسب است یا نه.\n\nاگر قصد جست وجو دارید، بهترین کار این است که از همین صفحه وارد دسته ای شوید که به نیاز شما نزدیک تر است و بعد آگهی های فعال همان بخش را ببینید. اگر قصد ثبت آگهی دارید، باید عنوانی روشن، توضیحی دقیق و موقعیتی مشخص بنویسید. در شهری مثل لندن، همین وضوح ساده می تواند تفاوت بزرگی در کیفیت بازخورد ایجاد کند.\n\nاین صفحه برای جایگزین شدن با صفحه لیست آگهی ها ساخته نشده است. نقش آن این است که بازار لندن را بهتر توضیح دهد، 6 دسته اصلی را شفاف نشان دهد، و کاربر را با کمترین سردرگمی به صفحه ای برساند که واقعا به نیازش نزدیک است.",
            'hero_intro'           => 'اگر به دنبال نیازمندی ایرانیان در لندن هستید، این صفحه کمک می کند سریع تر بین 6 دسته اصلی برنادو در این شهر حرکت کنید؛ از استخدام و املاک تا خدمات، کالا و لوازم، اجتماعی و وسایل نقلیه، و مستقیم وارد آگهی های فعال و مرتبط شوید.',
            'market_summary'       => 'لندن یکی از فعال ترین بازارهای برنادو در بریتانیا است و هر 6 دسته اصلی سایت در این شهر دیده می شوند: اجتماعی، استخدام و کاریابی، املاک، خدمات، کالا و لوازم و وسایل نقلیه. در عمل، استخدام و کاریابی، خدمات و املاک سهم پررنگ تری از نیاز کاربران را پوشش می دهند، اما چهار دسته دیگر هم برای جست وجوهای روزمره و مشخص، کاملا کاربردی و مهم هستند.',
            'how_to_steps'         => "اگر دنبال کار هستید، از استخدام و کاریابی شروع کنید؛ اگر دنبال اتاق، اجاره یا خانه مشترک هستید، مستقیم وارد املاک شوید.\nدر شهری مثل لندن، آگهی های جدیدتر را زودتر بررسی کنید، چون زمان در بعضی دسته ها اهمیت زیادی دارد.\nقبل از تماس، حتما موقعیت، نوع آگهی، محدوده قیمت و شرایط اصلی را یک بار دقیق مرور کنید.\nاگر می خواهید آگهی ثبت کنید، عنوان روشن، دسته بندی درست و ذکر منطقه یا محدوده محلی را جدی بگیرید.\nبرای قرار ملاقات یا پرداخت، مثل هر پلتفرم نیازمندی دیگر، با دقت و بر اساس اصول ایمنی جلو بروید.",
            'proof_points'         => "در صفحه فعلی لندن، هر 6 دسته اصلی برنادو حضور دارند و این یعنی بازار این شهر فقط محدود به یک یا دو نیاز خاص نیست.\nاستخدام و کاریابی در لندن پررنگ ترین دسته است و نشان می دهد بخش مهمی از کاربران با هدف پیدا کردن فرصت شغلی وارد این بازار می شوند.\nاملاک در لندن فقط به خرید و فروش ملک محدود نیست و آگهی های اتاق، اجاره و خانه مشترک هم در آن نقش مهمی دارند.\nخدمات در لندن از آموزش و زیبایی تا نظافت و سرویس های تخصصی را پوشش می دهد و برای کاربر فارسی زبان یک نیاز واقعی روزمره است.\nحضور کالا و لوازم، اجتماعی و وسایل نقلیه باعث می شود کاربر برای نیازهای کوچک تر اما مهم هم مجبور نباشد از پلتفرم دیگری استفاده کند.",
            'faq_items'            => "در صفحه لندن چه دسته هایی را می توان دید؟ || در لندن هر 6 دسته اصلی برنادو فعال هستند: اجتماعی، استخدام و کاریابی، املاک، خدمات، کالا و لوازم و وسایل نقلیه.\nاگر دنبال کار در لندن باشم از کجا شروع کنم؟ || بهترین مسیر، ورود مستقیم به دسته استخدام و کاریابی لندن است؛ چون بخش زیادی از آگهی های این شهر مربوط به فرصت های شغلی، همکاری و جذب نیرو است.\nآیا در لندن می توان آگهی اتاق، اجاره و خانه مشترک پیدا کرد؟ || بله. بخش املاک لندن فقط برای خرید یا فروش نیست و برای اتاق، اجاره و house-share هم یکی از مسیرهای مهم جست وجو محسوب می شود.\nاگر دنبال خدمات محلی فارسی زبان باشم کدام بخش مناسب تر است؟ || برای آموزش، زیبایی، نظافت، خدمات تخصصی و بسیاری از سرویس های محلی، بهتر است از بخش خدمات لندن شروع کنید.\nاین صفحه چه تفاوتی با صفحه آگهی های لندن دارد؟ || این صفحه برای آشنایی سریع با بازار لندن و انتخاب مسیر مناسب ساخته شده است؛ اما صفحه آگهی های لندن جایی است که می توانید نتایج فعال را ببینید، فیلتر کنید و مستقیم وارد جزئیات هر آگهی شوید.",
            'local_areas'          => "North Finchley|https://bornado.com/uk/london/\nFinchley Central|https://bornado.com/uk/london/\nHendon|https://bornado.com/uk/london/\nKilburn|https://bornado.com/uk/london/\nWest London|https://bornado.com/uk/london/\nبریتانیا|https://bornado.com/uk/",
            'trust_text'           => "برای استفاده مطمئن تر از آگهی های لندن، قبل از تماس یا قرار ملاقات، جزئیات آگهی را کامل بخوانید و اگر موردی برایتان مبهم است همان ابتدا سوال بپرسید. در آگهی های مربوط به اجاره، خرید و فروش یا خدمات، شفاف بودن قیمت، موقعیت و شرایط همکاری اهمیت زیادی دارد. اگر خواستید آگهی ثبت کنید، عنوان دقیق، دسته بندی درست و ذکر محدوده محلی کمک می کند بازخورد بهتری بگیرید. همچنین اگر موردی مشکوک دیدید، بهتر است از مسیرهای پشتیبانی و گزارش موجود در سایت استفاده کنید.",
            'cta_primary_label'    => 'دیدن آگهی های لندن',
            'cta_primary_url'      => 'https://bornado.com/uk/london/',
            'cta_secondary_label'  => 'ثبت آگهی در لندن',
            'cta_secondary_url'    => '',
            'cta_tertiary_label'   => 'مشاهده استخدام و کاریابی لندن',
            'cta_tertiary_url'     => 'https://bornado.com/uk/london/jobs/',
        );
    }
}

if (!function_exists('bornado_geo_guide_get_field_guides')) {
    /**
     * Per-field editorial guidance mirrored from the planning canvases.
     *
     * @return array<string,array<int,string>>
     */
    function bornado_geo_guide_get_field_guides()
    {
        return array(
            'hero_intro' => array(
                'در 60 تا 100 کلمه، خیلی مستقیم بگو این صفحه برای چه کسی است و چه کمکی می کند.',
                'به 2 تا 4 دسته مهم همین شهر اشاره کن و از متن عمومی و کلیشه ای دوری کن.',
                'این بخش باید answer-first و قابل استخراج برای AI باشد.',
            ),
            'market_summary' => array(
                'این بخش باید market context واقعی بدهد، نه توضیح عمومی درباره شهر.',
                'به نوع تقاضا، نوع آگهی های مهم، یا رفتار کاربران همین بازار اشاره کن.',
                'اگر insight محلی داری، اینجا بهترین جا برای آن است.',
            ),
            'how_to_steps' => array(
                'هر خط یک step کوتاه و عملی باشد.',
                'از زبان task-based استفاده کن: شروع کن، بررسی کن، تماس بگیر، ثبت کن.',
                'بهتر است 3 تا 5 مرحله واضح داشته باشد.',
            ),
            'proof_points' => array(
                'هر خط یک proof واقعی باشد: آمار، دسته داغ، نمونه بازار یا insight first-party.',
                'از claim مبهم مثل «خیلی پرطرفدار است» پرهیز کن.',
                'بهتر است حداقل 3 یا 4 proof واقعی وارد شود.',
            ),
            'faq_items' => array(
                'هر خط با فرمت Question || Answer نوشته شود.',
                'سوال ها باید city-specific و بر پایه intent واقعی باشند.',
                'پاسخ ها کوتاه، روشن و self-contained باشند.',
            ),
            'local_areas' => array(
                'فقط مناطق یا مسیرهای واقعا مرتبط را وارد کن؛ این بخش نباید dump لینک شود.',
                'فرمت استاندارد: Label|URL',
                'بهتر است محله ها، ناحیه ها یا بازارهای نزدیک و مرتبط را بیاوری.',
            ),
            'trust_text' => array(
                'خلاصه و مطمئن کننده بنویس، نه حقوقی و سنگین.',
                'به ایمنی، گزارش تخلف، تماس و governance محتوا اشاره کن.',
                'این بخش باید trust signal صفحه را تقویت کند.',
            ),
            'cta_primary_label' => array(
                'CTA اصلی باید برای کاربر آماده اقدام باشد؛ معمولا رفتن به listing page.',
            ),
            'cta_secondary_label' => array(
                'CTA دوم معمولا ثبت آگهی یا اقدام مهم دوم است.',
            ),
            'cta_tertiary_label' => array(
                'CTA سوم بهتر است کاربر مردد را به دسته یا مسیر مهم هدایت کند.',
            ),
        );
    }
}

if (!function_exists('bornado_geo_guide_get_meta')) {
    /**
     * Read one guide meta value.
     *
     * @param int    $post_id Post ID.
     * @param string $field   Field slug from bornado_geo_guide_meta_keys().
     * @param mixed  $default Default value.
     * @return mixed
     */
    function bornado_geo_guide_get_meta($post_id, $field, $default = '')
    {
        $keys = bornado_geo_guide_meta_keys();
        if (empty($keys[$field])) {
            return $default;
        }

        $value = get_post_meta((int) $post_id, $keys[$field], true);

        return $value === '' ? $default : $value;
    }
}

if (!function_exists('bornado_geo_guide_get_quality_checklist')) {
    /**
     * Evaluate whether the current guide content satisfies core template quality gates.
     *
     * @param int $post_id Page ID.
     * @return array<int,array<string,mixed>>
     */
    function bornado_geo_guide_get_quality_checklist($post_id)
    {
        $post_id      = (int) $post_id;
        $post         = get_post($post_id);
        $country_id   = (int) bornado_geo_guide_get_meta($post_id, 'country_term_id', 0);
        $city_id      = (int) bornado_geo_guide_get_meta($post_id, 'city_term_id', 0);
        $is_city      = $city_id > 0;
        $hero_intro   = trim((string) bornado_geo_guide_get_meta($post_id, 'hero_intro', ''));
        $market       = trim((string) bornado_geo_guide_get_meta($post_id, 'market_summary', ''));
        $steps        = bornado_geo_guide_parse_lines((string) bornado_geo_guide_get_meta($post_id, 'how_to_steps', ''));
        $proofs       = bornado_geo_guide_parse_lines((string) bornado_geo_guide_get_meta($post_id, 'proof_points', ''));
        $faq_items    = bornado_geo_guide_parse_faq_lines((string) bornado_geo_guide_get_meta($post_id, 'faq_items', ''));
        $areas        = bornado_geo_guide_parse_link_lines((string) bornado_geo_guide_get_meta($post_id, 'local_areas', ''));
        $trust        = trim((string) bornado_geo_guide_get_meta($post_id, 'trust_text', ''));
        $editor_body  = $post instanceof WP_Post ? trim((string) $post->post_content) : '';
        $excerpt      = $post instanceof WP_Post ? trim((string) $post->post_excerpt) : '';

        if (!$is_city) {
            return array(
                array(
                    'label'   => 'کشور انتخاب شده باشد',
                    'ok'      => $country_id > 0,
                    'message' => 'برای اسکلت URL کشور باید country term مشخص باشد.',
                ),
                array(
                    'label'   => 'هاب کشور (بدون شهر) — noindex می‌ماند',
                    'ok'      => $country_id > 0,
                    'message' => 'شهر را خالی بگذارید. این صفحه ایندکس نمی‌شود تا محتوای کشور آماده شود.',
                ),
            );
        }

        return array(
            array(
                'label'   => 'کشور انتخاب شده باشد',
                'ok'      => $country_id > 0,
                'message' => 'برای هر صفحه باید market country مشخص باشد.',
            ),
            array(
                'label'   => 'شهر انتخاب شده باشد',
                'ok'      => $city_id > 0,
                'message' => 'برای city guide باید city term مشخص باشد.',
            ),
            array(
                'label'   => 'Hero intro یا excerpt وجود داشته باشد',
                'ok'      => $hero_intro !== '' || $excerpt !== '',
                'message' => 'صفحه باید در همان اسکرین اول، جواب اولیه روشن بدهد.',
            ),
            array(
                'label'   => 'خلاصه بازار نوشته شده باشد',
                'ok'      => $market !== '',
                'message' => 'market context باید محلی و واقعی باشد.',
            ),
            array(
                'label'   => 'حداقل 3 step برای how-to وجود داشته باشد',
                'ok'      => count($steps) >= 3,
                'message' => 'چطور استفاده کنیم باید عملی، کوتاه و اسکن‌پذیر باشد.',
            ),
            array(
                'label'   => 'حداقل 3 proof point محلی وجود داشته باشد',
                'ok'      => count($proofs) >= 3,
                'message' => 'صفحه بدون local proof به thin content نزدیک می شود.',
            ),
            array(
                'label'   => 'حداقل 4 FAQ وجود داشته باشد',
                'ok'      => count($faq_items) >= 4,
                'message' => 'FAQ باید از intentهای واقعی شهر بیاید.',
            ),
            array(
                'label'   => 'مناطق/مسیرهای مرتبط تعریف شده باشد',
                'ok'      => count($areas) >= 2,
                'message' => 'related markets و internal linking برای این الگو مهم است.',
            ),
            array(
                'label'   => 'Trust block پر شده باشد',
                'ok'      => $trust !== '',
                'message' => 'governance و trust باید واضح و قابل مشاهده باشد.',
            ),
            array(
                'label'   => 'بدنه اصلی صفحه در editor تکمیل شده باشد',
                'ok'      => $editor_body !== '',
                'message' => 'editor body باید narrative و محتوای تکمیلی صفحه را پوشش دهد.',
            ),
        );
    }
}

if (!function_exists('bornado_geo_guide_render_admin_example_note')) {
    /**
     * Render a compact admin-only example hint.
     *
     * @param string $label Example label.
     * @param string $value Example value.
     * @return void
     */
    function bornado_geo_guide_render_admin_example_note($label, $value)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return;
        }
        ?>
        <div class="bornado-geo-guide-admin-note">
            <strong><?php echo esc_html($label); ?>:</strong>
            <div><?php echo nl2br(esc_html($value)); ?></div>
        </div>
        <?php
    }
}

if (!function_exists('bornado_geo_guide_render_field_guides')) {
    /**
     * Render compact guidance list under one admin field.
     *
     * @param string $field Field slug.
     * @return void
     */
    function bornado_geo_guide_render_field_guides($field)
    {
        $guides = bornado_geo_guide_get_field_guides();
        if (empty($guides[$field]) || !is_array($guides[$field])) {
            return;
        }
        ?>
        <ul class="bornado-geo-guide-admin-tips">
            <?php foreach ($guides[$field] as $tip) : ?>
                <li><?php echo esc_html((string) $tip); ?></li>
            <?php endforeach; ?>
        </ul>
        <?php
    }
}

if (!function_exists('bornado_geo_guide_get_post_ad_url')) {
    /**
     * Resolve the post-ad page URL from AdForest options.
     *
     * @return string
     */
    function bornado_geo_guide_get_post_ad_url()
    {
        global $adforest_theme;

        $page_id = !empty($adforest_theme['sb_post_ad_page']) ? (int) $adforest_theme['sb_post_ad_page'] : 0;
        if ($page_id > 0) {
            $page_id = (int) apply_filters('adforest_ad_post_verified_id', $page_id);
            $page_id = (int) apply_filters('adforest_language_page_id', $page_id);
        }

        return $page_id > 0 ? (string) get_permalink($page_id) : home_url('/');
    }
}

if (!function_exists('bornado_geo_guide_resolve_page_url')) {
    /**
     * Resolve a published page URL from common slugs or titles.
     *
     * @param array<int,string> $paths Candidate slugs/paths.
     * @param array<int,string> $titles Candidate page titles.
     * @return string
     */
    function bornado_geo_guide_resolve_page_url($paths = array(), $titles = array())
    {
        foreach ((array) $paths as $path) {
            $path = trim((string) $path, "/ \t\n\r\0\x0B");
            if ($path === '') {
                continue;
            }

            $page = get_page_by_path($path, OBJECT, 'page');
            if ($page instanceof WP_Post && $page->post_status === 'publish') {
                return (string) get_permalink($page);
            }
        }

        foreach ((array) $titles as $title) {
            $title = trim((string) $title);
            if ($title === '') {
                continue;
            }

            $page = get_page_by_title($title, OBJECT, 'page');
            if ($page instanceof WP_Post && $page->post_status === 'publish') {
                return (string) get_permalink($page);
            }
        }

        return '';
    }
}

if (!function_exists('bornado_geo_guide_get_trust_links')) {
    /**
     * Build visible trust/support links for the guide page.
     *
     * @param array<string,mixed> $settings Guide settings.
     * @return array<int,array<string,string>>
     */
    function bornado_geo_guide_get_trust_links($settings)
    {
        $links = array();

        $links[] = array(
            'label' => 'نکات استفاده امن',
            'url'   => '#bornado-guide-trust',
        );

        $report_url = bornado_geo_guide_resolve_page_url(
            array('report-abuse', 'report', 'reports', 'complaint', 'flag-ad', 'gozaresh-takhalof', 'گزارش-تخلف'),
            array('گزارش تخلف', 'گزارش آگهی', 'Report Abuse', 'Report')
        );
        if ($report_url !== '') {
            $links[] = array(
                'label' => 'گزارش تخلف',
                'url'   => $report_url,
            );
        }

        $contact_url = bornado_geo_guide_resolve_page_url(
            array('contact-us', 'contact', 'support', 'tam-as-ba-ma', 'تماس-با-ما'),
            array('تماس با ما', 'ارتباط با ما', 'پشتیبانی', 'Contact Us', 'Support')
        );
        if ($contact_url !== '') {
            $links[] = array(
                'label' => 'تماس با ما',
                'url'   => $contact_url,
            );
        }

        if (!empty($settings['city_listing_url'])) {
            $links[] = array(
                'label' => 'آگهی های این شهر',
                'url'   => (string) $settings['city_listing_url'],
            );
        }

        $post_ad_url = !empty($settings['secondary_cta_url']) ? (string) $settings['secondary_cta_url'] : bornado_geo_guide_get_post_ad_url();
        if ($post_ad_url !== '') {
            $links[] = array(
                'label' => 'ثبت آگهی',
                'url'   => $post_ad_url,
            );
        }

        $seen  = array();
        $clean = array();
        foreach ($links as $link) {
            $label = !empty($link['label']) ? (string) $link['label'] : '';
            $url   = !empty($link['url']) ? (string) $link['url'] : '';
            if ($label === '' || $url === '') {
                continue;
            }

            $key = md5($label . '|' . $url);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $clean[]    = array(
                'label' => $label,
                'url'   => $url,
            );
        }

        return $clean;
    }
}

if (!function_exists('bornado_geo_guide_parse_lines')) {
    /**
     * Normalize a textarea into a clean list of lines.
     *
     * @param string $value Raw textarea value.
     * @return array<int,string>
     */
    function bornado_geo_guide_parse_lines($value)
    {
        if (!is_string($value) || trim($value) === '') {
            return array();
        }

        $lines = preg_split('/\r\n|\r|\n/', $value);
        if (!is_array($lines)) {
            return array();
        }

        $lines = array_map('trim', $lines);
        $lines = array_values(array_filter($lines, static function ($line) {
            return is_string($line) && $line !== '';
        }));

        return $lines;
    }
}

if (!function_exists('bornado_geo_guide_parse_link_lines')) {
    /**
     * Parse newline-delimited `Label|URL` rows for local area pills.
     *
     * @param string $value Raw textarea value.
     * @return array<int,array<string,string>>
     */
    function bornado_geo_guide_parse_link_lines($value)
    {
        $items = array();

        foreach (bornado_geo_guide_parse_lines($value) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            $label = isset($parts[0]) ? (string) $parts[0] : '';
            $url   = isset($parts[1]) ? esc_url_raw($parts[1]) : '';

            if ($label === '') {
                continue;
            }

            $items[] = array(
                'label' => $label,
                'url'   => $url,
            );
        }

        return $items;
    }
}

if (!function_exists('bornado_geo_guide_parse_faq_lines')) {
    /**
     * Parse FAQ rows from `Question || Answer` textarea input.
     *
     * @param string $value Raw textarea value.
     * @return array<int,array<string,string>>
     */
    function bornado_geo_guide_parse_faq_lines($value)
    {
        $items = array();

        foreach (bornado_geo_guide_parse_lines($value) as $line) {
            $parts = preg_split('/\s*\|\|\s*|\s+\|\s+/', $line, 2);
            if (!is_array($parts) || count($parts) < 2) {
                continue;
            }

            $question = trim((string) $parts[0]);
            $answer   = trim((string) $parts[1]);

            if ($question === '' || $answer === '') {
                continue;
            }

            $items[] = array(
                'question' => $question,
                'answer'   => $answer,
            );
        }

        return $items;
    }
}

if (!function_exists('bornado_geo_guide_looks_like_editorial_placeholder')) {
    /**
     * Detect legacy editorial/instructional placeholder copy that should not
     * appear on the public page.
     *
     * @param string $text Candidate text.
     * @return bool
     */
    function bornado_geo_guide_looks_like_editorial_placeholder($text)
    {
        $text = trim((string) $text);
        if ($text === '') {
            return false;
        }

        $needles = array(
            'هدف trust block',
            'این بخش باید به کاربر حس اطمینان بدهد',
            'لینک خشک به قوانین',
            'inventory فعال',
            'شروع از مهم ترین دسته ها',
            'hub محتوایی',
            'intent این بازار',
            'city name swap',
        );

        foreach ($needles as $needle) {
            if ($needle !== '' && mb_strpos($text, $needle) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('bornado_geo_guide_build_semantic_url')) {
    /**
     * Build a public country/city/category semantic URL for guide CTAs.
     *
     * @param WP_Term|null $country_term Country term.
     * @param WP_Term|null $city_term City term.
     * @param WP_Term|null $category_term Category term.
     * @return string
     */
    function bornado_geo_guide_build_semantic_url($country_term = null, $city_term = null, $category_term = null)
    {
        $segments = array();

        if ($country_term instanceof WP_Term) {
            $segments[] = $country_term->slug;
        }

        if ($city_term instanceof WP_Term) {
            $segments[] = $city_term->slug;
        }

        if ($category_term instanceof WP_Term) {
            $ancestor_ids = array_reverse(array_map('intval', get_ancestors((int) $category_term->term_id, 'ad_cats', 'taxonomy')));
            foreach ($ancestor_ids as $ancestor_id) {
                $ancestor = get_term($ancestor_id, 'ad_cats');
                if ($ancestor instanceof WP_Term) {
                    $segments[] = $ancestor->slug;
                }
            }

            $segments[] = $category_term->slug;
        }

        if (empty($segments)) {
            return '';
        }

        return home_url(user_trailingslashit(implode('/', array_map('rawurlencode', $segments))));
    }
}

if (!function_exists('bornado_geo_guide_get_location_terms')) {
    /**
     * Resolve configured country/city terms for a guide page.
     *
     * @param int $post_id Page ID.
     * @return array<string,WP_Term|null>
     */
    function bornado_geo_guide_get_location_terms($post_id)
    {
        $country_term = null;
        $city_term    = null;

        $country_term_id = (int) bornado_geo_guide_get_meta($post_id, 'country_term_id', 0);
        $city_term_id    = (int) bornado_geo_guide_get_meta($post_id, 'city_term_id', 0);

        if ($country_term_id > 0) {
            $term = get_term($country_term_id, 'ad_country');
            if ($term instanceof WP_Term) {
                $country_term = $term;
            }
        }

        if ($city_term_id > 0) {
            $term = get_term($city_term_id, 'ad_country');
            if ($term instanceof WP_Term) {
                $city_term = $term;
            }
        }

        return array(
            'country_term' => $country_term,
            'city_term'    => $city_term,
        );
    }
}

if (!function_exists('bornado_geo_guide_get_location_label')) {
    /**
     * Human-friendly location label for a guide page.
     *
     * @param int $post_id Page ID.
     * @return string
     */
    function bornado_geo_guide_get_location_label($post_id)
    {
        $terms        = bornado_geo_guide_get_location_terms($post_id);
        $country_term = $terms['country_term'];
        $city_term    = $terms['city_term'];

        if ($city_term instanceof WP_Term && $country_term instanceof WP_Term) {
            return $city_term->name . '، ' . $country_term->name;
        }

        if ($country_term instanceof WP_Term) {
            return $country_term->name;
        }

        return trim((string) get_the_title($post_id));
    }
}

if (!function_exists('bornado_geo_guide_get_location_term_id')) {
    /**
     * Deepest location term ID for ad counts.
     *
     * @param int $post_id Page ID.
     * @return int
     */
    function bornado_geo_guide_get_location_term_id($post_id)
    {
        $terms = bornado_geo_guide_get_location_terms($post_id);

        if ($terms['city_term'] instanceof WP_Term) {
            return (int) $terms['city_term']->term_id;
        }

        if ($terms['country_term'] instanceof WP_Term) {
            return (int) $terms['country_term']->term_id;
        }

        return 0;
    }
}

if (!function_exists('bornado_geo_guide_query_ad_count')) {
    /**
     * Count published ads for a location and optional category.
     *
     * @param int $location_term_id Location term ID.
     * @param int $category_term_id Category term ID.
     * @return int
     */
    function bornado_geo_guide_query_ad_count($location_term_id, $category_term_id = 0)
    {
        $location_term_id = (int) $location_term_id;
        $category_term_id = (int) $category_term_id;

        if ($location_term_id < 1) {
            return 0;
        }

        $tax_query = array(
            array(
                'taxonomy'         => 'ad_country',
                'field'            => 'term_id',
                'terms'            => array($location_term_id),
                'include_children' => false,
            ),
        );

        if ($category_term_id > 0) {
            $tax_query[] = array(
                'taxonomy'         => 'ad_cats',
                'field'            => 'term_id',
                'terms'            => array($category_term_id),
                'include_children' => true,
            );
        }

        $query = new WP_Query(array(
            'post_type'              => 'ad_post',
            'post_status'            => 'publish',
            'fields'                 => 'ids',
            'posts_per_page'         => 1,
            'no_found_rows'          => false,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => $tax_query,
        ));

        return (int) $query->found_posts;
    }
}

if (!function_exists('bornado_geo_guide_get_category_summary_map')) {
    /**
     * Short UX/SEO copy for known top-level categories.
     *
     * @return array<int,string>
     */
    function bornado_geo_guide_get_category_summary_map()
    {
        return array(
            339 => 'مناسب برای فرصت های شغلی، جذب نیرو و جست وجوی کار در این بازار.',
            338 => 'مناسب برای اتاق، اجاره، هم خانه و آگهی های پرتقاضای ملکی.',
            341 => 'برای خدمات محلی، آموزش، زیبایی، نظافت و سرویس های تخصصی.',
            342 => 'برای خرید و فروش کالا، وسایل منزل و موارد کاربردی روزمره.',
            340 => 'برای خودرو، موتور و سایر آگهی های مرتبط با وسایل نقلیه.',
            343 => 'برای نیازهای اجتماعی، معرفی، درخواست ها و آگهی های community.',
        );
    }
}

if (!function_exists('bornado_geo_guide_get_featured_categories')) {
    /**
     * Build the featured-categories strip for the guide page.
     *
     * @param int         $post_id Page ID.
     * @param WP_Term|null $country_term Country term.
     * @param WP_Term|null $city_term City term.
     * @param int         $limit Max items.
     * @return array<int,array<string,mixed>>
     */
    function bornado_geo_guide_get_featured_categories($post_id, $country_term, $city_term, $limit = 6)
    {
        $post_id          = (int) $post_id;
        $limit            = max(1, (int) $limit);
        $location_term_id = bornado_geo_guide_get_location_term_id($post_id);
        $cache_key        = 'bornado_geo_guide_categories_' . md5($post_id . '|' . $location_term_id . '|' . $limit);
        $cached           = get_transient($cache_key);

        if (is_array($cached)) {
            return $cached;
        }

        $summary_map = bornado_geo_guide_get_category_summary_map();
        $roots       = get_terms(array(
            'taxonomy'   => 'ad_cats',
            'hide_empty' => false,
            'parent'     => 0,
            'orderby'    => 'term_id',
            'order'      => 'ASC',
        ));

        if (is_wp_error($roots) || empty($roots)) {
            return array();
        }

        $items = array();
        foreach ($roots as $term) {
            if (!($term instanceof WP_Term)) {
                continue;
            }

            $count = bornado_geo_guide_query_ad_count($location_term_id, (int) $term->term_id);
            $items[] = array(
                'term_id'      => (int) $term->term_id,
                'name'         => $term->name,
                'count'        => $count,
                'description'  => !empty($summary_map[(int) $term->term_id]) ? $summary_map[(int) $term->term_id] : 'برای این بخش می توانید آگهی های مرتبط و محلی را سریع تر پیدا کنید.',
                'url'          => bornado_geo_guide_build_semantic_url($country_term, $city_term, $term),
            );
        }

        usort($items, static function ($left, $right) {
            $left_count  = isset($left['count']) ? (int) $left['count'] : 0;
            $right_count = isset($right['count']) ? (int) $right['count'] : 0;

            if ($left_count === $right_count) {
                return strcmp((string) $left['name'], (string) $right['name']);
            }

            return $right_count <=> $left_count;
        });

        $items = array_slice($items, 0, $limit);
        set_transient($cache_key, $items, 15 * MINUTE_IN_SECONDS);

        return $items;
    }
}

if (!function_exists('bornado_geo_guide_get_recent_ads_for_category')) {
    /**
     * Return the latest ads for one top-level category in the current guide location.
     *
     * @param int $post_id Guide page ID.
     * @param int $category_term_id Category term ID.
     * @param int $limit Max ads to return.
     * @return array<int,int>
     */
    function bornado_geo_guide_get_recent_ads_for_category($post_id, $category_term_id, $limit = 10)
    {
        $post_id          = (int) $post_id;
        $category_term_id = (int) $category_term_id;
        $limit            = max(1, (int) $limit);

        if ($post_id < 1 || $category_term_id < 1) {
            return array();
        }

        $terms            = bornado_geo_guide_get_location_terms($post_id);
        $city_term        = $terms['city_term'];
        $country_term     = $terms['country_term'];
        $location_term_id = $city_term instanceof WP_Term ? (int) $city_term->term_id : ($country_term instanceof WP_Term ? (int) $country_term->term_id : 0);
        $include_children = !($city_term instanceof WP_Term);

        if ($location_term_id < 1) {
            return array();
        }

        $cache_key = 'bornado_geo_guide_recent_ads_' . md5($post_id . '|' . $location_term_id . '|' . $category_term_id . '|' . $limit);
        $cached    = get_transient($cache_key);
        if (is_array($cached)) {
            return array_values(array_map('intval', $cached));
        }

        $query = new WP_Query(array(
            'post_type'              => 'ad_post',
            'post_status'            => 'publish',
            'posts_per_page'         => $limit,
            'fields'                 => 'ids',
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'ignore_sticky_posts'    => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'tax_query'              => array(
                array(
                    'taxonomy'         => 'ad_country',
                    'field'            => 'term_id',
                    'terms'            => array($location_term_id),
                    'include_children' => $include_children,
                ),
                array(
                    'taxonomy'         => 'ad_cats',
                    'field'            => 'term_id',
                    'terms'            => array($category_term_id),
                    'include_children' => true,
                ),
            ),
        ));

        $ad_ids = !empty($query->posts) && is_array($query->posts)
            ? array_values(array_map('intval', $query->posts))
            : array();

        set_transient($cache_key, $ad_ids, 10 * MINUTE_IN_SECONDS);

        return $ad_ids;
    }
}

if (!function_exists('bornado_geo_guide_render_ad_card')) {
    /**
     * Render one ad card using the site's existing search/grid card design.
     *
     * @param int $ad_id Ad post ID.
     * @return string
     */
    function bornado_geo_guide_render_ad_card($ad_id)
    {
        $ad_id = (int) $ad_id;
        if ($ad_id < 1 || !function_exists('get_ad_post_details') || !function_exists('adforest_ad_grid_1')) {
            return '';
        }

        $ad_post = get_post($ad_id);
        if (!($ad_post instanceof WP_Post) || $ad_post->post_status !== 'publish') {
            return '';
        }

        $GLOBALS['post'] = $ad_post;
        setup_postdata($ad_post);

        $ad_details = get_ad_post_details($ad_id);
        if (empty($ad_details) || !is_array($ad_details)) {
            wp_reset_postdata();
            return '';
        }

        $first_img          = !empty($ad_details['img']) ? (string) $ad_details['img'] : '';
        $ad_permalink       = !empty($ad_details['ad_link']) ? (string) $ad_details['ad_link'] : get_permalink($ad_id);
        $heart_class        = !empty($ad_details['heart_class']) ? (string) $ad_details['heart_class'] : 'far fa-heart';
        $is_featured        = !empty($ad_details['is_featured']);
        $ad_categories_post = !empty($ad_details['categories']) && is_array($ad_details['categories']) ? $ad_details['categories'] : array();
        $price_html         = !empty($ad_details['price_html']) ? (string) $ad_details['price_html'] : '';
        $title_raw          = !empty($ad_details['ad_title']) ? (string) $ad_details['ad_title'] : get_the_title($ad_id);
        $location_raw       = !empty($ad_details['location']) ? (string) $ad_details['location'] : '';
        $truncated_title    = function_exists('truncate_string') ? truncate_string($title_raw, 40) : $title_raw;
        $truncated_location = function_exists('truncate_string') ? truncate_string($location_raw, 40) : $location_raw;

        $card_html = adforest_ad_grid_1(
            $ad_permalink,
            $first_img,
            $is_featured,
            $ad_categories_post,
            $ad_details,
            $truncated_title,
            $truncated_location,
            $price_html,
            $heart_class
        );

        wp_reset_postdata();

        return (string) $card_html;
    }
}

if (!function_exists('bornado_geo_guide_get_total_count')) {
    /**
     * Total ad count for the configured location.
     *
     * @param int $post_id Page ID.
     * @return int
     */
    function bornado_geo_guide_get_total_count($post_id)
    {
        $location_term_id = bornado_geo_guide_get_location_term_id($post_id);
        $cache_key        = 'bornado_geo_guide_total_' . md5((string) $location_term_id);
        $cached           = get_transient($cache_key);

        if ($cached !== false) {
            return (int) $cached;
        }

        $count = bornado_geo_guide_query_ad_count($location_term_id);
        set_transient($cache_key, $count, 15 * MINUTE_IN_SECONDS);

        return $count;
    }
}

if (!function_exists('bornado_geo_guide_get_settings')) {
    /**
     * Aggregate all template settings and intelligent defaults for one page.
     *
     * @param int $post_id Page ID.
     * @return array<string,mixed>
     */
    function bornado_geo_guide_get_settings($post_id)
    {
        $post_id      = (int) $post_id;
        $post         = get_post($post_id);
        $terms        = bornado_geo_guide_get_location_terms($post_id);
        $country_term = $terms['country_term'];
        $city_term    = $terms['city_term'];
        $location     = bornado_geo_guide_get_location_label($post_id);
        $country_name = $country_term instanceof WP_Term ? $country_term->name : '';
        $city_name    = $city_term instanceof WP_Term ? $city_term->name : '';
        $featured     = bornado_geo_guide_get_featured_categories($post_id, $country_term, $city_term, 6);
        $total_count  = bornado_geo_guide_get_total_count($post_id);

        $hero_intro = trim((string) bornado_geo_guide_get_meta($post_id, 'hero_intro', ''));
        if (bornado_geo_guide_looks_like_editorial_placeholder($hero_intro)) {
            $hero_intro = '';
        }
        if ($hero_intro === '' && $post instanceof WP_Post) {
            $hero_intro = trim((string) $post->post_excerpt);
        }
        if ($hero_intro === '') {
            $top_names = array();
            foreach ($featured as $item) {
                if (!empty($item['name'])) {
                    $top_names[] = (string) $item['name'];
                }
            }
            $hero_intro = sprintf(
                'اگر به دنبال آگهی های ایرانیان در %s هستید، این صفحه کمک می کند سریع تر به مهم ترین دسته ها، آگهی های فعال، و مسیر ثبت آگهی در Bornado برسید.%s',
                $location,
                !empty($top_names) ? ' مهم ترین دسته های این بازار شامل ' . implode('، ', array_slice($top_names, 0, 3)) . ' است.' : ''
            );
        }

        $market_summary = trim((string) bornado_geo_guide_get_meta($post_id, 'market_summary', ''));
        if (bornado_geo_guide_looks_like_editorial_placeholder($market_summary)) {
            $market_summary = '';
        }
        if ($market_summary === '') {
            $market_summary = sprintf(
                '%s یکی از بازارهای فعال برنادو است و این صفحه برای آن طراحی شده تا کاربر فارسی زبان بتواند مهم ترین مسیرهای جست وجو، دسته های پرتقاضا، و مسیرهای اقدام را سریع تر پیدا کند. در این بازار، کیفیت دسته بندی، وضوح عنوان آگهی، و دسترسی سریع به آگهی های جدید نقش مهمی در تجربه کاربری و رتبه گیری صفحه دارد.',
                $location
            );
        }

        $how_to_steps = bornado_geo_guide_parse_lines((string) bornado_geo_guide_get_meta($post_id, 'how_to_steps', ''));
        if (empty($how_to_steps)) {
            $how_to_steps = array(
                'از دسته مناسب شروع کنید و اگر هدف شما مشخص است، مستقیم وارد همان بخش شوید.',
                'آگهی های جدیدتر را زودتر بررسی کنید تا فرصت های بهتری را از دست ندهید.',
                'قبل از تماس، موقعیت، نوع آگهی و جزئیات کلیدی را دقیق مرور کنید.',
                'برای ثبت آگهی، عنوان روشن، دسته بندی درست و توضیح مختصر اما دقیق بنویسید.',
                'در ارتباط با آگهی دهنده، نکات ایمنی و حرفه ای را رعایت کنید.',
            );
        }

        $proof_points = bornado_geo_guide_parse_lines((string) bornado_geo_guide_get_meta($post_id, 'proof_points', ''));
        if (empty($proof_points)) {
            $proof_points = array();
            if ($total_count > 0) {
                $proof_points[] = number_format_i18n($total_count) . ' آگهی فعال در این بازار ثبت شده است.';
            }
            foreach (array_slice($featured, 0, 3) as $item) {
                $count = !empty($item['count']) ? (int) $item['count'] : 0;
                if ($count > 0) {
                    $proof_points[] = sprintf(
                        'دسته %s در این بازار با %s آگهی فعال، یکی از مهم ترین مسیرهای جست وجو است.',
                        (string) $item['name'],
                        number_format_i18n($count)
                    );
                }
            }
        }

        $faq_items = bornado_geo_guide_parse_faq_lines((string) bornado_geo_guide_get_meta($post_id, 'faq_items', ''));
        if (empty($faq_items)) {
            $primary_category = !empty($featured[0]['name']) ? (string) $featured[0]['name'] : 'دسته های اصلی';
            $faq_items = array(
                array(
                    'question' => sprintf('بهترین دسته برای شروع جست وجو در %s کدام است؟', $location),
                    'answer'   => sprintf('اگر هنوز مطمئن نیستید از کجا شروع کنید، بهتر است ابتدا مهم ترین دسته های فعال این بازار مثل %s را بررسی کنید و بعد وارد آگهی های جدیدتر شوید.', $primary_category),
                ),
                array(
                    'question' => sprintf('آیا از این صفحه می توان مستقیم وارد آگهی های %s شد؟', $location),
                    'answer'   => 'بله. این صفحه برای این ساخته شده که سریع تر دسته مناسب را پیدا کنید و از همان جا وارد آگهی های فعال و مرتبط شوید.',
                ),
                array(
                    'question' => sprintf('برای ثبت آگهی در %s چه چیزی بیشترین اهمیت را دارد؟', $location),
                    'answer'   => 'عنوان شفاف، انتخاب دسته بندی درست، ذکر موقعیت محلی و نوشتن توضیح کوتاه اما دقیق، از مهم ترین عوامل دیده شدن بهتر آگهی در این بازار است.',
                ),
                array(
                    'question' => 'فرق این صفحه با صفحه لیست آگهی چیست؟',
                    'answer'   => 'این صفحه بازار را توضیح می دهد و مسیر درست را نشان می دهد؛ اما صفحه آگهی ها برای دیدن نتایج فعال، فیلتر کردن و بررسی جزئیات هر آگهی است.',
                ),
            );
        }

        $local_areas = bornado_geo_guide_parse_link_lines((string) bornado_geo_guide_get_meta($post_id, 'local_areas', ''));
        $trust_text  = trim((string) bornado_geo_guide_get_meta($post_id, 'trust_text', ''));
        if (bornado_geo_guide_looks_like_editorial_placeholder($trust_text)) {
            $trust_text = '';
        }
        if ($trust_text === '') {
            $trust_text = 'قبل از تماس، خرید، اجاره یا قرار ملاقات، جزئیات آگهی را کامل بررسی کنید و اگر موردی مبهم بود سوال بپرسید. در آگهی های مرتبط با کار، املاک، خدمات و خرید و فروش، شفاف بودن عنوان، قیمت، موقعیت و شرایط همکاری اهمیت زیادی دارد. اگر هم موردی مشکوک دیدید، از مسیرهای پشتیبانی یا گزارش موجود در سایت استفاده کنید.';
        }

        $city_listing_url    = bornado_geo_guide_build_semantic_url($country_term, $city_term);
        $country_listing_url = bornado_geo_guide_build_semantic_url($country_term, null);
        $post_ad_url         = bornado_geo_guide_get_post_ad_url();
        $tertiary_url        = !empty($featured[0]['url']) ? (string) $featured[0]['url'] : $country_listing_url;
        $tertiary_label      = !empty($featured[0]['name']) ? 'مشاهده ' . (string) $featured[0]['name'] . ' در ' . $location : 'مشاهده دسته های اصلی';

        $settings = array(
            'post'                  => $post,
            'location'              => $location,
            'country_name'          => $country_name,
            'city_name'             => $city_name,
            'country_term'          => $country_term,
            'city_term'             => $city_term,
            'hero_intro'            => $hero_intro,
            'market_summary'        => $market_summary,
            'how_to_steps'          => $how_to_steps,
            'proof_points'          => $proof_points,
            'faq_items'             => $faq_items,
            'local_areas'           => $local_areas,
            'trust_text'            => $trust_text,
            'featured_categories'   => $featured,
            'total_count'           => $total_count,
            'primary_cta_label'     => trim((string) bornado_geo_guide_get_meta($post_id, 'cta_primary_label', '')) !== ''
                ? trim((string) bornado_geo_guide_get_meta($post_id, 'cta_primary_label', ''))
                : sprintf('دیدن آگهی های %s', $location),
            'primary_cta_url'       => trim((string) bornado_geo_guide_get_meta($post_id, 'cta_primary_url', '')) !== ''
                ? esc_url((string) bornado_geo_guide_get_meta($post_id, 'cta_primary_url', ''))
                : $city_listing_url,
            'secondary_cta_label'   => trim((string) bornado_geo_guide_get_meta($post_id, 'cta_secondary_label', '')) !== ''
                ? trim((string) bornado_geo_guide_get_meta($post_id, 'cta_secondary_label', ''))
                : sprintf('ثبت آگهی در %s', $location),
            'secondary_cta_url'     => trim((string) bornado_geo_guide_get_meta($post_id, 'cta_secondary_url', '')) !== ''
                ? esc_url((string) bornado_geo_guide_get_meta($post_id, 'cta_secondary_url', ''))
                : $post_ad_url,
            'tertiary_cta_label'    => trim((string) bornado_geo_guide_get_meta($post_id, 'cta_tertiary_label', '')) !== ''
                ? trim((string) bornado_geo_guide_get_meta($post_id, 'cta_tertiary_label', ''))
                : $tertiary_label,
            'tertiary_cta_url'      => trim((string) bornado_geo_guide_get_meta($post_id, 'cta_tertiary_url', '')) !== ''
                ? esc_url((string) bornado_geo_guide_get_meta($post_id, 'cta_tertiary_url', ''))
                : $tertiary_url,
            'city_listing_url'      => $city_listing_url,
            'country_listing_url'   => $country_listing_url,
            'content'               => $post instanceof WP_Post ? (string) $post->post_content : '',
            'published_date'        => $post instanceof WP_Post ? get_the_date('c', $post) : '',
            'modified_date'         => $post instanceof WP_Post ? get_the_modified_date('c', $post) : '',
        );

        $settings['trust_links'] = bornado_geo_guide_get_trust_links($settings);

        return $settings;
    }
}

if (!function_exists('bornado_geo_guide_render_meta_box')) {
    /**
     * Render page-template controls in the editor.
     *
     * @param WP_Post $post Page post object.
     * @return void
     */
    function bornado_geo_guide_render_meta_box($post)
    {
        wp_nonce_field('bornado_geo_guide_save', 'bornado_geo_guide_nonce');

        $country_id  = (int) bornado_geo_guide_get_meta($post->ID, 'country_term_id', 0);
        $city_id     = (int) bornado_geo_guide_get_meta($post->ID, 'city_term_id', 0);
        $example     = bornado_geo_guide_get_london_admin_example();
        $checklist   = bornado_geo_guide_get_quality_checklist((int) $post->ID);
        $permalink   = get_permalink($post);
        ?>
        <div class="bornado-geo-guide-admin" data-template-slug="<?php echo esc_attr(bornado_geo_guide_template_slug()); ?>">
            <p>
                این محتوا از برگه‌های معمولی جداست. اسلاگ همین پست باید فقط شهر یا کشور باشد
                (<code>london</code> یا <code>uk</code>). مسیر کامل از والد ساخته می‌شود:
                <code>/iranians/uk/london/</code>
            </p>
            <?php if (is_string($permalink) && $permalink !== '') : ?>
                <p>
                    <strong>URL فعلی:</strong>
                    <code><?php echo esc_html($permalink); ?></code>
                </p>
            <?php endif; ?>
            <details class="bornado-geo-guide-admin-sample" open>
                <summary><?php esc_html_e('Reference sample for United Kingdom > London', 'adforest'); ?></summary>
                <div class="bornado-geo-guide-admin-sample__content">
                    <?php bornado_geo_guide_render_admin_example_note(__('Suggested title', 'adforest'), (string) $example['title']); ?>
                    <?php bornado_geo_guide_render_admin_example_note(__('Suggested excerpt / hero intro', 'adforest'), (string) $example['excerpt']); ?>
                    <?php bornado_geo_guide_render_admin_example_note(__('Suggested editor body', 'adforest'), (string) $example['content']); ?>
                    <p class="description">
                        <?php esc_html_e('Use this as a professional benchmark so the editor can see exactly what a good guide page should look like before filling the fields.', 'adforest'); ?>
                    </p>
                    <p>
                        <button type="button" class="button button-secondary" id="bornado-geo-guide-apply-london-sample">
                            <?php esc_html_e('Insert London sample into empty fields', 'adforest'); ?>
                        </button>
                    </p>
                </div>
            </details>
            <details class="bornado-geo-guide-admin-sample" open>
                <summary><?php esc_html_e('Quality checklist based on the template standard', 'adforest'); ?></summary>
                <div class="bornado-geo-guide-admin-sample__content">
                    <ul class="bornado-geo-guide-admin-checklist">
                        <?php foreach ($checklist as $item) : ?>
                            <li class="<?php echo !empty($item['ok']) ? 'is-ok' : 'is-missing'; ?>">
                                <strong><?php echo !empty($item['ok']) ? esc_html__('OK', 'adforest') : esc_html__('Missing', 'adforest'); ?>:</strong>
                                <?php echo esc_html((string) $item['label']); ?>
                                <div><?php echo esc_html((string) $item['message']); ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </details>
            <script type="application/json" id="bornado-geo-guide-sample-data"><?php echo wp_json_encode($example, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
        </div>
        <p>
            <label for="bornado_geo_guide_country_term_id"><strong><?php esc_html_e('Country term', 'adforest'); ?></strong></label>
            <?php
            wp_dropdown_categories(array(
                'taxonomy'          => 'ad_country',
                'hide_empty'        => false,
                'name'              => 'bornado_geo_guide_country_term_id',
                'id'                => 'bornado_geo_guide_country_term_id',
                'selected'          => $country_id,
                'show_option_none'  => __('Select a country', 'adforest'),
                'option_none_value' => '0',
                'class'             => 'widefat',
                'value_field'       => 'term_id',
                'depth'             => 1,
            ));
            ?>
            <small><?php esc_html_e('Professional sample: United Kingdom', 'adforest'); ?></small>
        </p>
        <p>
            <label for="bornado_geo_guide_city_term_id"><strong><?php esc_html_e('City term', 'adforest'); ?></strong></label>
            <?php
            wp_dropdown_categories(array(
                'taxonomy'          => 'ad_country',
                'hide_empty'        => false,
                'name'              => 'bornado_geo_guide_city_term_id',
                'id'                => 'bornado_geo_guide_city_term_id',
                'selected'          => $city_id,
                'show_option_none'  => __('Select a city (optional)', 'adforest'),
                'option_none_value' => '0',
                'class'             => 'widefat',
                'value_field'       => 'term_id',
            ));
            ?>
            <small><?php esc_html_e('Professional sample: London', 'adforest'); ?></small>
        </p>
        <p>
            <label for="bornado_geo_guide_hero_intro"><strong><?php esc_html_e('Hero intro override', 'adforest'); ?></strong></label>
            <textarea class="widefat" rows="4" id="bornado_geo_guide_hero_intro" name="bornado_geo_guide_hero_intro" placeholder="<?php echo esc_attr((string) $example['hero_intro']); ?>"><?php echo esc_textarea((string) bornado_geo_guide_get_meta($post->ID, 'hero_intro', '')); ?></textarea>
            <small><?php esc_html_e('If left empty, the page excerpt is used first, then an automatic intro is generated.', 'adforest'); ?></small>
            <?php bornado_geo_guide_render_field_guides('hero_intro'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_market_summary"><strong><?php esc_html_e('Market summary', 'adforest'); ?></strong></label>
            <textarea class="widefat" rows="5" id="bornado_geo_guide_market_summary" name="bornado_geo_guide_market_summary" placeholder="<?php echo esc_attr((string) $example['market_summary']); ?>"><?php echo esc_textarea((string) bornado_geo_guide_get_meta($post->ID, 'market_summary', '')); ?></textarea>
            <?php bornado_geo_guide_render_field_guides('market_summary'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_how_to_steps"><strong><?php esc_html_e('How-to steps', 'adforest'); ?></strong></label>
            <textarea class="widefat" rows="5" id="bornado_geo_guide_how_to_steps" name="bornado_geo_guide_how_to_steps" placeholder="<?php echo esc_attr((string) $example['how_to_steps']); ?>"><?php echo esc_textarea((string) bornado_geo_guide_get_meta($post->ID, 'how_to_steps', '')); ?></textarea>
            <small><?php esc_html_e('One step per line.', 'adforest'); ?></small>
            <?php bornado_geo_guide_render_field_guides('how_to_steps'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_proof_points"><strong><?php esc_html_e('Local proof points', 'adforest'); ?></strong></label>
            <textarea class="widefat" rows="5" id="bornado_geo_guide_proof_points" name="bornado_geo_guide_proof_points" placeholder="<?php echo esc_attr((string) $example['proof_points']); ?>"><?php echo esc_textarea((string) bornado_geo_guide_get_meta($post->ID, 'proof_points', '')); ?></textarea>
            <small><?php esc_html_e('One proof point per line.', 'adforest'); ?></small>
            <?php bornado_geo_guide_render_field_guides('proof_points'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_faq_items"><strong><?php esc_html_e('FAQ items', 'adforest'); ?></strong></label>
            <textarea class="widefat" rows="6" id="bornado_geo_guide_faq_items" name="bornado_geo_guide_faq_items" placeholder="<?php echo esc_attr((string) $example['faq_items']); ?>"><?php echo esc_textarea((string) bornado_geo_guide_get_meta($post->ID, 'faq_items', '')); ?></textarea>
            <small><?php esc_html_e('One FAQ per line using: Question || Answer', 'adforest'); ?></small>
            <?php bornado_geo_guide_render_field_guides('faq_items'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_local_areas"><strong><?php esc_html_e('Local areas / related markets', 'adforest'); ?></strong></label>
            <textarea class="widefat" rows="5" id="bornado_geo_guide_local_areas" name="bornado_geo_guide_local_areas" placeholder="<?php echo esc_attr((string) $example['local_areas']); ?>"><?php echo esc_textarea((string) bornado_geo_guide_get_meta($post->ID, 'local_areas', '')); ?></textarea>
            <small><?php esc_html_e('One item per line. Use Label|URL for linked pills or just Label for text-only pills.', 'adforest'); ?></small>
            <?php bornado_geo_guide_render_field_guides('local_areas'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_trust_text"><strong><?php esc_html_e('Trust block text', 'adforest'); ?></strong></label>
            <textarea class="widefat" rows="4" id="bornado_geo_guide_trust_text" name="bornado_geo_guide_trust_text" placeholder="<?php echo esc_attr((string) $example['trust_text']); ?>"><?php echo esc_textarea((string) bornado_geo_guide_get_meta($post->ID, 'trust_text', '')); ?></textarea>
            <?php bornado_geo_guide_render_field_guides('trust_text'); ?>
        </p>
        <hr />
        <p><strong><?php esc_html_e('CTA overrides (optional)', 'adforest'); ?></strong></p>
        <p>
            <label for="bornado_geo_guide_cta_primary_label"><?php esc_html_e('Primary CTA label', 'adforest'); ?></label>
            <input class="widefat" type="text" id="bornado_geo_guide_cta_primary_label" name="bornado_geo_guide_cta_primary_label" value="<?php echo esc_attr((string) bornado_geo_guide_get_meta($post->ID, 'cta_primary_label', '')); ?>" placeholder="<?php echo esc_attr((string) $example['cta_primary_label']); ?>" />
            <?php bornado_geo_guide_render_field_guides('cta_primary_label'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_cta_primary_url"><?php esc_html_e('Primary CTA URL', 'adforest'); ?></label>
            <input class="widefat" type="url" id="bornado_geo_guide_cta_primary_url" name="bornado_geo_guide_cta_primary_url" value="<?php echo esc_attr((string) bornado_geo_guide_get_meta($post->ID, 'cta_primary_url', '')); ?>" placeholder="<?php echo esc_attr((string) $example['cta_primary_url']); ?>" />
        </p>
        <p>
            <label for="bornado_geo_guide_cta_secondary_label"><?php esc_html_e('Secondary CTA label', 'adforest'); ?></label>
            <input class="widefat" type="text" id="bornado_geo_guide_cta_secondary_label" name="bornado_geo_guide_cta_secondary_label" value="<?php echo esc_attr((string) bornado_geo_guide_get_meta($post->ID, 'cta_secondary_label', '')); ?>" placeholder="<?php echo esc_attr((string) $example['cta_secondary_label']); ?>" />
            <?php bornado_geo_guide_render_field_guides('cta_secondary_label'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_cta_secondary_url"><?php esc_html_e('Secondary CTA URL', 'adforest'); ?></label>
            <input class="widefat" type="url" id="bornado_geo_guide_cta_secondary_url" name="bornado_geo_guide_cta_secondary_url" value="<?php echo esc_attr((string) bornado_geo_guide_get_meta($post->ID, 'cta_secondary_url', '')); ?>" placeholder="<?php echo esc_attr((string) $example['cta_secondary_url']); ?>" />
        </p>
        <p>
            <label for="bornado_geo_guide_cta_tertiary_label"><?php esc_html_e('Tertiary CTA label', 'adforest'); ?></label>
            <input class="widefat" type="text" id="bornado_geo_guide_cta_tertiary_label" name="bornado_geo_guide_cta_tertiary_label" value="<?php echo esc_attr((string) bornado_geo_guide_get_meta($post->ID, 'cta_tertiary_label', '')); ?>" placeholder="<?php echo esc_attr((string) $example['cta_tertiary_label']); ?>" />
            <?php bornado_geo_guide_render_field_guides('cta_tertiary_label'); ?>
        </p>
        <p>
            <label for="bornado_geo_guide_cta_tertiary_url"><?php esc_html_e('Tertiary CTA URL', 'adforest'); ?></label>
            <input class="widefat" type="url" id="bornado_geo_guide_cta_tertiary_url" name="bornado_geo_guide_cta_tertiary_url" value="<?php echo esc_attr((string) bornado_geo_guide_get_meta($post->ID, 'cta_tertiary_url', '')); ?>" placeholder="<?php echo esc_attr((string) $example['cta_tertiary_url']); ?>" />
        </p>
        <?php
    }
}

if (!function_exists('bornado_geo_guide_add_meta_box')) {
    /**
     * Register the geo-guide controls on page edit screens.
     *
     * @return void
     */
    function bornado_geo_guide_add_meta_box()
    {
        if (!function_exists('bornado_geo_guide_post_type')) {
            return;
        }

        add_meta_box(
            'bornado-geo-guide-template',
            __('Bornado Geo Guide Template', 'adforest'),
            'bornado_geo_guide_render_meta_box',
            bornado_geo_guide_post_type(),
            'normal',
            'high'
        );
    }
}
add_action('add_meta_boxes', 'bornado_geo_guide_add_meta_box');

if (!function_exists('bornado_geo_guide_enqueue_admin_assets')) {
    /**
     * Load admin helpers only on Iranians guide screens.
     *
     * @param string $hook_suffix Current admin page hook.
     * @return void
     */
    function bornado_geo_guide_enqueue_admin_assets($hook_suffix)
    {
        if (!in_array($hook_suffix, array('post.php', 'post-new.php'), true)) {
            return;
        }

        $screen = get_current_screen();
        $post_type = $screen instanceof WP_Screen ? (string) $screen->post_type : '';
        if (!function_exists('bornado_geo_guide_post_type') || $post_type !== bornado_geo_guide_post_type()) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_register_script('bornado-geo-guide-admin', '', array('jquery'), false, true);
        wp_enqueue_script('bornado-geo-guide-admin');

        $js = "
        (function($){
            function applySampleToEmptyFields() {
                var sampleNode = document.getElementById('bornado-geo-guide-sample-data');
                if (!sampleNode) {
                    return;
                }
                var sample = {};
                try {
                    sample = JSON.parse(sampleNode.textContent || '{}');
                } catch (error) {
                    sample = {};
                }
                var map = {
                    '#bornado_geo_guide_country_term_id': sample.country_term_id || '',
                    '#bornado_geo_guide_city_term_id': sample.city_term_id || '',
                    '#bornado_geo_guide_hero_intro': sample.hero_intro || '',
                    '#bornado_geo_guide_market_summary': sample.market_summary || '',
                    '#bornado_geo_guide_how_to_steps': sample.how_to_steps || '',
                    '#bornado_geo_guide_proof_points': sample.proof_points || '',
                    '#bornado_geo_guide_faq_items': sample.faq_items || '',
                    '#bornado_geo_guide_local_areas': sample.local_areas || '',
                    '#bornado_geo_guide_trust_text': sample.trust_text || '',
                    '#bornado_geo_guide_cta_primary_label': sample.cta_primary_label || '',
                    '#bornado_geo_guide_cta_primary_url': sample.cta_primary_url || '',
                    '#bornado_geo_guide_cta_secondary_label': sample.cta_secondary_label || '',
                    '#bornado_geo_guide_cta_secondary_url': sample.cta_secondary_url || '',
                    '#bornado_geo_guide_cta_tertiary_label': sample.cta_tertiary_label || '',
                    '#bornado_geo_guide_cta_tertiary_url': sample.cta_tertiary_url || ''
                };
                Object.keys(map).forEach(function(selector){
                    var field = document.querySelector(selector);
                    var value = map[selector];
                    if (!field || value === '') {
                        return;
                    }
                    if (field.tagName === 'SELECT') {
                        if (String(field.value || '0') === '0') {
                            field.value = String(value);
                        }
                        return;
                    }
                    if (String(field.value || '').trim() === '') {
                        field.value = String(value);
                    }
                });
                var excerpt = document.getElementById('excerpt');
                if (excerpt && String(excerpt.value || '').trim() === '' && sample.excerpt) {
                    excerpt.value = String(sample.excerpt);
                }
                var title = document.getElementById('title');
                if (title && String(title.value || '').trim() === '' && sample.title) {
                    title.value = String(sample.title);
                }
                var content = document.getElementById('content');
                if (content && String(content.value || '').trim() === '' && sample.content) {
                    content.value = String(sample.content);
                }
            }
            $(document).ready(function(){
                $(document).on('click', '#bornado-geo-guide-apply-london-sample', function(event){
                    event.preventDefault();
                    applySampleToEmptyFields();
                });
            });
        })(jQuery);
        ";
        wp_add_inline_script('bornado-geo-guide-admin', $js);

        $css = '
        #bornado-geo-guide-template .bornado-geo-guide-admin-note {
            margin: 8px 0;
            padding: 10px 12px;
            background: #f6f8fb;
            border: 1px solid #e0e5ec;
            border-radius: 8px;
            line-height: 1.7;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-sample {
            margin: 12px 0 16px;
            border: 1px solid #d7dee8;
            border-radius: 10px;
            background: #fff;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-sample summary {
            cursor: pointer;
            padding: 12px 14px;
            font-weight: 700;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-sample__content {
            padding: 0 14px 14px;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-tips {
            margin: 8px 0 0;
            padding-right: 18px;
            color: #475569;
            line-height: 1.8;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-checklist {
            margin: 0;
            padding-right: 18px;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-checklist li {
            margin: 0 0 10px;
            line-height: 1.8;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-checklist li.is-ok strong {
            color: #0f8a3b;
        }
        #bornado-geo-guide-template .bornado-geo-guide-admin-checklist li.is-missing strong {
            color: #b42318;
        }';
        wp_register_style('bornado-geo-guide-admin', false);
        wp_enqueue_style('bornado-geo-guide-admin');
        wp_add_inline_style('bornado-geo-guide-admin', $css);
    }
}
add_action('admin_enqueue_scripts', 'bornado_geo_guide_enqueue_admin_assets');

if (!function_exists('bornado_geo_guide_save_meta_box')) {
    /**
     * Persist guide fields.
     *
     * @param int $post_id Page ID.
     * @return void
     */
    function bornado_geo_guide_save_meta_box($post_id)
    {
        if (!isset($_POST['bornado_geo_guide_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bornado_geo_guide_nonce'])), 'bornado_geo_guide_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $post = get_post($post_id);
        if (!($post instanceof WP_Post)) {
            return;
        }

        $is_guide_cpt = function_exists('bornado_geo_guide_is_guide_post') && bornado_geo_guide_is_guide_post($post_id);
        if (!$is_guide_cpt) {
            return;
        }

        $keys = bornado_geo_guide_meta_keys();
        $textareas = array('hero_intro', 'market_summary', 'how_to_steps', 'proof_points', 'faq_items', 'local_areas', 'trust_text');
        $urls = array('cta_primary_url', 'cta_secondary_url', 'cta_tertiary_url');
        $ints = array('country_term_id', 'city_term_id');

        foreach ($keys as $field => $meta_key) {
            $request_key = 'bornado_geo_guide_' . $field;

            if (!isset($_POST[$request_key])) {
                delete_post_meta($post_id, $meta_key);
                continue;
            }

            $raw_value = wp_unslash($_POST[$request_key]);

            if (in_array($field, $ints, true)) {
                $value = max(0, (int) $raw_value);
                if ($value > 0) {
                    update_post_meta($post_id, $meta_key, $value);
                } else {
                    delete_post_meta($post_id, $meta_key);
                }
                continue;
            }

            if (in_array($field, $urls, true)) {
                $value = esc_url_raw((string) $raw_value);
            } elseif (in_array($field, $textareas, true)) {
                $value = trim((string) $raw_value);
            } else {
                $value = sanitize_text_field((string) $raw_value);
            }

            if ($value !== '') {
                update_post_meta($post_id, $meta_key, $value);
            } else {
                delete_post_meta($post_id, $meta_key);
            }
        }
    }
}
if (function_exists('bornado_geo_guide_post_type')) {
    add_action('save_post_' . bornado_geo_guide_post_type(), 'bornado_geo_guide_save_meta_box');
}

if (!function_exists('bornado_geo_guide_enqueue_assets')) {
    /**
     * Load template CSS only on guide pages.
     *
     * @return void
     */
    function bornado_geo_guide_enqueue_assets()
    {
        if (is_admin() || !bornado_geo_guide_is_template()) {
            return;
        }

        $css_path = get_stylesheet_directory() . '/assets/css/bornado-geo-guide.css';
        if (!file_exists($css_path)) {
            return;
        }

        $deps = function_exists('bornado_get_theme_style_handles')
            ? bornado_get_theme_style_handles()
            : array();

        wp_enqueue_style(
            'bornado-geo-guide',
            get_stylesheet_directory_uri() . '/assets/css/bornado-geo-guide.css',
            $deps,
            (string) filemtime($css_path)
        );

        wp_register_script('bornado-geo-guide-slider', '', array(), (string) filemtime($css_path), true);
        wp_enqueue_script('bornado-geo-guide-slider');
        wp_add_inline_script(
            'bornado-geo-guide-slider',
            "(function(){
                function initGuideSlider(root) {
                    var track = root.querySelector('[data-bornado-guide-slider-track]');
                    var prev = root.querySelector('[data-bornado-guide-slider-prev]');
                    var next = root.querySelector('[data-bornado-guide-slider-next]');
                    var viewport = root.querySelector('.bornado-geo-guide__slider-viewport');

                    if (!track || !prev || !next || !viewport) {
                        return;
                    }

                    function getStep() {
                        return viewport.clientWidth;
                    }

                    function getMaxScroll() {
                        return Math.max(0, track.scrollWidth - viewport.clientWidth);
                    }

                    function updateButtons() {
                        var max = getMaxScroll();
                        var left = track.scrollLeft;
                        prev.disabled = left <= 4;
                        next.disabled = left >= (max - 4);
                    }

                    prev.addEventListener('click', function(){
                        track.scrollBy({ left: -getStep(), behavior: 'smooth' });
                    });

                    next.addEventListener('click', function(){
                        track.scrollBy({ left: getStep(), behavior: 'smooth' });
                    });

                    track.addEventListener('scroll', updateButtons, { passive: true });
                    window.addEventListener('resize', updateButtons);
                    updateButtons();
                }

                document.addEventListener('DOMContentLoaded', function(){
                    document.querySelectorAll('[data-bornado-guide-slider]').forEach(initGuideSlider);
                });
            })();",
            'after'
        );
    }
}
add_action('wp_enqueue_scripts', 'bornado_geo_guide_enqueue_assets', 205);

if (!function_exists('bornado_geo_guide_add_body_class')) {
    /**
     * Add a template body class for scoped styling.
     *
     * @param array<int,string> $classes Existing classes.
     * @return array<int,string>
     */
    function bornado_geo_guide_add_body_class($classes)
    {
        if (bornado_geo_guide_is_template()) {
            $classes[] = 'bornado-geo-guide-view';
        }

        return $classes;
    }
}
add_filter('body_class', 'bornado_geo_guide_add_body_class');

if (!function_exists('bornado_geo_guide_get_breadcrumb_schema')) {
    /**
     * Build breadcrumb list items from the page tree.
     *
     * @param int    $post_id Page ID.
     * @param string $page_url Canonical page URL.
     * @return array<int,array<string,mixed>>
     */
    function bornado_geo_guide_get_breadcrumb_schema($post_id, $page_url)
    {
        $post_id = (int) $post_id;
        $items   = array();
        $position = 1;

        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => get_bloginfo('name'),
            'item'     => home_url('/'),
        );

        $ancestors = array_reverse(array_map('intval', get_post_ancestors($post_id)));
        foreach ($ancestors as $ancestor_id) {
            $title = get_the_title($ancestor_id);
            $url   = get_permalink($ancestor_id);
            if ($title && $url) {
                $items[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position++,
                    'name'     => $title,
                    'item'     => $url,
                );
            }
        }

        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => get_the_title($post_id),
            'item'     => $page_url,
        );

        return $items;
    }
}

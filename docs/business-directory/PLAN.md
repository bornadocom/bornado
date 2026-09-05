# پلن دایرکتوری پایدار کسب‌وکار Bornado

تاریخ: ۵ سپتامبر ۲۰۲۶  
وضعیت: تصمیم محصولی قفل شده؛ شروع فاز ۱ فقط پس از بستن قراردادهای P0 همین سند  
مخاطب: اجرا، سئو، محصول

## ۱. تصمیم قفل‌شده

Bornado برای بخش کسب‌وکار یک **دایرکتوری پایدار** می‌سازد، نه یک دسته جدید داخل آگهی‌ها.

- هویت کسب‌وکار یک موجودیت ماندگار است.
- آگهی همان پیشنهاد موقت می‌ماند (`ad_post`).
- یک کسب‌وکار می‌تواند بعداً به چند آگهی وصل شود.
- `sb-directory` برای این کار استفاده نمی‌شود.
- هیچ‌کدام از verticalهای فعلی آگهی بازنویسی نمی‌شوند.

هدف سئو و هوش مصنوعی: ساختن موجودیت محلی قابل استناد، نه ساختن هزاران صفحه ترکیب‌شده خالی.

## ۲. مسئله‌ای که این بخش حل می‌کند

سایت امروز سه لایه جدا دارد:

| لایه | موجودیت | نمونه URL | نیت |
|---|---|---|---|
| آگهی و جستجو | `ad_post` | `/uk/london/property/` و `/ad/{hash}/{slug}` | خرید، اجاره، استخدام، خدمت موردی |
| راهنمای جامعه | `bornado_geo_guide` | `/iranians/uk/london/` | شناخت جامعه ایرانی آن شهر |
| دایرکتوری کسب‌وکار | هنوز وجود ندارد | باید ساخته شود | پیدا کردن کسب‌وکار ایرانی پایدار |

جستجوهایی مثل «نانوایی ایرانی لندن» یا «وکیل مهاجرت تورنتو» به لایه سوم نیاز دارند. اگر همین نیت را داخل `ad_post` بگذاریم:

- صفحه با انقضای آگهی می‌میرد.
- فروشنده در اسکیما `Person` است، نه کسب‌وکار.
- مدل‌ها فکت ناپایدار را سخت‌تر نقل می‌کنند؛ این تضمین citation نیست.
- اعتبار سئو روی URL موقت جمع نمی‌شود.

## ۳. غیرهدف‌ها

این پلن عمداً این‌ها را انجام نمی‌دهد:

- روشن کردن یا فورک کردن `plugins/sb-directory`
- ساختن دسته `businesses` داخل `ad_cats` برای نمایش آگهی
- استفاده از permalink هش‌شده آگهی (`/ad/{hash}/{slug}`) برای کسب‌وکار
- تبدیل پروفایل کاربر به فروشگاه
- ساختن سیستم رزرو نوبت یا ایونت
- ایندکس کردن همه ترکیب‌های کشور / شهر / دسته از روز اول
- بازنویسی resolver آگهی در `bornado-routing` یا بازنویسی `bornado-search-core`، geo guide، یا اسکیمای آگهی
- کپی کردن UI جستجوی آگهی به‌عنوان منبع حقیقت دایرکتوری
- کپی کردن قالب `seo-landing.php` یا `page-search.php` برای دایرکتوری
- ساختن sitemap موازی خارج از Rank Math

افزودن یک **extension contract کوچک و سازگار با عقب** به `bornado-routing` بازنویسی نیست و برای فاز ۰ لازم است.

## ۴. اصول طراحی

این اصول از اسناد فعلی پروژه گرفته شده‌اند و برای دایرکتوری هم لازم‌الاجرا هستند.

1. URL یک projection است، نه منبع حقیقت. شناسه پایدار کسب‌وکار جدا از slug است.
2. جغرافیا و دسته از هم جدا می‌مانند. جغرافیا همان `ad_country` است و با **relationship واقعی taxonomy** ذخیره می‌شود؛ دسته کسب‌وکار درخت جدا دارد.
3. کشور در path می‌آید، فیلتر غیرساختاری در query string می‌ماند.
4. صفحه‌بندی عمومی با `/page/{n}/` است، نه فقط اسکرول بی‌نهایت.
5. هر URL فقط وقتی `index` می‌شود که نیت، موجودی واقعی، متن editorial، و canonical همزمان وجود داشته باشد.
6. یک گره اسکیما یک مالک دارد. موجودیت اصلی تک‌صفحه بر اساس `entity_kind` است، نه اجبار همه به `LocalBusiness`. صفحه آگهی همان عمود فعلی می‌ماند.
7. قابلیت فعلی سایت دوباره نوشته نمی‌شود. اتصال به routing فقط از طریق **قرارداد عمومی جدید** است، نه با فرض وجود فیلترهایی که امروز در کد نیستند.
8. هر رکورد عمومی در فاز ۱ نماینده **یک مکان یا یک service-area** است، نه کل برند چندشهری.
9. `route_family=business` باید پیش از هر pipeline آگهی از جستجوی آگهی جدا شود.

## ۵. چه چیزی را نگه می‌داریم و چه چیزی را می‌سازیم

### ۵.۱ استفاده مجدد مستقیم

| قابلیت فعلی | محل | نقش در دایرکتوری |
|---|---|---|
| پیشوند رزرو شده | فیلتر `bornado_seo_routing_reserved_prefixes` | فقط رزرو `/businesses/` و زیرمسیرهای legacy آن؛ برای `/uk/london/businesses/` کافی نیست |
| مدل کشور | `Bornado_Country_Model` | کد کشور، نام نمایشی، وضعیت بازار |
| جغرافیا | taxonomy `ad_country` | کشور و شهر؛ درخت مکان جدید ساخته نمی‌شود |
| لایه اسکیما | `adforest-child/schema` | شاخه جدید؛ تشخیص page type پیش از branch آگهی |
| الگوی جستجوی تمیز | `plugins/bornado-search-core` | فقط الگو: path ساختاری، query غیرساختاری؛ بدون ساخت URL آگهی |
| راهنمای ایرانیان | `bornado_geo_guide` | لینک داخلی دوطرفه با هاب کسب‌وکار همان شهر |
| هاب کشور/شهر آگهی | `/uk/` و `/uk/london/` | ورود به دایرکتوری همان بازار؛ خود هاب بازنویسی نمی‌شود |
| احراز هویت | `bornado-auth-modal` | ورود برای ثبت و claim |
| نوتیفیکیشن | `bornado-notification-bridge` | فاز ۱.۵: eventهای جدید claim و انتشار |
| Rank Math | فیلترهای robots/sitemap | CPT کسب‌وکار در sitemap Rank Math؛ بدون sitemap موازی |

### ۵.۲ استفاده پس از adapter؛ امروز مستقیم ممکن نیست

| قابلیت فعلی | مانع فعلی | کار لازم |
|---|---|---|
| `bornado-routing` | تنها extension عمومی، رزرو پیشوند است. resolver خصوصی بعد از کشور/شهر بقیه path را در `ad_cats` می‌بیند | extension contract کوچک: تشخیص خانواده route پیش از `ad_cats`، تحویل country/city resolveشده، context استاندارد، انتخاب template، 404/canonical |
| CPT `seo_landing` | دسته را از `ad_cats` می‌گیرد؛ route key و preview URL مخصوص آگهی است | adapter/registry برای `route_family`؛ کلید با namespace مثل `business:city_category:...` |
| قالب `seo-landing.php` | بعد از متن editorial همیشه layout جستجوی آگهی را include می‌کند | قالب دایرکتوری مستقل؛ این فایل فقط برای خانواده آگهی می‌ماند |
| `bornado_is_ad_search_view()` و بقیه consumerها | هر semantic route معتبر را صفحه جستجوی آگهی می‌دانند | bypass صریح `route_family=business` در همه نقاط زیر، نه فقط یک helper |
| location picker | action و URL را برای جستجوی آگهی می‌سازد | تزریق URL provider دایرکتوری |
| phone picker | integraton آماده برای فرم کسب‌وکار ندارد | API render/bind عمومی، یا فیلد معتبر ساده در فاز ۱ |
| contact verification | کانال `_sb_contact` پروفایل کاربر را بررسی می‌کند، نه کانال کسب‌وکار | فقط transport و normalization؛ state ماشین claim در افزونه دایرکتوری |
| listing SEO و breadcrumb | برای `ad_cats` و جستجوی آگهی نوشته شده‌اند | لایه title/H1/breadcrumb مستقل دایرکتوری |

### ۵.۳ ساخت جدید

افزونه جدید: `plugins/bornado-business-directory`

مالک این‌ها است:

- CPT کسب‌وکار و taxonomy دسته
- validation و quality policy
- URL service و route adapter
- قالب لندینگ و تک‌صفحه
- کوئری دایرکتوری
- قوانین index/sitemap مخصوص کسب‌وکار
- claim state machine
- اتصال به routing و schema از طریق قرارداد عمومی، نه کپی منطق آگهی

Context تضمینی extension: `route_family`, `route_mode`, `is_valid`, `country_term`, `city_term`, `business_category_term`, `bound_object`, `canonical_url`, `template`, `skip_ad_search_pipeline`, `paged`.

نقاطی که امروز route معتبر را آگهی می‌دانند و باید برای business bypass شوند:

- `Bornado_SEO_Routing::inject_internal_search_state()`
- `Bornado_SEO_Routing::filter_template_include()`
- `Bornado_SEO_Routing::filter_document_title_parts()`
- canonical و robots فعلی routing
- `bornado_listing_seo_should_apply()`
- `bornado-search-windowed-infinite-scroll.php`
- `bornado-contextual-category-counts.php`
- `bornado-performance-optimizations.php`
- `bornado-semantic-route-query-fix.php`
- ItemList آگهی در `schema/shared/item-list.php`
- loading mode صفحه‌اسکرول آگهی
- `Bornado_SEO_Routing::hydrate_route_context()` و `Bornado_SEO_Landing_Manager::find_matching_landing()`: route کسب‌وکار پیش از lookup لندینگ آگهی short-circuit می‌شود؛ hydration خانواده business فقط adapter همان خانواده را می‌بیند؛ دسته از `bornado_business_cat` می‌آید؛ route key و preview family-aware است

مالکیت: routing فقط parse path و کشور/شهر؛ افزونه دایرکتوری URL و template و canonical و robots و query کسب‌وکار؛ child schema فقط graph. تاریخچه slug کشور/شهر متعلق به سرویس مرکزی geo-alias است، نه `class-urls.php`.

قالب والد `adforest/` لمس نمی‌شود. child theme فقط در صورت نیاز override نمایش می‌دهد؛ CPT داخل child نمی‌رود.

## ۶. مدل داده

### ۶.۱ موجودیت اصلی: `bornado_business`

CPT عمومی، قابل کوئری، بدون archive پیش‌فرض وردپرس (`has_archive = false`). URL عمومی را URL service دایرکتوری می‌سازد، نه rewrite خام وردپرس.

منبع حقیقت مکان و دسته **relationship taxonomy** است، نه post meta تکراری.

- جغرافیا: عمیق‌ترین term معتبر `ad_country` (معمولاً شهر) به پست assign می‌شود؛ کشور از ancestor مشتق می‌شود. Organization کشوری فقط به root country assign می‌شود، نه به شهر؛ query آن `include_children=false` است تا location شهری در URL کشور resolve نشود.
- دسته: همه دسته‌های اصلی و فرعی روی `bornado_business_cat` assign می‌شوند.
- فقط `primary_category_term_id` در meta می‌ماند و باید یکی از termهای assignشده باشد.
- آرایه تکراری `country_term_id` / `city_term_id` / `secondary_category_ids` به‌عنوان منبع حقیقت ذخیره نمی‌شود.

`business_uid` هنگام ایجاد با UUID ساخته می‌شود، immutable است، و هر تغییر بعدی رد می‌شود. قبل از ذخیره collision بررسی می‌شود. `@id` اسکیما از همین UID ساخته می‌شود، نه از canonical.

فیلدهای اصلی:

| فیلد | نوع | اجباری برای انتشار عمومی | نکته |
|---|---|---|---|
| `business_uid` | UUID پایدار | بله، سیستم می‌سازد | منبع هویت معنایی؛ به slug قفل نشود |
| `name` | عنوان پست | بله | نام عمومی این location |
| `alternate_name` | متن | خیر | نام انگلیسی یا نام رایج جدا از title |
| `summary` | excerpt | بله برای index | ۲ تا ۳ جمله خاص همان کسب‌وکار |
| `description` | محتوا | بله برای index | متن یکتا؛ کپی دایرکتوری دیگر ممنوع |
| city via `ad_country` | taxonomy | بله برای index شهری | کشور derive می‌شود |
| categories via `bornado_business_cat` | taxonomy | بله | یک دسته اصلی + حداکثر ۲ فرعی |
| `primary_category_term_id` | meta | بله | باید داخل termهای assignشده باشد |
| `entity_kind` | enum | بله | کلید داخلی: `local_business`, `organization`, `professional_service`, `community_organization`. `@type` خروجی `ProfessionalService` نیست؛ آن term در Schema.org منسوخ است |
| `schema_subtype` | allowlist editorial | بله، با پیش‌فرض دسته | ورودی آزاد کاربر نیست |
| `street_address` | متن | توصیه قوی | برای `PostalAddress` فقط اگر واقعی است |
| `postal_code` | متن | خیر | |
| `lat` / `lng` | عدد | اگر آدرس حضوری دارد | مختصات تقریبی ساختگی منتشر نشود |
| `service_area` | ساختار قطعی زیر | برای بدون مراجعه حضوری | چند URL برای یک رکورد نسازد |
| `services_offered` | فهرست کوتاه اختیاری | خیر | نام خدمات/محصولات؛ سیستم رزرو نیست |
| `logo` | media | خیر | جدا از cover/gallery؛ برای `logo` اسکیما |
| `phone` | E.164 | یکی از کانال‌ها برای کیفیت | با phone picker یا فیلد معتبر |
| `whatsapp` | E.164 | خیر | |
| `email` | ایمیل | خیر | پیش‌فرض عمومی نیست؛ `email_visibility` |
| `website` | URL | خیر | تا تأیید، لینک خارجی `rel="ugc nofollow"` |
| `same_as` | آرایه URL تأییدشده | خیر | فقط پروفایل رسمی؛ صفحه داخلی سایت `sameAs` نیست |
| `opening_hours` | ساختار هفته | خیر | چند بازه در روز، روز تعطیل، عبور از نیمه‌شب |
| `price_range` | اختیاری | خیر | فقط اگر معنای واقعی دارد؛ enum اجباری نیست |
| `languages` | آرایه BCP 47 | بدون پیش‌فرض | خالی/unknown تا moderator یا مالک حداقل یک زبان واقعی ثبت کند؛ `fa` خودکار نیست |
| `post_status` | وضعیت وردپرس | بله | `draft`, `pending`, `publish`, `trash` |
| `operational_status` | meta | بله | `active`, `temporarily_closed`, `permanently_closed`, `unknown` |
| `claim_status` | meta | بله | `unclaimed`, `pending`, `verified`, `revoked` |
| `owner_user_id` | user ID | برای claim | |
| `parent_organization_uid` | UUID | خیر | از قرارداد داده؛ UI در فاز ۳ |
| `source` | enum | بله | provenance ورود؛ بعداً به `owner` جعل نمی‌شود |
| `last_verified_at` | datetime | خیر | جدا از `dateModified` صفحه |
| `address_visibility` / `email_visibility` | enum | بله | پیش‌فرض ایمیل مخفی |
| `indexability_state` | خروجی policy | سیستم | همراه `indexability_reasons` و نسخه policy |
| `linked_business` روی آگهی | relationship فاز ۲ | خیر | آرایه دوطرفه ناسازگار ساخته نشود |
| `cover` / گالری | media | کاور برای index | عکس واقعی |

`indexable` یک boolean تنها و بدون دلیل نیست. ذخیره می‌شود:

- `indexability_state`
- `indexability_reasons`
- `quality_policy_version`
- `quality_evaluated_at`

محاسبه هنگام save یا CLI است، نه در هر page view.

`service_area` این shape را دارد:

```text
mode = none | city_list | country_wide
city_term_ids = int[]
```

`country_term_id` ذخیره نمی‌شود؛ از root ancestor مکان canonical (taxonomy `ad_country`) derive می‌شود. اگر در serialization موقت ظاهر شود، validator آن را از taxonomy overwrite می‌کند و ورودی مستقل کاربر/import نیست.

قواعد: `country_wide` فقط برای `entity_kind=organization` یا allowlist صریح؛ رکورد country-wide فقط به root `ad_country` assign می‌شود؛ هر `city_term_id` باید location قابل route در قرارداد فعلی باشد، root country نباشد و به همان root تعلق داشته باشد؛ `city_list` canonical کشوری نمی‌سازد؛ URL جدا per city ساخته نمی‌شود؛ آدرس دفتر از `areaServed` جدا است؛ radius در فاز ۱ نیست؛ HTML و JSON-LD از همین منبع واحد می‌خوانند.

در فاز ۱، `service_area` فقط فکت نمایشی و Schema است. عضویت هاب، count، فیلتر، sitemap و canonical فقط از مکان canonical در `ad_country` می‌آید. بودن منچستر در `city_list` کسب‌وکار لندن را عضو `/uk/manchester/businesses/` نمی‌کند. جستجوی آرایه serialized در post meta برای عضویت هاب ممنوع است؛ اگر بعداً نمایش در چند هاب لازم شد، رابطه queryable جدا طراحی می‌شود.

ذخیره WordPress اتمیک نیست. تمام مسیرها ابتدا post را در `draft`/`pending` کامل می‌کنند. فقط سرویس واحد `publish_business()` پس از snapshot کامل post/meta/taxonomy اجازه transition به `publish` دارد. انتشار خارج از این مسیر (`transition_post_status` یا معادل) رد یا به `pending` برمی‌گردد. quality، indexability، URL history و cache فقط بعد از snapshot معتبر محاسبه می‌شوند.

Invariantها هنگام save، import، REST و WP-CLI از یک validator مرکزی enforce می‌شوند. نقض در ادمین پیام واضح می‌دهد و رکورد `draft`/`pending` می‌ماند؛ داده ناقص publish نمی‌شود:

1. دقیقاً یک term جغرافیایی canonical: city برای location، یا root country برای Organization کشوری
2. location شهری مستقیماً root country نگیرد
3. Organization کشوری term شهر نگیرد
4. حداکثر یک primary و دو secondary
5. `primary_category_term_id` یکی از termهای assignشده باشد
6. `entity_kind` و `schema_subtype` سازگار باشند
7. slug با دسته و reserved slug برخورد نداشته باشد

رکورد در هاب primary و secondary نمایش داده می‌شود. breadcrumb و URL تک‌صفحه همیشه از primary می‌آیند. تغییر primary/secondary هاب‌های قدیم و جدید را invalidate می‌کند.

### ۶.۲ دسته: `bornado_business_cat`

درخت جدا از `ad_cats`. دلیل:

- نیت دایرکتوری با نیت آگهی یکی نیست.
- `/services/` امروز جستجوی آگهی خدمت است و نباید با «کسب‌وکارهای خدماتی» قاطی شود.
- resolver فعلی routing بعد از کشور/شهر، بقیه path را در `ad_cats` می‌بیند. اگر `businesses` را وارد `ad_cats` کنیم، همان صفحات تبدیل به جستجوی آگهی می‌شوند.

پیشنهاد دسته سطح اول برای شروع؛ عمیق‌تر فقط وقتی موجودی واقعی باشد:

- `food-drink` — غذا و نوشیدنی
- `grocery` — سوپرمارکت و محصولات ایرانی
- `health` — پزشکی، دندان، داروخانه
- `legal-immigration` — حقوقی و مهاجرت
- `education` — آموزش و زبان
- `beauty` — زیبایی و آرایش
- `auto` — خودرو و تعمیرات
- `home-trade` — خانه، تعمیرات، ساخت
- `professional` — حسابداری، بیمه، ترجمه
- `community` — مسجد، انجمن، رسانه، رویدادساز

Slug انگلیسی است، مثل بقیه سایت. نام نمایشی فارسی است.

هر دسته یک `schema_subtype` پیش‌فرض و یک `entity_kind` پیش‌فرض دارد. مسجد یا انجمن نباید به‌زور `LocalBusiness` شوند.

پیش از قفل slug مسیر `/businesses/` باید audit شود که term زنده یا alias با همین slug در `ad_cats`، و page یا rewrite دیگری، وجود نداشته باشد. در اسناد قدیمی `businesses` به‌عنوان pillar آگهی آمده است؛ بعد از قفل، `/uk/businesses/` فقط هاب دایرکتوری است.

### ۶.۳ لندینگ editorial

لندینگ جدا از خود کسب‌وکار است:

> لندینگ یک دارایی سئویی با state مشخص است، نه خود لیست نتایج.

خود CPT `seo_landing` حفظ می‌شود تا CPT سوم ساخته نشود. مدیر فعلی به‌تنهایی کافی نیست، چون دسته را از `ad_cats` می‌گیرد.

لازم است:

- adapter برای `route_family=ads|business`
- route key با namespace
- جلوگیری از انتشار دو لندینگ با یک کلید
- mapping دسته کسب‌وکار از `bornado_business_cat`
- preview URL از URL service دایرکتوری

نوع‌های خانواده business:

- `business_root` → `/businesses/`
- `business_country` → `/uk/businesses/`
- `business_country_category` → `/uk/businesses/grocery/`
- `business_country_city` → `/uk/london/businesses/`
- `business_country_city_category` → `/uk/london/businesses/grocery/`

محتوای editorial از همین CPT خوانده می‌شود؛ رندر با قالب افزونه دایرکتوری است، نه `seo-landing.php`.

### ۶.۴ برند و شعبه

فاز ۱: هر پست عمومی یک location است. نام، تلفن، ساعت و URL همان مکان را دارند.

اگر چند شهر یک برند واحدند، چند رکورد ساخته می‌شود. `parent_organization_uid` از ابتدا در داده هست؛ UI ادغام برند فاز ۳ است. ادغام چند شعبه در یک صفحه فقط وقتی مجاز است که واقعاً یک مکان واحد نیستند و مدل `Organization` مناسب‌تر است.

## ۷. قرارداد URL

سایت country-first است. دایرکتوری هم باید همان قرارداد را رعایت کند.

### ۷.۱ URLهای عمومی فاز ۱

```text
/businesses/
/businesses/page/2/
/uk/businesses/
/uk/businesses/grocery/
/uk/london/businesses/
/uk/london/businesses/grocery/
/uk/london/businesses/grocery/page/2/
/uk/london/businesses/nanvaei-ariana/
```

معنی:

- `/businesses/` هاب جهانی: intro + دسته + بازار + **صفحه اول فهرست همه رکوردهای عمومی**
- `/businesses/page/{n}/` ادامه همان فهرست؛ self-canonical؛ اگر `/businesses/` indexable باشد `index,follow`، وگرنه `noindex,follow`؛ خارج از محدوده `404`
- `/uk/businesses/` هاب کشور
- `/uk/businesses/grocery/` هاب دسته در کشور؛ `index` فقط با موجودی و نیت مستقل
- `/uk/london/businesses/` هاب شهر؛ مهم‌ترین money page دایرکتوری
- `/uk/london/businesses/grocery/` هاب دسته در شهر
- آخرین سگمنت اگر دسته نباشد: تک‌صفحه location شهری، یا فقط در `/uk/businesses/{slug}/` تک‌صفحه Organization کشوری

### ۷.۲ URLهایی که باید تکلیف‌شان روشن باشد

| URL | وضعیت فاز ۱ |
|---|---|
| `/businesses/{category}/` | در فاز ۱ وجود ندارد و `404` است |
| `/uk/businesses/grocery/` | مجاز با route type `business_country_category`؛ `index` فقط با موجودی و نیت مستقل کشور |
| `/uk/businesses/{slug}/` | فقط Organization منتشرشده با assign مستقیم به root country و service-area واقعاً country-wide؛ چند شهر بودن به‌تنهایی canonical کشوری نمی‌سازد. location شهری روی این URL برابر `404` است |
| `/businesses/uk/london/` | همیشه ۳۰۱ به `/uk/london/businesses/` |
| چند شهر به‌عنوان service-area | یک canonical؛ URL جدا per city ساخته نشود |

هر location دقیقاً یک canonical دارد.

### ۷.۳ تقسیم مالکیت route

- `/businesses/` و ریدایرکت‌های legacy زیر `/businesses/*` → افزونه دایرکتوری (چون پیشوند رزرو می‌شود و resolver آگهی آن را نادیده می‌گیرد)
- `/uk/[city/]businesses/...` → extension contract در `bornado-routing`
- ساخت canonical، `/page/1/`→۳۰۱، page بزرگ‌تر از `max_pages`→۴۰۴، تاریخچه slug کسب‌وکار و `bornado_business_cat`، redirect merge، و lookup Organization با `include_children=false` → `class-urls.php` و route adapter دایرکتوری؛ routing فعلی آگهی این‌ها را آماده ندارد
- تاریخچه slug و alias مربوط به taxonomy مشترک `ad_country` مالکیت مرکزی دارد، نه افزونه دایرکتوری. سرویس مشترک geo-alias (مصرف‌شده توسط `bornado-routing` و geo guide) slug قدیمی کشور/شهر را پیش از resolve نهایی تشخیص می‌دهد و هر route family معنایی متاثر را، با حفظ tail معتبر، به canonical جدید همان خانواده ۳۰۱ می‌کند. افزونه دایرکتوری geo history را دوباره ذخیره نمی‌کند. path/alias تاریخی `ad_country` دوباره تخصیص داده نمی‌شود. redirectها مستقیم‌اند و زنجیره ساخته نمی‌شود. query مجاز و pagination طبق قرارداد همان خانواده حفظ می‌شود؛ state نامعتبر با ۳۰۱ عمومی پنهان نمی‌شود و طبق قرارداد همان خانواده `404` است.

### ۷.۴ قوانین resolver

بعد از تشخیص کشور و شهر فعلی:

1. اگر سگمنت بعدی `businesses` نیست، منطق آگهی فعلی بدون تغییر ادامه می‌یابد.
2. اگر هست، `route_family=business` روشن می‌شود و دیگر `ad_cats` خوانده نمی‌شود.
3. سگمنت بعدی اگر term دسته باشد → کالکشن. slug دسته بر Organization اولویت دارد؛ برخورد هنگام ذخیره مسدود می‌شود.
4. اگر city در context وجود دارد و location منتشرشده با آن slug به همان city تعلق دارد → تک‌صفحه location.
5. اگر city در context وجود ندارد و Organization منتشرشده با آن slug مستقیماً به root country تعلق دارد و service-area آن country-wide است → تک‌صفحه Organization. query این حالت `include_children=false` است. `entity_kind` باید `organization` یا معادل allowlist باشد. داشتن چند service-area شهری به‌تنهایی canonical کشوری نمی‌سازد.
6. در غیر این صورت → `404`.
7. query فقط از allowlist: `q`, `sort`. مقدار خالی و پیش‌فرض حذف می‌شود. ترتیب قطعی است.
8. پارامتر tracking شناخته‌شده مثل `utm_*` در محتوا و query دخالت نمی‌کند و canonical بدون آن است. پارامتر فیلتر ناشناخته یا تکراری state جدید نمی‌سازد؛ اگر حذفش نیت را از بین نبرد، ۳۰۱ به نسخه تمیز. اگر برای ساخت route یا state نامعتبر باشد، `404`. لینک داخلی سایت پارامتر ناشناخته تولید نمی‌کند.
9. `sort` کالکشن: `noindex,follow` و canonical به path تمیز هاب. `q`: `noindex,follow` و self-canonical به همان URL نرمال‌شدهٔ `q`؛ خارج از sitemap و خارج از لینک‌های crawlable ناوبری. exact match یکتا همچنان پیش از render به تک‌صفحه ۳۰۱ می‌شود.
10. صفحه‌بندی فقط `/page/{n}/`. `/page/1/` به URL پایه ۳۰۱ می‌شود. صفحه بعد از آخرین صفحه `404` است. pagination path تمیزِ کالکشن واجد شرایط: `index,follow` و self-canonical، فقط اگر صفحه پایه همان کالکشن indexable باشد؛ intro کامل لازم نیست. اگر پایه `noindex` باشد، تمام pagination آن هم `noindex,follow` است. pagination با `q` یا `sort`: `noindex,follow`.
11. این URLها در `robots.txt` مسدود نمی‌شوند؛ crawler باید meta robots را ببیند.

Slug کسب‌وکار نباید با slug دسته یا slugهای رزرو (`page`, `feed`, `amp`, `embed`) یکی شود. یکتا بودن **مسیر نهایی** بررسی می‌شود، نه فقط `post_name`. slugهای `bornado_business_cat` که در URL عمومی رفته‌اند تاریخچه و ۳۰۱ دارند و مالک‌شان افزونه دایرکتوری است. slug کشور/شهر از سرویس مرکزی geo-alias می‌آید، نه از history جداگانهٔ دایرکتوری. path یا alias تاریخی به موجودیت دیگری داده نمی‌شود. برخورد هنگام تغییر business، دسته، مکان و alias دوطرفه چک می‌شود.

### ۷.۵ تاریخچه و کسب‌وکار بسته

- تغییر slug کسب‌وکار → ۳۰۱ از مسیر قبلی همان کسب‌وکار
- تغییر شهر یک رکورد → ۳۰۱ از مسیر کامل قبلی همان کسب‌وکار
- تغییر slug term مشترک `ad_country` → ۳۰۱ مرکزی برای همهٔ خانواده‌های معنایی متاثر (آگهی، دایرکتوری، geo guide)، نه فقط URL کسب‌وکار
- تغییر دسته اصلی URL تک‌صفحه را عوض نمی‌کند، چون دسته در URL تک‌صفحه نیست
- merge دستی فاز ۱: survivor همان رکورد اصلی است؛ `survivor_uid` می‌ماند؛ `loser_uid` هرگز reuse نمی‌شود؛ loser tombstone غیرعمومی با `merged_into_uid` است؛ همه URLهای loser مستقیم ۳۰۱ به canonical survivor؛ زنجیره redirect در لحظه merge flatten می‌شود؛ self-merge و cycle ممنوع؛ انتقال روابط اتمیک یا rollback‌پذیر است
- `temporarily_closed` صفحه می‌ماند؛ وضعیت در HTML واضح است. تا وقتی `specialOpeningHoursSpecification` نیست، `openingHoursSpecification` برای این وضعیت خروجی داده نمی‌شود تا موجودیت باز دیده نشود
- `permanently_closed` در حالت عادی مدتی با اطلاعات تاریخی می‌ماند، سپس در صورت بی‌ارزشی `noindex` و در نهایت در صورت نبود جایگزین `410`
- ۳۰۱ به هاب فقط برای جابه‌جایی، merge، یا جایگزین واقعی؛ نه برای هر رکورد بسته

### ۷.۶ جستجوی نام

- کشور/شهر/دسته کامل → route معنایی
- `q` موجود → همان هاب نزدیک با `?q=`، `noindex,follow` و self-canonical به همان query نرمال‌شده
- exact match فقط وقتی ۳۰۱ می‌شود که `normalize(name) == normalize(q)` باشد و در همان country/city/category دقیقاً یک نتیجه بماند
- چند هم‌نام، یا فقط شباهت فازی: redirect نشود
- تلفن یا دامنه فقط برای رفع ابهام کمکی است، نه برای ۳۰۱ فازی
- حذف `q` هنگام ناقص بودن جغرافیا ممنوع است

### ۷.۷ چیزهایی که URL کسب‌وکار نیست

- `/ad/{hash}/{slug}` فقط آگهی است.
- `/iranians/{country}/{city}/` فقط راهنمای جامعه است.
- `/uk/london/services/` فقط آگهی خدمات است.
- archive خام وردپرس برای CPT و taxonomy دایرکتوری به URL معنایی ۳۰۱ می‌شود.

## ۸. لندینگ همه کسب‌وکارها

کاربر صریحاً یک لندینگ برای تمام کسب‌وکارها با دسته و ورودهای جغرافیایی خواسته است. این صفحه `/businesses/` است.

### ۸.۱ وظیفه صفحه

این صفحه جایگزین هاب کشور یا شهر نمی‌شود، ولی **فهرست جهانی همه کسب‌وکارهای عمومی** را هم حمل می‌کند؛ «همه» یعنی `publish` و قابل نمایش، نه draft یا private.

کارش این است:

- نیت دایرکتوری را تعریف کند.
- دسته‌ها و بازارهای فعال را نشان دهد.
- جستجو و فیلتر را شروع کند؛ انتخاب کشور/شهر/دسته به route معنایی همان سطح برود.
- صفحه اول: intro + فیلترها + نتایج عمومی.
- صفحه ۲ به بعد (`/businesses/page/{n}/`): context کوتاه + نتایج؛ intro و FAQ کامل تکرار نشود.
- کل دیتابیس در یک HTML بی‌انتها ریخته نشود؛ صفحه‌بندی واقعی است.

### ۸.۲ بلوک‌های لازم

1. **H1 و intro editorial**  
   متن مخصوص همین صفحه. تکرار «بهترین دایرکتوری کسب‌وکار» بدون مکان و شاهد، ممنوع. مالک H1 و title لایه SEO دایرکتوری است، نه قالب آگهی.
2. **جستجو**  
   نام، شهر، دسته. اگر شهر و دسته کامل باشد: `/uk/london/businesses/grocery/`. اگر `q` باشد حفظ می‌شود. اگر ناقص باشد: نزدیک‌ترین هاب تمیز **با حفظ `q` معتبر**.
3. **شبکه دسته‌ها**  
   فقط دسته‌هایی که حداقل یک کسب‌وکار قابل نمایش دارند. دسته خالی لینک indexable نمی‌گیرد. لینک جهانی `/businesses/{cat}/` در فاز ۱ وجود ندارد (`404`). شبکه دسته کاربر را به انتخاب بازار، یا به هاب country/category و city/category موجود، هدایت می‌کند.
4. **بازارهای فعال**  
   از `Bornado_Country_Model` و tier فعلی: اول بریتانیا، کانادا، آمریکا. کشور Tier 3 اگر موجودی ندارد در این شبکه نیاید.
5. **شهرهای اولویت‌دار**  
   لندن، منچستر، تورنتو، ونکوور، لس‌آنجلس و هر شهری که آستانه هاب شهر را رد کند.
6. **فهرست عمومی صفحه‌بندی‌شده**  
   همه رکوردهای قابل نمایش همان صفحه. `ItemList` فقط همان نتایج visible است، نه کل `found_posts`. `q` و `sort` روی query می‌مانند و `noindex` هستند. فرم جستجو می‌تواند GET باشد، ولی سایت شبکه `<a href>` از queryهای دلخواه نمی‌سازد؛ `q` در sitemap، شبکه دسته، related links و پیشنهادهای indexable نمی‌آید. طول، encoding و نرخ درخواست محدود است؛ cache عمومی per-query نامحدود ساخته نمی‌شود. block پیش‌دستانهٔ همهٔ `q`ها در `robots.txt` الزام فاز ۱ نیست.
7. **تفاوت با آگهی**  
   یک پاراگراف کوتاه: اینجا هویت پایدار است؛ برای استخدام یا اجاره به آگهی بروید. لینک به `/jobs/` و `/property/` موجود.
8. **ثبت / claim**  
   CTA به فرم. ایندکس صفحه به این CTA وابسته نیست.
9. **FAQ**  
   فقط اگر نیاز واقعی کاربر را جواب می‌دهد. نبود FAQ quality gate را نمی‌شکند. `FAQPage` فقط وقتی پرسش و پاسخ کامل در HTML دیده شود.

### ۸.۳ ایندکس هاب جهانی

`/businesses/` وقتی `index` است که:

- landing از نوع `business_root` با متن واقعی داشته باشد؛
- حداقل چند دسته غیرخالی وجود داشته باشد؛
- حداقل دو بازار یا چند کسب‌وکار قابل استناد وجود داشته باشد.

اگر دایرکتوری هنوز خالی است، صفحه می‌تواند برای توسعه بالا باشد ولی `noindex,follow` می‌ماند. `/businesses/page/{n}/` تمیز همان وضعیت index صفحه پایه را به ارث می‌برد؛ intro کامل روی صفحه ۲ لازم نیست.

## ۹. بقیه صفحه‌های کالکشن

### ۹.۱ `/uk/businesses/`

نقش: کسب‌وکارهای ایرانی در آن کشور.  
محتوا: intro کشور، شهرهای فعال، دسته‌های غیرخالی، چند مورد برتر.  
ایندکس: طبق آستانه کشور در بخش ۱۱.  
از `/uk/` یک لینک «کسب‌وکارها» به اینجا کافی است.

### ۹.۱.۱ `/uk/businesses/grocery/`

نقش: یک دسته در کل آن کشور. route type: `business_country_category`.  
ایندکس فقط با موجودی واقعی و intro کشور-دسته. اگر نیت مستقل از هاب کشور یا هاب شهر-دسته ندارد، `noindex,follow`.

### ۹.۲ `/uk/london/businesses/`

مهم‌ترین صفحه دایرکتوری.  
محتوا: intro محلی، دسته‌ها، فهرست واقعی، لینک به `/iranians/uk/london/` و به `/uk/london/`.  
ایندکس: موجودی پایدار + editorial محلی.

### ۹.۳ `/uk/london/businesses/grocery/`

فقط وقتی دسته در آن شهر موجودی و نیت مستقل دارد.  
اگر موجودی ضعیف باشد: برای UX می‌تواند دیده شود، ولی `noindex,follow`.

### ۹.۴ تک‌صفحه کسب‌وکار

بلوک‌های لازم:

- نام، نوع موجودیت، دسته، شهر، کشور
- آدرس و نقشه فقط در صورت داده واقعی
- تلفن / واتساپ / وب‌سایت
- ساعت کاری ساخت‌یافته، هم‌خوان با اسکیما
- توضیحات یکتا
- گالری واقعی
- وضعیت عملیاتی و claim در HTML
- زبان خدمت
- تاریخ آخرین بررسی اطلاعات، جدا از تاریخ ویرایش صفحه
- کسب‌وکارهای مرتبط همان شهر و همان دسته
- آگهی‌های متصل در فاز ۲
- لینک برگشت به هاب شهر و دسته

نظرات کاربری در فاز ۱ نیست.

تک‌صفحه Organization کشوری شهر در breadcrumb ندارد و به `/uk/businesses/` برمی‌گردد، نه به هاب شهر.

### ۹.۵ SEO presentation و breadcrumb

افزونه دایرکتوری مالک این‌ها است:

- یک H1 اصلی
- `document_title_parts` و فیلتر title رنک‌مث هم‌تراز
- meta description از excerpt editorial یا fallback کنترل‌شده
- جلوگیری از عنوان تکراری بین شهرها و دسته‌ها
- query `?q=` می‌تواند title را عوض کند ولی `noindex` می‌ماند

`bornado-listing-seo.php` روی دایرکتوری اجرا نمی‌شود.

breadcrumb مستقل، هم‌خوان با `BreadcrumbList`:

خانه → کسب‌وکارها → کشور → شهر در صورت وجود → دسته در صورت وجود → نام location در تک‌صفحه

برای `/uk/businesses/grocery/` شهر در زنجیره نیست.

`bornado-breadcrumbs.php` فعلی `ad_cats` را می‌خواند و برای این مسیر کافی نیست.

## ۱۰. معیارهای رتبه در موتور جستجو

### ۱۰.۱ موجودیت و پایداری

- هر location یک canonical فعلی و یک `@id` پایدار مبتنی بر UID دارد.
- با عوض شدن عنوان، slug قدیمی ۳۰۱ می‌شود؛ `business_uid` و `@id` عوض نمی‌شوند.
- کسب‌وکار بسته‌شده طبق بخش ۷.۵ رفتار می‌کند، نه با ۳۰۱ گروهی به هاب.

Convention هویت:

```text
{home_url}/#bornado-business-{uuid}
```

`url` موجودیت همان canonical صفحه است. `WebPage.mainEntity` به `@id` پایدار اشاره می‌کند.

### ۱۰.۲ NAP

- نام، آدرس، تلفن در HTML و JSON-LD یکی باشند.
- اگر آدرس کامل نیست، آدرس جعلی ساخته نشود.
- `telephone` با E.164 همان شماره دیده شده در صفحه است.
- LocalBusiness بدون `PostalAddress` واقعی می‌تواند از نظر Schema.org معنادار باشد، اما الزامات مستند Google Local Business rich result را برآورده نمی‌کند. آدرس جعلی برای کسب eligibility ساخته نمی‌شود.

### ۱۰.۳ محتوا و thin page

- کالکشن بدون intro اختصاصی index نمی‌شود. استثنا: pagination path تمیز (`/page/{n}/` برای n≥۲) اگر صفحه پایه indexable باشد، با context کوتاه و نتایج یکتا `index,follow` است.
- کالکشن بدون موجودی واقعی index نمی‌شود.
- تک‌صفحه بدون توضیح یکتا index نمی‌شود.
- تولید انبوه «بهترین X در Y» ممنوع است.
- فیلتر و سورت وارد sitemap یا index اصلی نمی‌شوند.

### ۱۰.۴ ایندکس و کنونیکال

- URL تمیز self-canonical است.
- هر صفحه pagination معتبر به خودش canonical است، نه به صفحه اول. robots آن طبق قاعده بخش ۷.۴ است، نه از روی نبود intro کامل.
- native permalink وردپرس CPT/لندینگ، canonical عمومی نیست.
- `post_type_link` همیشه URL معنایی را برمی‌گرداند.
- برای `route_family=business` منبع حقیقت robots فقط `class-indexation.php` است. `wp_robots` و Rank Math از همان policy ساخته می‌شوند و routing آگهی بعداً آن را overwrite نمی‌کند. اولویت hook در `integration-contracts.md` قفل و تست می‌شود.

### ۱۰.۵ Sitemap

- فقط URLهای indexable وارد sitemap Rank Math می‌شوند؛ pagination تمیز کالکشن واجد شرایط هم اگر `index` باشد می‌تواند بیاید.
- کلاس sitemap مستقل موازی ساخته نمی‌شود.
- `rank_math/sitemap/entry` و count query با همان policy هم‌خوان‌اند.
- بعد از تغییر publish، URL، term، claim یا indexability کش Rank Math invalidate می‌شود.
- لندینگ‌ها هم route عمومی درست را در sitemap می‌دهند، نه permalink بومی CPT.

### ۱۰.۶ لینک داخلی

- `/uk/london/` → `/uk/london/businesses/`
- `/uk/london/businesses/` ↔ `/iranians/uk/london/`
- هاب دسته ↔ چند تک‌صفحه
- تک‌صفحه → هاب شهر و هاب دسته
- `/businesses/` → بازارها و دسته‌های فعال
- فاز ۲: آگهی متصل ↔ کسب‌وکار verified

لینک‌ها semantic هستند، نه `/ad_cats/` یا `/ad_country/`.

### ۱۰.۷ رندر و کراول

- HTML اول سرور است. اسکرول اضافه فقط enhancement است.
- صفحه ۲ و بعد بدون جاوااسکریپت و با `<a href>` واقعی previous/next در HTML SSR قابل رسیدن است. `rel=next/prev` الزامی نیست. تست روی HTML خام.
- NAP و متن اصلی در HTML اول هستند.

### ۱۰.۸ E-E-A-T

ترتیب اعتماد:

1. مالک claim کرده و کنترل کانال از پیش ثبت‌شده کسب‌وکار را ثابت کرده، یا بررسی دستی شده
2. ثبت editorial تیم با منبع مشخص
3. import خام بدون تأیید

Import تأییدنشده وارد sitemap نمی‌شود. تأیید شماره پروفایل کاربر به‌تنهایی claim نیست.

پذیرش در دایرکتوری بر پایه ارتباط قابل تأیید با خدمت فارسی یا جامعه ایرانی است، نه استنباط قومیت مالک. حداقل یکی از این‌ها کافی است: ارائه خدمت به زبان فارسی، معرفی رسمی خدمات برای جامعه ایرانی، یا تأیید داوطلبانه همین ارتباط توسط مالک. این ویژگی در UI با همین زبان غیرتبعیض‌آمیز بیان می‌شود و در Schema به‌صورت فکت قومیتی درج نمی‌شود.

### ۱۰.۹ زبان

- `inLanguage` صفحه مثل بقیه سایت `fa-IR` است.
- زبان خدمت کسب‌وکار فقط اگر صریح ثبت شده باشد در HTML/JSON-LD می‌آید؛ `knowsLanguage` اختیاری و فقط با معنای درست است.
- نام انگلیسی در `alternate_name` می‌ماند.
- hreflang در فاز ۱ لازم نیست مگر نسخه انگلیسی واقعی همان صفحه وجود داشته باشد.

## ۱۱. آستانه ایندکس پیشنهادی

این اعداد guardrail هستند، hard-code غیرقابل تنظیم نیستند. از فیلتر/config per route type خوانده می‌شوند و همراه reason code در ادمین دیده می‌شوند.

نمونه reason: `missing_editorial`, `inventory_below_threshold`, `unverified_import`, `missing_contact_or_address`.

| صفحه | حداقل موجودی قابل نمایش | محتوای editorial | نتیجه |
|---|---|---|---|
| `/businesses/` | چند دسته غیرخالی + چند مورد واقعی | intro هاب | index |
| `/uk/businesses/` | ۱۵ کسب‌وکار یا ۳ شهر فعال | intro کشور | index |
| `/uk/businesses/grocery/` | ۱۵ کسب‌وکار در آن دسته در کشور، یا نیت مستقل روشن | intro کشور-دسته | index |
| `/uk/london/businesses/` | ۸ کسب‌وکار | intro شهر | index |
| `/uk/london/businesses/grocery/` | ۵ کسب‌وکار و نیت مستقل | intro دسته-شهر | index |
| تک‌صفحه | ۱ location کامل | توضیح یکتا + مکان یا service-area + دسته | index فقط اگر کیفیت حداقل رد شود |

کیفیت حداقل تک‌صفحه برای index:

- نام + دسته
- شهر، مگر entity واقعاً country-wide/`Organization`
- توضیح یکتا
- حداقل یکی از این‌ها: آدرس، تلفن معتبر، وب‌سایت واقعی، یا claim تأییدشده
- `post_status=publish` و `operational_status` برابر `active` یا `temporarily_closed`
- `source` provenance ورود است و بعد از claim به `owner` عوض نمی‌شود
- رکورد واجد شرایط است اگر `source` برابر `owner` یا `editorial` باشد؛ یا `source=import` باشد ولی `claim_status=verified` و انتشار از مسیر moderation انجام شده باشد
- import هرگز مستقیم `publish` نمی‌شود؛ فقط capability moderator می‌تواند آن را منتشر کند؛ transition انتشار audit می‌شود؛ claim به‌تنهایی و بدون publish کنترل‌شده موجب index نمی‌شود
- import خام و تأییدنشده هرگز وارد sitemap نمی‌شود

FAQ برای این آستانه‌ها اجباری نیست.

بازار شروع مطابق `MARKET-TIERING-PLAYBOOK.md`: UK، Canada، چند متروی USA.

## ۱۲. معیارهای هوش مصنوعی

### ۱۲.۱ فکت‌های قابل نقل

در HTML و JSON-LD:

- نام رسمی
- نوع موجودیت و subtype معتبر
- شهر و کشور
- آدرس در صورت وجود
- تلفن
- ساعت کاری
- زبان خدمت

وضعیت باز / موقتاً بسته / دائم‌بسته و تاریخ آخرین بررسی فقط در HTML می‌آیند. `last_verified_at` وارد JSON-LD نمی‌شود. `dateModified` فقط زمان واقعی تغییر صفحه است. `dissolutionDate` فقط برای انحلال واقعی Organization است، نه بستن یک location. `specialOpeningHoursSpecification` فقط با بازهٔ تاریخی واقعی استفاده می‌شود. وضعیت موقتاً بسته طبق بخش ۷.۵ با حذف ساعات عادی و بیان واضح در HTML مدیریت می‌شود؛ property ساختگی برای `operational_status` ساخته نمی‌شود.

جمله‌هایی مثل «بهترین در شهر» بدون شاهد در H1 نمی‌آیند.

### ۱۲.۲ گراف موجودیت

تک‌صفحه:

- `WebSite` و publisher فعلی سایت
- `WebPage` یا `ItemPage`
- موجودیت اصلی با `@id` پایدار UID
- `PostalAddress` و `GeoCoordinates` فقط با داده واقعی
- `ImageObject` واقعی
- `BreadcrumbList` هم‌خوان با breadcrumb دیده شده

گره‌های کمکی صفحه (`#webpage`, `#breadcrumb`) می‌توانند از canonical ساخته شوند. هویت کسب‌وکار نه.

کالکشن: `CollectionPage` + `ItemList` فقط موارد دیده شده همان صفحه + breadcrumb. `FAQPage` فقط با FAQ واقعی. برای `?q=` در صورت نیاز `SearchResultsPage`، همچنان `noindex`.

قواعد:

- یک مالک برای هر گره
- `Offer` و rating و review جعلی ساخته نشود
- `identifier` برای UID است، نه مهر تأیید
- نشان claim فقط در HTML است
- `sameAs` به صفحه داخلی سایت داده نشود
- sanitizer دایرکتوری nodeهای آگهی یا Rank Math متعارض را از graph این صفحه حذف کند
- تشخیص page type دایرکتوری پیش از `city_collection` و shapeهای آگهی انجام شود

Page typeها: `business_root`, `business_country_collection`, `business_country_category_collection`, `business_city_collection`, `business_city_category_collection`, `single_business`.

### ۱۲.۳ subtype

| دسته | kind / subtype پیشنهادی |
|---|---|
| غذا و نوشیدنی | `Restaurant` / `CafeOrCoffeeShop` / `Bakery` |
| سوپرمارکت | `GroceryStore` |
| پزشکی | `Physician` / `Dentist` / `Pharmacy` |
| حقوقی | `LegalService` |
| آموزش | `EducationalOrganization` یا `LocalBusiness` |
| زیبایی | `HealthAndBeautyBusiness` |
| خودرو | `AutoRepair` / `AutoDealer` |
| حسابداری | `AccountingService` / `FinancialService` |
| مسجد / انجمن / رسانه | `Mosque` / `Organization` / `NewsMediaOrganization` |

اگر مطمئن نیستیم، نوع عمومی درست بهتر از subtype غلط است. `Attorney` و `ProfessionalService` در خروجی JSON-LD استفاده نمی‌شوند.

### ۱۲.۴ اتصال به راهنمای جامعه

- یک بلوک لینک به `/uk/london/businesses/`
- ذکر ۲ یا ۳ کسب‌وکار فقط وقتی editorial واقعاً آن‌ها را نام می‌برد؛ بعد با `@id` پایدار اشاره می‌شود

### ۱۲.۵ چیزهایی که برای AI ساخته نمی‌شوند

- صفحه جدا «برای مدل‌ها»
- FAQ ساختگی
- rating بدون نظر واقعی
- `sameAs` به صفحه‌های ساخته‌شده خودمان
- کپی ویکی‌پدیا یا گوگل مپ به‌عنوان توضیحات
- ساخت سفارشی `llms.txt` به‌عنوان milestone. Rank Math همین حالا ماژول `llms-txt` دارد؛ اگر بعداً لازم شد، اول همان ارزیابی می‌شود. این کار فاز رشد قطعی نیست.

منابع خصوصی claim و اسناد داخلی در JSON-LD یا REST عمومی نمی‌آیند.

## ۱۳. اسکیما؛ محل فایل‌ها

مالک: `adforest-child/schema`  
هسته آگهی دست نخورده می‌ماند. business logic و quality gate داخل schema نمی‌رود.

```text
adforest-child/schema/pages/business-directory/root.php
adforest-child/schema/pages/business-directory/country.php
adforest-child/schema/pages/business-directory/country-category.php
adforest-child/schema/pages/business-directory/city.php
adforest-child/schema/pages/business-directory/category.php
adforest-child/schema/pages/business-directory/single.php
adforest-child/schema/verticals/businesses/enrich.php
```

`shared/context.php` خانواده business را پیش از mapping آگهی تشخیص می‌دهد.

صفحه تک‌آگهی فعلی `Person` را برای فروشنده نگه می‌دارد. فقط در فاز ۲، اگر آگهی به کسب‌وکار verified وصل باشد، می‌توان به `@id` پایدار همان موجودیت ارجاع داد.

## ۱۴. معماری افزونه

```text
plugins/bornado-business-directory/
  bornado-business-directory.php
  includes/
    class-post-type.php
    class-taxonomy.php
    class-meta.php
    class-quality-gate.php
    class-routing-integration.php
    class-query.php
    class-urls.php
    class-indexation.php
    class-claim.php
    class-seo-presentation.php
    class-admin.php
  templates/
    archive-root.php
    archive-geo.php
    single-business.php
    parts/
  assets/
```

مسئولیت‌ها:

- `class-urls.php` تنها سازنده URL عمومی
- `class-query.php` با tax query روی `ad_country` و `bornado_business_cat`؛ از query آگهی استفاده نمی‌کند
- `class-routing-integration.php` مصرف‌کننده extension contract، نه fork resolver آگهی
- `class-quality-gate.php` محاسبه state هنگام save
- `class-claim.php` state ماشین مالکیت؛ verification فعلی فقط کانال ارسال است
- `class-seo-presentation.php` title، H1، description، breadcrumb items

`plugins/bornado-routing` فقط parse path، resolve کشور/شهر، و dispatch/registry عمومی را نگه می‌دارد. برای `route_family=business` مقدار canonical فقط از URL service افزونه دایرکتوری می‌آید؛ routing hook عمومی را اجرا می‌کند و canonical مستقل تولید یا overwrite نمی‌کند.

برای فاز ۱، `WP_Query` کافی است. quality تک‌رکورد با تغییر همان رکورد محاسبه می‌شود. eligibility هاب کشور/شهر/دسته محاسبه جدا است: هر تغییر publish، status، city، category، indexability، نام/sort، landing editorial، merge، privacy projection یا نسخه policy، cache هاب‌های قدیم و جدید را invalidate می‌کند. اگر آخرین عضو یک هاب حذف یا منتقل شود، آن هاب از sitemap خارج می‌شود. cache keyها versionedاند. هیچ‌کدام از این محاسبه‌ها در هر page view از صفر اجرا نمی‌شود. جدول lookup جدا برای UID الان لازم نیست. CPT در فاز ۱ `show_in_rest=false` است مگر API اختصاصی با allowlist بیاید.

اگر بعداً API JSON اضافه شد: version، allowlist فیلد عمومی، سقف pagination، و بدون leak ایمیل خصوصی یا evidence ادعا. API جایگزین HTML اول نمی‌شود و در فاز ۱ اجباری نیست.

## ۱۵. ثبت، claim و نقش آگهی

### فاز ۱ — عرضه کنترل‌شده

- تیم editorial کسب‌وکارهای واقعی Tier 1 را وارد می‌کند.
- فرم عمومی در صورت وجود فقط `pending` می‌سازد.
- انتشار عمومی بعد از بررسی کیفیت.
- duplicate احتمالی بر اساس تلفن، دامنه، نام نرمال‌شده و آدرس، به‌صورت دستی merge می‌شود؛ merge خودکار فقط با شباهت نام ممنوع است.

### فاز ۱.۵ — claim

- کاربر لاگین‌شده مالکیت را درخواست می‌کند.
- challenge به کانال از پیش ثبت‌شده همان کسب‌وکار می‌رود، نه شماره‌ای که claimant همان لحظه وارد می‌کند.
- اگر کانال قابل تأیید نیست: بررسی دستی.
- تغییر تلفن/ایمیل همزمان با claim تأیید خودکار نمی‌سازد.
- claim باید expiry، rate limit، تعداد تلاش، actor و تصمیم moderator داشته باشد.
- badge عمومی فقط پس از اثبات کنترل کانال یا بررسی دستی.
- بعد از تأیید: ویرایش ساعت، عکس، توضیح، لینک‌ها.
- تغییر شهر، نام، دامنه، تلفن و دسته اصلی staged است: صفحه publish فعلی تا تأیید moderator می‌ماند؛ پیشنهاد در revision جدا نگه داشته می‌شود؛ کل پست به `pending` تبدیل نمی‌شود؛ پذیرش تغییر اتمیک است
- invariant پیش از claim عمومی: `verified` بدون `owner_user_id` ممنوع؛ حداکثر یک claim فعال و یک مالک verified؛ `revoked` فوراً دسترسی و badge را برمی‌دارد؛ تغییر مالک transition جدا و auditشده است؛ `source` مالکیت فعلی نیست

### فاز ۲ — اتصال به آگهی

- رابطه قابل query بین `ad_post` و `bornado_business`
- نمایش اتصال فقط اگر verified باشد
- اسکیمای آگهی در این مرحله به `@id` پایدار کسب‌وکار وصل می‌شود
- آگهی بدون کسب‌وکار مثل امروز کار می‌کند

## ۱۶. جستجو و فیلتر دایرکتوری

جستجوی آگهی برای آگهی می‌ماند.

دایرکتوری URL service خواهر می‌خواهد:

- path: کشور، شهر، دسته، صفحه
- query allowlist: `q`, `sort`
- مقدار مجاز `sort`: `newest`, `name`؛ پیش‌فرض `newest`
- tie-breaker قطعی: `business_uid` یا post ID
- `sort` نامعتبر ۳۰۱ به نسخه بدون آن / پیش‌فرض
- نتایج `q` هم همان tie-breaker را دارند؛ ترتیب تصادفی برای pagination عمومی ممنوع است
- پارامتر خالی و پیش‌فرض حذف شود
- ترتیب query قطعی باشد

فیلترهای فاز ۱: نام، کشور/شهر، دسته.  
عقب‌افتاده: شعاع نقشه ایندکس‌شونده، قیمت، «باز الان» به‌صورت صفحه جدا، امتیاز.

کپی کامل `page-search.php` ممنوع است.

## ۱۷. امنیت، حریم خصوصی و moderation

1. capability جدا برای submit، edit own، moderate، verify و merge
2. nonce و authorization روی mutationها
3. sanitize بر اساس نوع فیلد؛ escape در خروجی
4. محدودیت MIME، حجم و تعداد تصویر
5. rate limit برای submit، claim و resend
6. audit برای claim و تغییر داده حساس
7. ایمیل به‌صورت پیش‌فرض عمومی نیست
8. گزارش کسب‌وکار جعلی، بسته، یا جعل هویت
9. revoke و انتقال مالکیت workflow دارد؛ takeover با تعویض شماره پروفایل ممکن نیست
10. جلوگیری از SSRF اگر بعداً fetch لوگو یا پیش‌نمایش سایت اضافه شود
11. مسیر عمومی درخواست اصلاح، اعتراض یا حذف
12. evidence و actorهای audit هرگز در HTML/JSON-LD/REST عمومی نیاید
13. `address_visibility=partial` از یک public projection واحد برای HTML، JSON-LD، REST و نقشه اعمال می‌شود: street/postal مخفی، مختصات حذف یا کاهش دقت، بدون EXIF در upload، بدون داده private در cache عمومی
14. مبنای انتشار: فقط فیلدهای عمومی allowlist‌شده پس از publish
15. CPT کسب‌وکار در فاز ۱ `show_in_rest = false` است مگر controller اختصاصی با allowlist و `auth_callback` صریح برای meta بیاید؛ ایمیل، evidence و actorهای audit در REST بومی نشت نمی‌کنند

## ۱۸. فازبندی اجرا

### فاز ۰ — قرارداد و اثبات فنی

قبل از UI عمومی:

1. قفل مدل location در برابر برند
2. قفل taxonomy به‌عنوان منبع حقیقت
3. قفل UID و `@id`
4. URL matrix کامل همین سند؛ از جمله `business_country_category` و `404` برای `/businesses/{category}/`
5. افزودن extension contract به `bornado-routing`
6. adapter خانواده route برای `seo_landing`
7. اصلاح `bornado_is_ad_search_view()` و helper `bornado_is_business_directory_view()`
8. تعریف quality policy و reason code
9. proof-of-concept: هاب شهر، هاب دسته شهر، یک تک‌صفحه
10. تست برگشت routeهای آگهی و geo guide
11. audit برخورد slug `businesses`: term/alias در `ad_cats`، `seo_landing` قدیمی، page/attachment، redirect رنک‌مث، rewrite سفارشی، و اصلاح زبان اسناد قدیمی (`SEO-INDEXATION-POLICY-SINGLE-SITE.md`, `MARKET-TIERING-PLAYBOOK.md`) پس از قفل قرارداد
12. قفل مالکیت مرکزی تاریخچه/alias برای `ad_country` و تست برگشت همزمان آگهی، دایرکتوری و geo guide پس از تغییر آزمایشی slug شهر

### فاز ۱ — عرضه editorial محدود

- CPT، taxonomy، validation، ادمین
- قالب root/country/country-category/city/city-category/single
- canonical، robots، sitemap Rank Math
- schema و breadcrumb و SEO presentation
- چند ده location واقعی در ۱ یا ۲ شهر Tier 1
- duplicate/merge دستی
- لینک از geo guide همان شهرها
- بدون ویرایش عمومی self-serve مگر submit به pending

معیار خروج فاز ۱:

- `/businesses/` با متن واقعی بالا است
- حداقل یک `/uk/london/businesses/` با موجودی واقعی indexable است
- تک‌صفحه‌ها موجودیت معتبر با `@id` پایدار می‌دهند
- روی routeهای `business_*` مقدار `bornado_is_ad_search_view()` برابر false است
- قالب فعال `page-search.php` یا `seo-landing.php` نیست
- هیچ asset یا modifier اسکرول نامحدود آگهی روی دایرکتوری فعال نیست
- `/uk/london/property/` و `/iranians/uk/london/` رفتار قبلی را حفظ کرده‌اند
- تغییر آزمایشی slug شهر، آگهی و دایرکتوری و geo guide را با ۳۰۱ مرکزی به canonical جدید همان خانواده می‌برد
- `sb-directory` همچنان خاموش است

### فاز ۱.۵ — claim امن

- state machine
- challenge به کانال کسب‌وکار یا review دستی
- audit و notification event جدید
- ویرایش مالک با moderation فیلدهای حساس

### فاز ۲ — اتصال آگهی و گسترش شهر

- relationship قابل query
- ارجاع schema آگهی به موجودیت verified
- گسترش شهر فقط پس از آستانه

### فاز ۳ — قابلیت پرریسک

- نظر واقعی با anti-abuse
- parent organization چندمکانه
- دسته عمیق‌تر
- geo radius فقط با نیاز واقعی
- API عمومی فقط با business case روشن

## ۱۹. کارهای صریح خارج از محدوده هر فاز اول

- بازنویسی هاب `/uk/` و `/uk/london/`
- افزودن `businesses` به mapping عمود آگهی `338..343`
- ویجت Elementor به‌عنوان منبع لندینگ
- ترجمه یا فعال‌سازی `sb-directory`
- ساخت CPT داخل `adforest-child`
- infinite scroll بدون `/page/{n}/`
- import انبوه برای ایندکس سریع. اگر بعداً importer وارد scope شد، پیش‌نیاز آن است نه gate فاز ۱: `source_namespace + external_id` یکتا، batch ID، timestamp و content hash، dry-run، نتیجه هر row، و اجرای دوباره بدون duplicate
- اجبار همه رکوردها به `LocalBusiness`
- ساخت `llms.txt` سفارشی به‌عنوان کار قطعی

## ۲۰. ریسک‌ها و تصمیم‌های ردشده

| گزینه | چرا رد شد |
|---|---|
| دسته آگهی `businesses` در `ad_cats` | صفحات موجود تبدیل به جستجوی آگهی می‌شوند |
| استفاده از `sb-directory` | CPT آن رویداد و رزرو است |
| نشانی فقط `/businesses/{country}/{city}/` | با country-first سایت دو سیستم URL می‌سازد |
| اتصال به routing فقط با فیلترهای موجود | چنین APIای امروز وجود ندارد |
| استفاده از قالب فعلی `seo-landing.php` | همیشه جستجوی آگهی را render می‌کند |
| پروفایل کاربر به‌جای CPT | هویت محلی نیست |
| لندینگ فقط با Elementor | indexability و مالک اسکیما از دست می‌رود |
| ایندکس همه ترکیب‌ها از روز اول | thin page و اسپم برای AI |
| کپی قالب جستجوی آگهی | کوئری و کارت آگهی به کسب‌وکار نمی‌خورد |
| `@id` برابر `{canonical}#localbusiness` | با تغییر slug هویت می‌شکند |
| ۳۰۱ هر کسب‌وکار بسته به هاب | soft-404 می‌سازد |
| claim با تأیید شماره پروفایل کاربر | اثبات کنترل کانال کسب‌وکار نیست |
| عوض کردن `source` از `import` به `owner` بعد از claim | provenance ورود از بین می‌رود |

ریسک اصلی اجرا: پر کردن سریع با import بی‌کیفیت.

## ۲۱. تداخل با بخش‌های موجود؛ تست برگشت

قبل از هر انتشار، این مسیرها باید بدون تغییر معنا بمانند:

- `/uk/`
- `/uk/london/`
- `/uk/london/property/`
- `/jobs/`
- `/ad/{hash}/{slug}`
- `/iranians/uk/london/`
- جستجوی آگهی و infinite scroll فعلی

تست دود دایرکتوری:

- `/businesses/` ۲۰۰، قالب دایرکتوری، و فهرست عمومی صفحه اول
- `/businesses/page/2/` self-canonical و اگر پایه indexable باشد `index,follow`؛ با `?sort=` برابر `noindex`؛ صفحه خارج از محدوده `404`
- `/businesses/grocery/` در فاز ۱ برابر `404`
- `/uk/businesses/grocery/` خانواده `business_country_category`، نه جستجوی آگهی
- `/uk/businesses/{organization-slug}/` فقط برای Organization کشوری معتبر ۲۰۰ است
- همان slug اگر فقط location شهری باشد، روی URL کشوری `404` است
- `/uk/london/businesses/` خانواده business، نه `country_city_category` آگهی
- `/uk/london/services/` همچنان آگهی است
- `/businesses/uk/london/` ۳۰۱ به `/uk/london/businesses/`
- `/page/1/` به پایه ۳۰۱ شود؛ صفحه خارج از محدوده ۴۰۴ شود
- تغییر slug یا شهر کسب‌وکار ۳۰۱ درست بسازد و `@id` عوض نشود
- پس از تغییر slug شهر در `ad_country`، `/uk/london/property/` و `/uk/london/businesses/` و `/iranians/uk/london/` هر کدام به canonical جدید همان خانواده ۳۰۱ شوند؛ query مجاز و pagination حفظ شود و state نامعتبر با ۳۰۱ عمومی پوشانده نشود
- شهر به کشور والد متعلق باشد
- primary category حتماً assign شده باشد
- فیلتر `?sort=` روی هاب `noindex` باشد و canonical به path تمیز همان هاب برود؛ `?q=` برابر `noindex` و self-canonical به همان query نرمال‌شده باشد؛ هیچ‌کدام در sitemap نباشند
- پیش‌نویس و import خام در sitemap نباشند
- NAP در HTML و JSON-LD یکی باشد
- graph بدون `Offer` جعلی، بدون `ItemList` آگهی، و بدون `Person` به‌جای کسب‌وکار باشد
- فقط یک main entity کسب‌وکار؛ LocalBusiness سراسری Rank Math با رکورد دایرکتوری ادغام نشود
- publisher سایت با کسب‌وکار فهرست‌شده یکی فرض نشود
- `numberOfItems` برابر تعداد visible همان صفحه باشد
- breadcrumb دیده شده با `BreadcrumbList` یکی باشد
- هر صفحه یک H1 اصلی داشته باشد

## ۲۲. معیار پذیرش محصول

1. هاب جهانی `/businesses/` هم intro دارد و هم فهرست صفحه‌بندی‌شده همه رکوردهای عمومی.
2. حداقل یک شهر Tier 1 هاب قابل ایندکس با موجودی واقعی دارد.
3. هر کسب‌وکار ایندکس‌شده NAP یا معادل اعتماد دارد و متن یکتا دارد.
4. گوگل و مدل می‌توانند از HTML بفهمند این موجودیت چیست، کجاست، و به کدام شهر/دسته تعلق دارد.
5. منطق آگهی routing و اسکیمای آگهی تغییر معنا نداده است؛ فقط extension سازگار اضافه شده.
6. مسیرهای آگهی و جامعه در تست برگشت سالم‌اند.
7. pipeline جستجوی آگهی روی هیچ route دایرکتوری فعال نیست.

## ۲۳. اسناد بعدی پس از تصویب این پلن

قبل از فاز ۱ لازم‌اند:

- `docs/business-directory/url-contract.md` — ماتریس URL، q/sort، تاریخچه slug کسب‌وکار و `bornado_business_cat`، لینک pagination
- `docs/business-directory/data-model.md` — publish اتمیک، merge، service area، invariantهای claim
- `docs/business-directory/indexation-rules.md` — canonical query، سیاست crawl برای `q`، ماتریس وابستگی cache/sitemap/robots
- `docs/business-directory/integration-contracts.md` — hydration، early bypass، template dispatch، اولویت hook، و مالکیت مرکزی geo-alias برای `ad_country` به‌همراه مصرف آن در routing آگهی و geo guide

`claim-threat-model` پیش از فاز ۱.۵ نوشته می‌شود، نه به‌عنوان مانع شروع اسکلت editorial.

تا آن زمان، همین فایل منبع حقیقت تصمیم است.

## ۲۴. خلاصه اجرایی

Bornado دایرکتوری را به‌عنوان موجودیت پایدار جدا می‌سازد. جغرافیا و روتینگ و اسکیما و احراز هویت فعلی دوباره نوشته نمی‌شوند، اما routing و لندینگ و تشخیص view آگهی امروز API لازم را ندارند و باید با قرارداد کوچک توسعه‌پذیر شوند. لندینگ `/businesses/` هم هاب editorial است و هم فهرست صفحه‌بندی‌شده همه رکوردهای عمومی. هاب‌های بازار country-first می‌مانند. آگهی، خدمات، و راهنمای ایرانیان سرجایشان می‌مانند. پایداری URL و هویت، محتوای واقعی و دادهٔ قابل تأیید احتمال فهم صحیح و استنادپذیری را بالا می‌برند؛ هیچ‌کدام تضمین رتبه یا نقل توسط گوگل یا مدل‌های AI نیستند.
